<?php

namespace App\Http\Controllers;

use App\Models\AiConversation;
use App\Models\AiMessage;
use App\Models\Project;
use App\Models\SystemSetting;
use App\Services\KoreContextAssembler;
use App\Services\OllamaChatService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Kore AI chat controller.
 *
 * Routes:
 *   GET  /ai                           → redirect to last conversation or create new
 *   GET  /ai/new                       → create conversation, redirect
 *   GET  /ai/{conversation}            → chat UI
 *   POST /ai/{conversation}/chat       → streaming chat (SSE via fetch)
 *   PUT  /ai/{conversation}            → rename / change model / set project scope
 *   DELETE /ai/{conversation}          → delete conversation
 *   GET  /ai/api/models                → list available Ollama models (JSON)
 *   GET  /ai/api/status                → Ollama health check (JSON)
 */
class AiController extends Controller
{
    public function __construct(
        private readonly OllamaChatService    $ollama,
        private readonly KoreContextAssembler $assembler,
    ) {}

    // ── Conversation Navigation ────────────────────────────────────────────────

    public function index(): RedirectResponse
    {
        $last = AiConversation::where('user_id', auth()->id())
            ->latest()
            ->first();

        if ($last) {
            return redirect()->route('ai.show', $last);
        }

        return redirect()->route('ai.new');
    }

    public function create(Request $request): RedirectResponse
    {
        $defaultModel = SystemSetting::get('ai_default_model', 'llama3');

        $conversation = AiConversation::create([
            'user_id'            => auth()->id(),
            'model'              => $request->input('model', $defaultModel),
            'context_project_id' => $request->input('project_id'),
        ]);

        return redirect()->route('ai.show', $conversation);
    }

    public function show(AiConversation $conversation): View
    {
        $this->authorise($conversation);

        $messages         = $conversation->messages()->orderBy('created_at')->get();
        $allConversations = AiConversation::where('user_id', auth()->id())
            ->orderByDesc('updated_at')
            ->limit(30)
            ->get();

        $enabledModels = $this->ollama->getEnabledModels();
        $projects      = Project::whereHas('status', fn($q) => $q->where('name', 'In Progress'))
            ->orderBy('project_number')
            ->get(['id', 'project_number', 'title']);

        return view('ai.chat', compact(
            'conversation', 'messages', 'allConversations', 'enabledModels', 'projects'
        ));
    }

    public function update(Request $request, AiConversation $conversation): JsonResponse
    {
        $this->authorise($conversation);

        $data = $request->validate([
            'title'              => ['nullable', 'string', 'max:200'],
            'model'              => ['nullable', 'string', 'max:100'],
            'context_project_id' => ['nullable', 'exists:projects,id'],
        ]);

        $conversation->update(array_filter($data, fn($v) => $v !== null));

        return response()->json(['ok' => true, 'conversation' => $conversation->fresh()]);
    }

    public function destroy(AiConversation $conversation): RedirectResponse
    {
        $this->authorise($conversation);
        $conversation->delete();

        $next = AiConversation::where('user_id', auth()->id())->latest()->first();
        if ($next) {
            return redirect()->route('ai.show', $next);
        }

        return redirect()->route('ai.new');
    }

    // ── Streaming Chat ─────────────────────────────────────────────────────────

    /**
     * Process a chat message and stream the AI response via Server-Sent Events.
     *
     * The browser calls this with fetch() + ReadableStream (NOT EventSource)
     * so it can send a POST with the CSRF token. Each SSE data line is:
     *   data: {"content": "chunk of text"}
     *   data: {"sources": [...], "done": true, "token_count": 123, "ms": 1500}
     *
     * The conversation history is replayed to Ollama each turn (stateless LLM).
     * Context is assembled fresh so it always reflects latest DB state.
     */
    public function chat(Request $request, AiConversation $conversation): StreamedResponse
    {
        $this->authorise($conversation);

        $userMessage = $request->validate([
            'message' => ['required', 'string', 'max:4000'],
            'model'   => ['nullable', 'string', 'max:100'],
        ])['message'];

        $model = $request->input('model', $conversation->model);

        // Update conversation model if changed
        if ($model !== $conversation->model) {
            $conversation->update(['model' => $model]);
        }

        // Save user message immediately (before streaming starts)
        $isFirstMessage = $conversation->message_count === 0;
        AiMessage::create([
            'conversation_id' => $conversation->id,
            'role'            => 'user',
            'content'         => $userMessage,
        ]);
        $conversation->recordTurn($isFirstMessage ? $userMessage : '');

        // Assemble context (does DB queries + RAG search)
        $contextResult = $this->assembler->assembleContext(
            $userMessage,
            auth()->user(),
            $conversation->context_project_id
        );

        // Build full messages array for Ollama
        $systemPrompt = $this->assembler->buildSystemPrompt(auth()->user());

        if ($contextResult['context']) {
            $systemPrompt .= "\n\n" . str_repeat('═', 60)
                . "\nCURRENT CONTEXT (live ERP data as of " . now()->format('H:i') . "):\n"
                . str_repeat('═', 60) . "\n\n"
                . $contextResult['context'];
        }

        $history  = $conversation->recentHistory(turns: 8);
        $messages = array_merge(
            [['role' => 'system', 'content' => $systemPrompt]],
            $history,
            [['role' => 'user', 'content' => $userMessage]]
        );

        $startTime      = microtime(true);
        $fullResponse   = '';
        $stats          = [];
        $sources        = $contextResult['sources'];

        return response()->stream(function () use (
            $messages, $model, $conversation, $sources, $startTime, &$fullResponse, &$stats
        ) {
            // Disable output buffering for real-time streaming
            while (ob_get_level() > 0) {
                ob_end_flush();
            }

            try {
                $this->ollama->stream(
                    messages: $messages,
                    model:    $model,
                    onChunk:  function (string $chunk) use (&$fullResponse) {
                        $fullResponse .= $chunk;
                        echo 'data: ' . json_encode(['content' => $chunk]) . "\n\n";
                        flush();
                    },
                    onDone: function (array $s) use (&$stats) {
                        $stats = $s;
                    }
                );
            } catch (\Throwable $e) {
                Log::error('Kore AI streaming error', [
                    'conversation_id' => $conversation->id,
                    'model'           => $model,
                    'error'           => $e->getMessage(),
                ]);

                $errorMsg = "I'm sorry — I encountered an error connecting to Ollama. "
                    . "Please ensure Ollama is running and the model is installed.\n\n"
                    . "> Error: " . $e->getMessage();

                echo 'data: ' . json_encode(['content' => $errorMsg]) . "\n\n";
                flush();
                $fullResponse = $errorMsg;
            }

            // Persist the assistant's response
            $processingMs = (int) ((microtime(true) - $startTime) * 1000);

            AiMessage::create([
                'conversation_id'    => $conversation->id,
                'role'               => 'assistant',
                'content'            => $fullResponse,
                'model'              => $model,
                'sources'            => $sources,
                'processing_time_ms' => $processingMs,
                'token_count'        => $stats['token_count'] ?? null,
            ]);

            $conversation->increment('message_count');

            // Signal completion with metadata for the UI
            echo 'data: ' . json_encode([
                'done'        => true,
                'sources'     => $sources,
                'token_count' => $stats['token_count'] ?? null,
                'ms'          => $processingMs,
            ]) . "\n\n";
            flush();

        }, 200, [
            'Content-Type'     => 'text/event-stream',
            'Cache-Control'    => 'no-cache, no-store',
            'X-Accel-Buffering'=> 'no',    // Disable nginx proxy buffering
            'Connection'       => 'keep-alive',
        ]);
    }

    // ── API Endpoints ──────────────────────────────────────────────────────────

    /** List installed Ollama models (JSON — for model selector). */
    public function apiModels(): JsonResponse
    {
        try {
            $installed = $this->ollama->getInstalledModels();
            $enabled   = $this->ollama->getEnabledModels();

            return response()->json([
                'available' => true,
                'installed' => $installed,
                'enabled'   => $enabled,
                'default'   => SystemSetting::get('ai_default_model', 'llama3'),
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'available' => false,
                'error'     => $e->getMessage(),
                'installed' => [],
                'enabled'   => [],
            ], 503);
        }
    }

    /** Ollama health check. */
    public function apiStatus(): JsonResponse
    {
        $available = $this->ollama->isAvailable();
        $models    = [];

        if ($available) {
            try {
                $models = $this->ollama->getInstalledModels();
            } catch (\Throwable) {}
        }

        return response()->json([
            'available'    => $available,
            'host'         => config('services.ollama.host'),
            'model_count'  => count($models),
            'ai_enabled'   => (bool) SystemSetting::get('ai_enabled', '1'),
        ], $available ? 200 : 503);
    }

    // ── Private ────────────────────────────────────────────────────────────────

    private function authorise(AiConversation $conversation): void
    {
        if ($conversation->user_id !== auth()->id()) {
            abort(403);
        }
    }
}
