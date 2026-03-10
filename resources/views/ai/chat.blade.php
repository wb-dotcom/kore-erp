@extends('layouts.app')

@section('title', 'Kore AI')

@push('head')
{{-- marked.js for markdown rendering --}}
<script src="https://cdn.jsdelivr.net/npm/marked@9.1.6/marked.min.js"></script>
{{-- highlight.js for code blocks --}}
<link rel="stylesheet" href="https://cdn.jsdelivr.net/gh/highlightjs/cdn-release@11.9.0/build/styles/github.min.css">
<script src="https://cdn.jsdelivr.net/gh/highlightjs/cdn-release@11.9.0/build/highlight.min.js"></script>

<style>
/* ── Kore AI Layout ────────────────────────────────────────────────────── */
.kore-ai-layout {
    display: flex;
    height: calc(100vh - 0px);
    overflow: hidden;
}

/* Left panel: conversation history */
.ai-sidebar {
    width: 260px;
    min-width: 260px;
    background: #fff;
    border-right: 1px solid #e5e7eb;
    display: flex;
    flex-direction: column;
    overflow: hidden;
}

.ai-sidebar-header {
    padding: 16px;
    border-bottom: 1px solid #e5e7eb;
}

.ai-conv-list {
    flex: 1;
    overflow-y: auto;
    padding: 8px;
}

.ai-conv-item {
    display: block;
    padding: 8px 12px;
    border-radius: 8px;
    margin-bottom: 2px;
    cursor: pointer;
    text-decoration: none;
    color: #374151;
    transition: background 0.15s;
    border: none;
    background: none;
    width: 100%;
    text-align: left;
}
.ai-conv-item:hover { background: #f3f4f6; color: #111; }
.ai-conv-item.active { background: #eff6ff; color: #1d4ed8; }

.ai-conv-title {
    font-size: 0.8rem;
    font-weight: 500;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
    display: block;
}
.ai-conv-meta {
    font-size: 0.7rem;
    color: #9ca3af;
    display: flex;
    gap: 6px;
    margin-top: 2px;
}

/* Main chat area */
.ai-main {
    flex: 1;
    display: flex;
    flex-direction: column;
    overflow: hidden;
    background: #f9fafb;
}

.ai-header {
    background: #fff;
    border-bottom: 1px solid #e5e7eb;
    padding: 12px 20px;
    display: flex;
    align-items: center;
    gap: 12px;
    min-height: 60px;
}

.ai-header-title {
    flex: 1;
    font-weight: 600;
    font-size: 0.9rem;
}

/* Model selector */
.model-selector {
    position: relative;
    display: inline-flex;
    align-items: center;
    gap: 6px;
}
.model-badge {
    background: #1a1d23;
    color: #a9b0be;
    border: none;
    border-radius: 20px;
    padding: 4px 12px;
    font-size: 0.72rem;
    font-weight: 500;
    cursor: pointer;
    display: inline-flex;
    align-items: center;
    gap: 5px;
    transition: background 0.15s;
}
.model-badge:hover { background: #2d3139; color: #fff; }
.model-badge .bi { font-size: 0.65rem; }

.model-dropdown {
    position: absolute;
    top: calc(100% + 8px);
    right: 0;
    background: #fff;
    border: 1px solid #e5e7eb;
    border-radius: 10px;
    box-shadow: 0 8px 24px rgba(0,0,0,0.12);
    min-width: 240px;
    z-index: 500;
    overflow: hidden;
    display: none;
}
.model-dropdown.open { display: block; }
.model-option {
    padding: 10px 16px;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 10px;
    transition: background 0.1s;
}
.model-option:hover { background: #f3f4f6; }
.model-option.selected { background: #eff6ff; }
.model-name { font-size: 0.82rem; font-weight: 600; }
.model-meta { font-size: 0.7rem; color: #6b7280; }
.model-check { color: #2563eb; display: none; }
.model-option.selected .model-check { display: block; }

/* Messages area */
.ai-messages {
    flex: 1;
    overflow-y: auto;
    padding: 24px 0;
    scroll-behavior: smooth;
}

.ai-message-wrapper {
    max-width: 800px;
    margin: 0 auto;
    padding: 0 24px;
    margin-bottom: 20px;
}

/* User message */
.msg-user {
    display: flex;
    justify-content: flex-end;
    gap: 10px;
}
.msg-user .bubble {
    background: #1d4ed8;
    color: #fff;
    border-radius: 16px 16px 4px 16px;
    padding: 10px 16px;
    max-width: 70%;
    font-size: 0.875rem;
    line-height: 1.5;
    white-space: pre-wrap;
}

/* AI message */
.msg-assistant {
    display: flex;
    gap: 12px;
    align-items: flex-start;
}
.ai-avatar {
    width: 32px;
    height: 32px;
    border-radius: 10px;
    background: #1a1d23;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
    font-size: 0.75rem;
    font-weight: 700;
    color: #4c8bf5;
    letter-spacing: -0.5px;
}
.msg-assistant .bubble {
    background: #fff;
    border: 1px solid #e5e7eb;
    border-radius: 4px 16px 16px 16px;
    padding: 14px 18px;
    max-width: 80%;
    font-size: 0.875rem;
    line-height: 1.65;
    box-shadow: 0 1px 3px rgba(0,0,0,0.04);
}

/* Markdown styles inside AI bubble */
.msg-assistant .bubble h1,
.msg-assistant .bubble h2 { font-size: 1rem; font-weight: 700; margin-top: 14px; }
.msg-assistant .bubble h3 { font-size: 0.9rem; font-weight: 600; margin-top: 12px; }
.msg-assistant .bubble p  { margin-bottom: 8px; }
.msg-assistant .bubble ul,
.msg-assistant .bubble ol { padding-left: 20px; margin-bottom: 8px; }
.msg-assistant .bubble li { margin-bottom: 4px; }
.msg-assistant .bubble code {
    background: #f3f4f6;
    border-radius: 4px;
    padding: 1px 5px;
    font-size: 0.82rem;
    font-family: 'Fira Code', monospace;
}
.msg-assistant .bubble pre {
    background: #1a1d23;
    color: #e5e7eb;
    border-radius: 8px;
    padding: 12px 16px;
    overflow-x: auto;
    margin: 8px 0;
}
.msg-assistant .bubble pre code { background: none; color: inherit; padding: 0; }
.msg-assistant .bubble table { width: 100%; border-collapse: collapse; margin: 10px 0; }
.msg-assistant .bubble th { background: #f3f4f6; padding: 6px 10px; text-align: left; font-size: 0.8rem; }
.msg-assistant .bubble td { padding: 5px 10px; border-bottom: 1px solid #e5e7eb; font-size: 0.82rem; }
.msg-assistant .bubble strong { font-weight: 600; }
.msg-assistant .bubble blockquote {
    border-left: 3px solid #e5e7eb;
    padding-left: 12px;
    color: #6b7280;
    margin: 8px 0;
}

/* Sources panel */
.sources-bar {
    margin-top: 8px;
    padding-top: 8px;
    border-top: 1px solid #f0f0f0;
}
.sources-toggle {
    font-size: 0.7rem;
    color: #9ca3af;
    cursor: pointer;
    display: inline-flex;
    align-items: center;
    gap: 4px;
    background: none;
    border: none;
    padding: 0;
}
.sources-toggle:hover { color: #4b5563; }
.sources-list { display: none; margin-top: 6px; display: flex; flex-wrap: wrap; gap: 5px; }
.source-chip {
    background: #f3f4f6;
    border: 1px solid #e5e7eb;
    border-radius: 20px;
    padding: 2px 10px;
    font-size: 0.68rem;
    color: #4b5563;
    display: inline-flex;
    align-items: center;
    gap: 4px;
}

/* Thinking indicator */
.thinking-dots {
    display: inline-flex;
    gap: 4px;
    align-items: center;
    padding: 6px 0;
}
.thinking-dots span {
    width: 6px; height: 6px;
    border-radius: 50%;
    background: #9ca3af;
    animation: think 1.2s infinite;
}
.thinking-dots span:nth-child(2) { animation-delay: 0.2s; }
.thinking-dots span:nth-child(3) { animation-delay: 0.4s; }
@keyframes think {
    0%, 60%, 100% { transform: translateY(0); opacity: 0.4; }
    30% { transform: translateY(-4px); opacity: 1; }
}

/* Input area */
.ai-input-area {
    background: #fff;
    border-top: 1px solid #e5e7eb;
    padding: 16px 24px;
}
.ai-input-inner {
    max-width: 800px;
    margin: 0 auto;
    position: relative;
}
.ai-textarea {
    width: 100%;
    border: 1.5px solid #e5e7eb;
    border-radius: 12px;
    padding: 12px 52px 12px 16px;
    font-size: 0.875rem;
    line-height: 1.5;
    resize: none;
    outline: none;
    font-family: inherit;
    min-height: 50px;
    max-height: 200px;
    overflow-y: auto;
    transition: border-color 0.2s;
}
.ai-textarea:focus { border-color: #2563eb; }
.ai-send-btn {
    position: absolute;
    right: 10px;
    bottom: 10px;
    width: 34px; height: 34px;
    border-radius: 8px;
    background: #1d4ed8;
    border: none;
    color: #fff;
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    transition: background 0.15s;
}
.ai-send-btn:hover { background: #1e40af; }
.ai-send-btn:disabled { background: #9ca3af; cursor: not-allowed; }

.ai-input-meta {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-top: 6px;
    font-size: 0.7rem;
    color: #9ca3af;
}

/* Welcome screen */
.ai-welcome {
    max-width: 680px;
    margin: 60px auto;
    padding: 0 24px;
    text-align: center;
}
.ai-welcome h2 { font-size: 1.4rem; font-weight: 700; margin-bottom: 8px; }
.ai-welcome p { color: #6b7280; font-size: 0.875rem; margin-bottom: 30px; }
.suggestion-grid {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 10px;
}
.suggestion-card {
    background: #fff;
    border: 1px solid #e5e7eb;
    border-radius: 10px;
    padding: 14px 16px;
    text-align: left;
    cursor: pointer;
    transition: border-color 0.15s, box-shadow 0.15s;
    font-size: 0.82rem;
    color: #374151;
}
.suggestion-card:hover {
    border-color: #2563eb;
    box-shadow: 0 2px 8px rgba(37,99,235,0.08);
}
.suggestion-card strong { display: block; margin-bottom: 3px; font-size: 0.8rem; color: #111; }

/* Scrollbar */
.ai-messages::-webkit-scrollbar { width: 4px; }
.ai-messages::-webkit-scrollbar-track { background: transparent; }
.ai-messages::-webkit-scrollbar-thumb { background: #d1d5db; border-radius: 4px; }

/* Ollama offline banner */
.ollama-offline {
    background: #fef2f2;
    border: 1px solid #fecaca;
    color: #991b1b;
    border-radius: 8px;
    padding: 10px 14px;
    font-size: 0.8rem;
    margin-bottom: 12px;
}
</style>
@endpush

@section('content')
<div class="kore-ai-layout" style="margin: -24px;">

    {{-- ── Left Sidebar: Conversations ───────────────────────────────────── --}}
    <div class="ai-sidebar">
        <div class="ai-sidebar-header">
            <a href="{{ route('ai.new') }}" class="btn btn-sm btn-primary w-100">
                <i class="bi bi-plus-lg me-1"></i> New Chat
            </a>
        </div>

        <div class="ai-conv-list">
            @forelse($allConversations as $conv)
            <a href="{{ route('ai.show', $conv) }}"
               class="ai-conv-item {{ $conv->id === $conversation->id ? 'active' : '' }}">
                <span class="ai-conv-title">
                    {{ $conv->title ?? 'New conversation' }}
                </span>
                <div class="ai-conv-meta">
                    <span>{{ $conv->model }}</span>
                    <span>·</span>
                    <span>{{ $conv->updated_at->diffForHumans(short: true) }}</span>
                </div>
            </a>
            @empty
            <div class="text-muted px-2 py-3" style="font-size:0.78rem;">No conversations yet.</div>
            @endforelse
        </div>

        {{-- Conversation actions --}}
        @if($conversation->id)
        <div class="p-2 border-top">
            <form action="{{ route('ai.destroy', $conversation) }}" method="POST"
                  onsubmit="return confirm('Delete this conversation?')">
                @csrf @method('DELETE')
                <button type="submit" class="btn btn-sm btn-outline-danger w-100">
                    <i class="bi bi-trash me-1"></i> Delete conversation
                </button>
            </form>
        </div>
        @endif
    </div>

    {{-- ── Main Chat Panel ────────────────────────────────────────────────── --}}
    <div class="ai-main">

        {{-- Header --}}
        <div class="ai-header">
            <div class="ai-avatar" style="width:28px;height:28px;border-radius:8px;">AI</div>
            <div class="ai-header-title">
                <span id="conv-title">{{ $conversation->title ?? 'Kore AI' }}</span>
                @if($conversation->contextProject)
                <span class="badge bg-light text-dark border ms-2" style="font-size:0.68rem;">
                    <i class="bi bi-folder me-1"></i>{{ $conversation->contextProject->project_number }}
                </span>
                @endif
            </div>

            {{-- Project scope selector --}}
            <div class="dropdown me-1">
                <button class="btn btn-sm btn-outline-secondary dropdown-toggle" type="button"
                        data-bs-toggle="dropdown" style="font-size:0.75rem;">
                    <i class="bi bi-folder me-1"></i>
                    {{ $conversation->contextProject?->project_number ?? 'All projects' }}
                </button>
                <ul class="dropdown-menu dropdown-menu-end">
                    <li>
                        <button class="dropdown-item" onclick="setProjectScope(null)">
                            <i class="bi bi-globe me-2"></i>All projects
                        </button>
                    </li>
                    <li><hr class="dropdown-divider"></li>
                    @foreach($projects as $p)
                    <li>
                        <button class="dropdown-item" onclick="setProjectScope({{ $p->id }})">
                            <span class="fw-600">{{ $p->project_number }}</span>
                            <span class="text-muted ms-1" style="font-size:0.78rem;">{{ Str::limit($p->title, 30) }}</span>
                        </button>
                    </li>
                    @endforeach
                </ul>
            </div>

            {{-- Model selector --}}
            <div class="model-selector" id="modelSelector">
                <button class="model-badge" onclick="toggleModelDropdown(event)">
                    <i class="bi bi-cpu"></i>
                    <span id="selectedModelLabel">{{ $conversation->model }}</span>
                    <i class="bi bi-chevron-down"></i>
                </button>
                <div class="model-dropdown" id="modelDropdown">
                    <div style="padding:10px 16px 6px;font-size:0.7rem;color:#9ca3af;font-weight:600;letter-spacing:0.05em;">
                        AVAILABLE MODELS
                    </div>
                    <div id="modelList">
                        <div style="padding:10px 16px;font-size:0.8rem;color:#6b7280;">Loading…</div>
                    </div>
                    <div style="padding:6px 10px;border-top:1px solid #f3f4f6;">
                        <a href="https://ollama.com/library" target="_blank"
                           style="font-size:0.7rem;color:#6b7280;text-decoration:none;">
                            <i class="bi bi-box-arrow-up-right me-1"></i>Browse Ollama library
                        </a>
                    </div>
                </div>
            </div>
        </div>

        {{-- Messages --}}
        <div class="ai-messages" id="messagesArea">
            @if($messages->isEmpty())
            {{-- Welcome screen --}}
            <div class="ai-welcome" id="welcomeScreen">
                <div class="ai-avatar mx-auto mb-3" style="width:48px;height:48px;border-radius:14px;font-size:1rem;">AI</div>
                <h2>Kore AI</h2>
                <p>Ask anything about your projects, proposals, team, or finances.<br>
                   I have live access to all data in the system.</p>

                <div class="suggestion-grid">
                    <div class="suggestion-card" onclick="useSuggestion(this)">
                        <strong>📊 Portfolio health check</strong>
                        Show me all active projects with budget risk flags
                    </div>
                    <div class="suggestion-card" onclick="useSuggestion(this)">
                        <strong>💰 Outstanding invoices</strong>
                        What invoices are overdue and how much is outstanding?
                    </div>
                    <div class="suggestion-card" onclick="useSuggestion(this)">
                        <strong>📋 My tasks</strong>
                        What are my open tasks and upcoming deadlines?
                    </div>
                    <div class="suggestion-card" onclick="useSuggestion(this)">
                        <strong>📈 Proposal pipeline</strong>
                        Summarise all open proposals and their total estimated fees
                    </div>
                    <div class="suggestion-card" onclick="useSuggestion(this)">
                        <strong>⏱ Team utilization</strong>
                        Who has the most hours logged this month and on which projects?
                    </div>
                    <div class="suggestion-card" onclick="useSuggestion(this)">
                        <strong>🔍 Document search</strong>
                        Search project documents for structural engineering comments
                    </div>
                </div>
            </div>
            @else
            {{-- Render existing messages --}}
            @foreach($messages as $msg)
                @if($msg->role === 'user')
                <div class="ai-message-wrapper">
                    <div class="msg-user">
                        <div class="bubble">{{ $msg->content }}</div>
                    </div>
                </div>
                @elseif($msg->role === 'assistant')
                <div class="ai-message-wrapper">
                    <div class="msg-assistant">
                        <div class="ai-avatar">AI</div>
                        <div>
                            <div class="bubble" data-rendered="true">
                                <div class="msg-content">{!! nl2br(e($msg->content)) !!}</div>
                            </div>
                            @if($msg->sources && count($msg->sources) > 0)
                            <div class="sources-bar">
                                <button class="sources-toggle" onclick="toggleSources(this)">
                                    <i class="bi bi-database me-1"></i>
                                    {{ count($msg->sources) }} source{{ count($msg->sources) !== 1 ? 's' : '' }} used
                                    <i class="bi bi-chevron-down"></i>
                                </button>
                                <div class="sources-list mt-1">
                                    @foreach($msg->sources as $source)
                                    <span class="source-chip">
                                        <i class="bi bi-{{ $source['type'] === 'document' ? 'file-earmark' : ($source['type'] === 'project' ? 'folder' : 'database') }}"></i>
                                        {{ $source['label'] }}
                                    </span>
                                    @endforeach
                                </div>
                            </div>
                            @endif
                            <div style="font-size:0.68rem;color:#9ca3af;margin-top:4px;">
                                {{ $msg->model }}
                                @if($msg->processing_time_ms) · {{ round($msg->processing_time_ms/1000,1) }}s @endif
                                @if($msg->token_count) · {{ $msg->token_count }} tokens @endif
                            </div>
                        </div>
                    </div>
                </div>
                @endif
            @endforeach
            @endif
        </div>

        {{-- Input --}}
        <div class="ai-input-area">
            <div class="ai-input-inner">
                <div id="ollamaOfflineBanner" class="ollama-offline d-none">
                    <i class="bi bi-exclamation-triangle me-2"></i>
                    <strong>Ollama is not running.</strong>
                    Start it with <code>ollama serve</code> then refresh.
                    Model selected: <span id="offlineModel"></span>
                </div>
                <textarea class="ai-textarea" id="chatInput"
                          placeholder="Ask about any project, proposal, invoice, team member, or document…"
                          rows="1"
                          onkeydown="handleInputKeydown(event)"
                          oninput="autoResize(this)"></textarea>
                <button class="ai-send-btn" id="sendBtn" onclick="sendMessage()" title="Send (Enter)">
                    <i class="bi bi-send-fill" style="font-size:0.8rem;"></i>
                </button>
            </div>
            <div class="ai-input-inner">
                <div class="ai-input-meta">
                    <span>Enter to send · Shift+Enter for new line</span>
                    <span id="streamingStatus" style="color:#2563eb;display:none;">
                        <i class="bi bi-circle-fill me-1" style="font-size:0.5rem;animation:think 1.2s infinite;"></i>
                        Generating…
                    </span>
                </div>
            </div>
        </div>

    </div>{{-- end .ai-main --}}
</div>{{-- end .kore-ai-layout --}}
@endsection

@push('scripts')
<script>
const CONVERSATION_ID = {{ $conversation->id }};
const CHAT_URL        = '{{ route('ai.chat', $conversation) }}';
const UPDATE_URL      = '{{ route('ai.update', $conversation) }}';
const MODELS_URL      = '{{ route('api.ai.models') }}';
const CSRF_TOKEN      = document.querySelector('meta[name="csrf-token"]').content;

let selectedModel = '{{ $conversation->model }}';
let isStreaming    = false;
let firstMessage   = {{ $messages->isEmpty() ? 'true' : 'false' }};

// ── Markdown renderer setup ──────────────────────────────────────────────────
marked.setOptions({
    breaks: true,
    gfm: true,
    highlight: function(code, lang) {
        if (lang && hljs.getLanguage(lang)) {
            return hljs.highlight(code, { language: lang }).value;
        }
        return hljs.highlightAuto(code).value;
    }
});

// ── On load ───────────────────────────────────────────────────────────────────
document.addEventListener('DOMContentLoaded', () => {
    // Render existing assistant messages
    document.querySelectorAll('.msg-assistant .msg-content').forEach(el => {
        const raw = el.innerText;
        el.innerHTML = marked.parse(raw);
        el.querySelectorAll('pre code').forEach(block => hljs.highlightElement(block));
    });

    loadModels();
    scrollToBottom();
    document.getElementById('chatInput').focus();
});

// ── Model selector ────────────────────────────────────────────────────────────
async function loadModels() {
    try {
        const res  = await fetch(MODELS_URL);
        const data = await res.json();

        if (!data.available) {
            document.getElementById('ollamaOfflineBanner').classList.remove('d-none');
            document.getElementById('offlineModel').textContent = selectedModel;
        }

        const list = document.getElementById('modelList');
        if (!data.enabled || data.enabled.length === 0) {
            list.innerHTML = '<div style="padding:10px 16px;font-size:0.8rem;color:#ef4444;">No models installed.<br>Run: <code>ollama pull llama3</code></div>';
            return;
        }

        list.innerHTML = data.enabled.map(modelName => {
            const installed = data.installed.find(m => m.name.startsWith(modelName) || m.name === modelName);
            const size  = installed ? `${installed.size_gb}GB` : '';
            const param = installed?.params ?? '';
            return `<div class="model-option ${modelName === selectedModel ? 'selected' : ''}"
                        onclick="selectModel('${modelName}')">
                <div>
                    <div class="model-name">${modelName}</div>
                    <div class="model-meta">${param}${param && size ? ' · ' : ''}${size}</div>
                </div>
                <i class="bi bi-check2 model-check"></i>
            </div>`;
        }).join('');

    } catch (e) {
        document.getElementById('modelList').innerHTML =
            '<div style="padding:10px 16px;font-size:0.8rem;color:#9ca3af;">Could not reach Ollama.</div>';
    }
}

function toggleModelDropdown(e) {
    e.stopPropagation();
    document.getElementById('modelDropdown').classList.toggle('open');
}

document.addEventListener('click', () => {
    document.getElementById('modelDropdown').classList.remove('open');
});

function selectModel(name) {
    selectedModel = name;
    document.getElementById('selectedModelLabel').textContent = name;
    document.querySelectorAll('.model-option').forEach(el => el.classList.remove('selected'));
    document.querySelectorAll('.model-option .model-check').forEach(el => el.style.display = 'none');
    event.currentTarget.classList.add('selected');
    document.getElementById('modelDropdown').classList.remove('open');

    // Persist model choice to the conversation
    fetch(UPDATE_URL, {
        method: 'PUT',
        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF_TOKEN },
        body: JSON.stringify({ model: name }),
    });
}

// ── Project scope ─────────────────────────────────────────────────────────────
function setProjectScope(projectId) {
    fetch(UPDATE_URL, {
        method: 'PUT',
        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF_TOKEN },
        body: JSON.stringify({ context_project_id: projectId }),
    }).then(() => location.reload());
}

// ── Input handling ────────────────────────────────────────────────────────────
function handleInputKeydown(e) {
    if (e.key === 'Enter' && !e.shiftKey) {
        e.preventDefault();
        sendMessage();
    }
}

function autoResize(el) {
    el.style.height = 'auto';
    el.style.height = Math.min(el.scrollHeight, 200) + 'px';
}

function useSuggestion(card) {
    const text = card.querySelector('strong').nextSibling.textContent.trim();
    document.getElementById('chatInput').value = text;
    autoResize(document.getElementById('chatInput'));
    sendMessage();
}

// ── Core chat / streaming ─────────────────────────────────────────────────────
async function sendMessage() {
    if (isStreaming) return;

    const input   = document.getElementById('chatInput');
    const message = input.value.trim();
    if (!message) return;

    input.value = '';
    input.style.height = 'auto';

    // Hide welcome screen on first message
    const welcome = document.getElementById('welcomeScreen');
    if (welcome) welcome.remove();

    appendUserMessage(message);

    setStreaming(true);

    // Create the AI message placeholder with a thinking indicator
    const { wrapper, contentEl, metaEl } = appendAssistantMessage();

    let fullText    = '';
    let sourcesData = [];

    try {
        const response = await fetch(CHAT_URL, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': CSRF_TOKEN,
                'Accept': 'text/event-stream',
            },
            body: JSON.stringify({ message, model: selectedModel }),
        });

        if (!response.ok) {
            throw new Error(`HTTP ${response.status}`);
        }

        const reader  = response.body.getReader();
        const decoder = new TextDecoder();
        let   buffer  = '';

        // Remove thinking dots once first chunk arrives
        let firstChunk = true;

        while (true) {
            const { done, value } = await reader.read();
            if (done) break;

            buffer += decoder.decode(value, { stream: true });
            const lines = buffer.split('\n');
            buffer = lines.pop();

            for (const line of lines) {
                if (!line.startsWith('data: ')) continue;
                const raw = line.slice(6).trim();
                if (!raw) continue;

                try {
                    const data = JSON.parse(raw);

                    if (data.content !== undefined) {
                        if (firstChunk) {
                            contentEl.innerHTML = '';
                            firstChunk = false;
                        }
                        fullText += data.content;
                        // Show raw text during streaming (render markdown on done)
                        contentEl.textContent = fullText;
                        scrollToBottom();
                    }

                    if (data.done) {
                        sourcesData = data.sources || [];
                        // Render final markdown
                        contentEl.innerHTML = marked.parse(fullText);
                        contentEl.querySelectorAll('pre code').forEach(b => hljs.highlightElement(b));

                        // Update meta line
                        let meta = selectedModel;
                        if (data.ms)          meta += ` · ${(data.ms/1000).toFixed(1)}s`;
                        if (data.token_count) meta += ` · ${data.token_count} tokens`;
                        metaEl.textContent = meta;

                        // Render sources
                        if (sourcesData.length > 0) {
                            appendSources(wrapper, sourcesData);
                        }

                        // Update sidebar conversation title on first message
                        if (firstMessage) {
                            firstMessage = false;
                            const title = message.length > 60 ? message.slice(0, 60) + '…' : message;
                            document.getElementById('conv-title').textContent = title;
                        }
                    }
                } catch (e) { /* ignore malformed chunks */ }
            }
        }

    } catch (err) {
        contentEl.innerHTML = `<span style="color:#ef4444;">
            <i class="bi bi-exclamation-circle me-1"></i>
            Error: ${err.message}. Check that Ollama is running.
        </span>`;
    } finally {
        setStreaming(false);
        scrollToBottom();
    }
}

// ── DOM helpers ───────────────────────────────────────────────────────────────
function appendUserMessage(text) {
    const area = document.getElementById('messagesArea');
    const div  = document.createElement('div');
    div.className = 'ai-message-wrapper';
    div.innerHTML  = `<div class="msg-user"><div class="bubble">${escHtml(text)}</div></div>`;
    area.appendChild(div);
    scrollToBottom();
}

function appendAssistantMessage() {
    const area    = document.getElementById('messagesArea');
    const wrapper = document.createElement('div');
    wrapper.className = 'ai-message-wrapper';
    wrapper.innerHTML = `
        <div class="msg-assistant">
            <div class="ai-avatar">AI</div>
            <div>
                <div class="bubble">
                    <div class="msg-content">
                        <div class="thinking-dots">
                            <span></span><span></span><span></span>
                        </div>
                    </div>
                </div>
                <div class="ai-msg-meta" style="font-size:0.68rem;color:#9ca3af;margin-top:4px;"></div>
            </div>
        </div>`;
    area.appendChild(wrapper);
    scrollToBottom();

    return {
        wrapper,
        contentEl: wrapper.querySelector('.msg-content'),
        metaEl:    wrapper.querySelector('.ai-msg-meta'),
    };
}

function appendSources(wrapper, sources) {
    const metaEl  = wrapper.querySelector('.ai-msg-meta');
    const srcHtml = sources.map(s => {
        const icon = s.type === 'document' ? 'file-earmark'
                   : s.type === 'project'  ? 'folder'
                   : 'database';
        return `<span class="source-chip"><i class="bi bi-${icon}"></i>${escHtml(s.label)}</span>`;
    }).join('');

    const sourcesDiv = document.createElement('div');
    sourcesDiv.className = 'sources-bar';
    sourcesDiv.innerHTML = `
        <button class="sources-toggle" onclick="toggleSources(this)">
            <i class="bi bi-database me-1"></i>${sources.length} source${sources.length !== 1 ? 's' : ''} used
            <i class="bi bi-chevron-down"></i>
        </button>
        <div class="sources-list mt-1">${srcHtml}</div>`;

    metaEl.parentNode.insertBefore(sourcesDiv, metaEl);
}

function toggleSources(btn) {
    const list = btn.nextElementSibling;
    const icon = btn.querySelector('.bi-chevron-down, .bi-chevron-up');
    if (list.style.display === 'none' || list.style.display === '') {
        list.style.display = 'flex';
        icon.className = icon.className.replace('chevron-down', 'chevron-up');
    } else {
        list.style.display = 'none';
        icon.className = icon.className.replace('chevron-up', 'chevron-down');
    }
}

function setStreaming(state) {
    isStreaming = state;
    document.getElementById('sendBtn').disabled = state;
    document.getElementById('streamingStatus').style.display = state ? 'flex' : 'none';
    document.getElementById('chatInput').placeholder = state
        ? 'Waiting for response…'
        : 'Ask about any project, proposal, invoice, team member, or document…';
}

function scrollToBottom() {
    const area = document.getElementById('messagesArea');
    area.scrollTop = area.scrollHeight;
}

function escHtml(str) {
    return str
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/\n/g, '<br>');
}
</script>
@endpush
