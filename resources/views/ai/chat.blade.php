@extends('layouts.app')

@section('title', 'Kore AI')

@push('head')
<script src="https://cdn.jsdelivr.net/npm/marked@9.1.6/marked.min.js"></script>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/gh/highlightjs/cdn-release@11.9.0/build/styles/github-dark.min.css">
<script src="https://cdn.jsdelivr.net/gh/highlightjs/cdn-release@11.9.0/build/highlight.min.js"></script>

<style>
/* ── Reset & Layout ─────────────────────────────────────────────────────── */
.kore-ai-layout {
    display: flex;
    height: calc(100vh - 57px); /* subtract top nav height */
    overflow: hidden;
    margin: -24px;
    background: #f8f9fb;
}

/* ── Sidebar ────────────────────────────────────────────────────────────── */
.ai-sidebar {
    width: 256px;
    min-width: 256px;
    background: #1a1d23;
    display: flex;
    flex-direction: column;
    overflow: hidden;
    border-right: 1px solid #2a2d35;
}

.ai-sidebar-top {
    padding: 14px 12px 10px;
    border-bottom: 1px solid #2a2d35;
}

.ai-new-btn {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 7px;
    width: 100%;
    padding: 9px 14px;
    background: #2563eb;
    color: #fff;
    border: none;
    border-radius: 8px;
    font-size: 0.82rem;
    font-weight: 600;
    text-decoration: none;
    cursor: pointer;
    transition: background 0.15s;
}
.ai-new-btn:hover { background: #1d4ed8; color: #fff; text-decoration: none; }
.ai-new-btn i { font-size: 0.8rem; }

.ai-conv-list {
    flex: 1;
    overflow-y: auto;
    padding: 8px 8px;
}
.ai-conv-list::-webkit-scrollbar { width: 3px; }
.ai-conv-list::-webkit-scrollbar-thumb { background: #3a3d47; border-radius: 3px; }

.ai-conv-section-label {
    font-size: 0.65rem;
    font-weight: 600;
    color: #4b5563;
    letter-spacing: 0.08em;
    text-transform: uppercase;
    padding: 10px 8px 5px;
}

.ai-conv-item {
    display: flex;
    align-items: center;
    padding: 8px 10px;
    border-radius: 7px;
    margin-bottom: 1px;
    cursor: pointer;
    text-decoration: none;
    color: #9ca3af;
    transition: background 0.12s, color 0.12s;
    gap: 9px;
    position: relative;
    group: true;
}
.ai-conv-item:hover { background: #252830; color: #e5e7eb; text-decoration: none; }
.ai-conv-item.active { background: #1e2940; color: #93b4fd; }
.ai-conv-item.active .ai-conv-title { color: #93b4fd; }

.ai-conv-icon {
    width: 28px;
    height: 28px;
    border-radius: 7px;
    background: #252830;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
    font-size: 0.7rem;
    color: #6b7280;
}
.ai-conv-item.active .ai-conv-icon { background: #1e3460; color: #60a5fa; }

.ai-conv-info { flex: 1; min-width: 0; }
.ai-conv-title {
    font-size: 0.78rem;
    font-weight: 500;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
    color: #d1d5db;
    display: block;
}
.ai-conv-meta {
    font-size: 0.68rem;
    color: #4b5563;
    margin-top: 1px;
    display: flex;
    gap: 5px;
}

.ai-conv-delete {
    opacity: 0;
    background: none;
    border: none;
    color: #6b7280;
    padding: 3px 5px;
    border-radius: 5px;
    cursor: pointer;
    font-size: 0.72rem;
    flex-shrink: 0;
    transition: opacity 0.12s, color 0.12s, background 0.12s;
}
.ai-conv-item:hover .ai-conv-delete { opacity: 1; }
.ai-conv-delete:hover { color: #f87171; background: #2d1f1f; }

.ai-sidebar-footer {
    padding: 10px 12px;
    border-top: 1px solid #2a2d35;
    font-size: 0.68rem;
    color: #4b5563;
    display: flex;
    align-items: center;
    gap: 6px;
}
.ai-status-dot {
    width: 6px;
    height: 6px;
    border-radius: 50%;
    background: #22c55e;
    flex-shrink: 0;
}
.ai-status-dot.offline { background: #ef4444; }

/* ── Main area ──────────────────────────────────────────────────────────── */
.ai-main {
    flex: 1;
    display: flex;
    flex-direction: column;
    overflow: hidden;
    min-width: 0;
}

/* ── Header ─────────────────────────────────────────────────────────────── */
.ai-header {
    background: #fff;
    border-bottom: 1px solid #e5e7eb;
    padding: 0 20px;
    display: flex;
    align-items: center;
    gap: 10px;
    height: 56px;
    flex-shrink: 0;
}

.ai-header-icon {
    width: 32px;
    height: 32px;
    border-radius: 9px;
    background: #1a1d23;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 0.7rem;
    font-weight: 800;
    color: #4c8bf5;
    letter-spacing: -0.5px;
    flex-shrink: 0;
}

.ai-header-title {
    font-weight: 600;
    font-size: 0.88rem;
    color: #111;
    flex: 1;
    min-width: 0;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}
.ai-header-title .context-badge {
    display: inline-flex;
    align-items: center;
    gap: 4px;
    background: #f3f4f6;
    border: 1px solid #e5e7eb;
    border-radius: 5px;
    padding: 2px 7px;
    font-size: 0.68rem;
    color: #6b7280;
    font-weight: 500;
    margin-left: 8px;
    vertical-align: middle;
}

/* Project selector */
.ai-project-btn {
    display: inline-flex;
    align-items: center;
    gap: 5px;
    padding: 5px 11px;
    background: #f9fafb;
    border: 1px solid #e5e7eb;
    border-radius: 7px;
    font-size: 0.75rem;
    color: #374151;
    cursor: pointer;
    transition: border-color 0.15s, background 0.15s;
    white-space: nowrap;
}
.ai-project-btn:hover { border-color: #d1d5db; background: #f3f4f6; }
.ai-project-btn i { font-size: 0.7rem; color: #9ca3af; }

/* Model selector pill */
.model-pill {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 5px 11px;
    background: #1a1d23;
    border: none;
    border-radius: 7px;
    font-size: 0.75rem;
    color: #a9b0be;
    cursor: pointer;
    transition: background 0.15s;
    white-space: nowrap;
    position: relative;
}
.model-pill:hover { background: #252830; color: #e5e7eb; }
.model-pill i.bi-cpu { color: #4c8bf5; font-size: 0.72rem; }
.model-pill i.bi-chevron-down { font-size: 0.6rem; color: #6b7280; }

.model-dropdown {
    position: absolute;
    top: calc(100% + 6px);
    right: 0;
    background: #fff;
    border: 1px solid #e5e7eb;
    border-radius: 10px;
    box-shadow: 0 8px 32px rgba(0,0,0,0.12), 0 2px 8px rgba(0,0,0,0.06);
    min-width: 230px;
    z-index: 1000;
    display: none;
    overflow: hidden;
}
.model-dropdown.open { display: block; }

.model-dropdown-header {
    padding: 10px 14px 7px;
    font-size: 0.65rem;
    font-weight: 700;
    color: #9ca3af;
    letter-spacing: 0.08em;
    text-transform: uppercase;
    border-bottom: 1px solid #f3f4f6;
}

.model-option {
    padding: 9px 14px;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 10px;
    transition: background 0.1s;
}
.model-option:hover { background: #f9fafb; }
.model-option.selected { background: #eff6ff; }
.model-name { font-size: 0.82rem; font-weight: 600; color: #111; }
.model-meta { font-size: 0.7rem; color: #9ca3af; margin-top: 1px; }
.model-check { color: #2563eb; font-size: 0.85rem; display: none; }
.model-option.selected .model-check { display: block; }

.model-dropdown-footer {
    padding: 8px 14px;
    border-top: 1px solid #f3f4f6;
}
.model-dropdown-footer a {
    font-size: 0.7rem;
    color: #9ca3af;
    text-decoration: none;
    display: inline-flex;
    align-items: center;
    gap: 4px;
}
.model-dropdown-footer a:hover { color: #4b5563; }

/* ── Messages ────────────────────────────────────────────────────────────── */
.ai-messages {
    flex: 1;
    overflow-y: auto;
    padding: 28px 0 16px;
    scroll-behavior: smooth;
}
.ai-messages::-webkit-scrollbar { width: 4px; }
.ai-messages::-webkit-scrollbar-track { background: transparent; }
.ai-messages::-webkit-scrollbar-thumb { background: #e5e7eb; border-radius: 4px; }

.ai-message-wrapper {
    max-width: 820px;
    margin: 0 auto 18px;
    padding: 0 28px;
}

/* User */
.msg-user {
    display: flex;
    justify-content: flex-end;
}
.msg-user .bubble {
    background: #2563eb;
    color: #fff;
    border-radius: 18px 18px 4px 18px;
    padding: 11px 16px;
    max-width: 68%;
    font-size: 0.875rem;
    line-height: 1.55;
    white-space: pre-wrap;
    box-shadow: 0 2px 8px rgba(37,99,235,0.25);
}

/* Assistant */
.msg-assistant {
    display: flex;
    gap: 12px;
    align-items: flex-start;
}
.ai-avatar {
    width: 30px;
    height: 30px;
    border-radius: 8px;
    background: #1a1d23;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
    font-size: 0.65rem;
    font-weight: 800;
    color: #4c8bf5;
    letter-spacing: -0.5px;
    margin-top: 2px;
}
.msg-assistant .bubble {
    background: #fff;
    border: 1px solid #e9eaec;
    border-radius: 4px 18px 18px 18px;
    padding: 14px 18px;
    max-width: 80%;
    font-size: 0.875rem;
    line-height: 1.65;
    box-shadow: 0 1px 4px rgba(0,0,0,0.05);
}

/* Markdown in bubble */
.msg-assistant .bubble h1,
.msg-assistant .bubble h2 { font-size: 1rem; font-weight: 700; margin: 14px 0 6px; }
.msg-assistant .bubble h3 { font-size: 0.9rem; font-weight: 600; margin: 12px 0 5px; }
.msg-assistant .bubble p  { margin-bottom: 8px; }
.msg-assistant .bubble p:last-child { margin-bottom: 0; }
.msg-assistant .bubble ul,
.msg-assistant .bubble ol { padding-left: 20px; margin-bottom: 8px; }
.msg-assistant .bubble li { margin-bottom: 3px; }
.msg-assistant .bubble code {
    background: #f1f5f9;
    border-radius: 4px;
    padding: 1px 5px;
    font-size: 0.8rem;
    font-family: 'Fira Code', ui-monospace, monospace;
    color: #0f172a;
}
.msg-assistant .bubble pre {
    background: #0f172a;
    border-radius: 8px;
    padding: 14px 16px;
    overflow-x: auto;
    margin: 10px 0;
}
.msg-assistant .bubble pre code { background: none; color: inherit; padding: 0; font-size: 0.8rem; }
.msg-assistant .bubble table { width: 100%; border-collapse: collapse; margin: 10px 0; font-size: 0.82rem; }
.msg-assistant .bubble th { background: #f8f9fb; padding: 7px 10px; text-align: left; font-weight: 600; border-bottom: 2px solid #e5e7eb; }
.msg-assistant .bubble td { padding: 6px 10px; border-bottom: 1px solid #f3f4f6; }
.msg-assistant .bubble strong { font-weight: 600; }
.msg-assistant .bubble blockquote {
    border-left: 3px solid #e5e7eb;
    padding-left: 12px;
    color: #6b7280;
    margin: 8px 0;
    font-style: italic;
}

/* Message meta */
.ai-msg-meta {
    display: flex;
    align-items: center;
    gap: 6px;
    font-size: 0.68rem;
    color: #c4c9d4;
    margin-top: 5px;
    padding-left: 2px;
}

/* Sources */
.sources-bar {
    margin-top: 6px;
    padding-left: 2px;
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
    transition: color 0.12s;
}
.sources-toggle:hover { color: #4b5563; }
.sources-list { display: none; flex-wrap: wrap; gap: 5px; margin-top: 6px; }
.source-chip {
    background: #f3f4f6;
    border: 1px solid #e5e7eb;
    border-radius: 20px;
    padding: 3px 10px;
    font-size: 0.68rem;
    color: #4b5563;
    display: inline-flex;
    align-items: center;
    gap: 5px;
}

/* Thinking animation */
.thinking-dots {
    display: inline-flex;
    gap: 5px;
    align-items: center;
    padding: 4px 2px;
}
.thinking-dots span {
    width: 7px; height: 7px;
    border-radius: 50%;
    background: #d1d5db;
    animation: thinking 1.3s infinite;
}
.thinking-dots span:nth-child(2) { animation-delay: 0.2s; }
.thinking-dots span:nth-child(3) { animation-delay: 0.4s; }
@keyframes thinking {
    0%, 60%, 100% { transform: translateY(0); opacity: 0.35; }
    30%            { transform: translateY(-5px); opacity: 1; }
}

/* ── Input area ──────────────────────────────────────────────────────────── */
.ai-input-area {
    background: #fff;
    border-top: 1px solid #e9eaec;
    padding: 14px 28px 16px;
    flex-shrink: 0;
}
.ai-input-wrap {
    max-width: 820px;
    margin: 0 auto;
}
.ai-input-box {
    display: flex;
    align-items: flex-end;
    gap: 0;
    background: #f8f9fb;
    border: 1.5px solid #e5e7eb;
    border-radius: 14px;
    padding: 10px 10px 10px 16px;
    transition: border-color 0.2s, box-shadow 0.2s;
}
.ai-input-box:focus-within {
    border-color: #2563eb;
    box-shadow: 0 0 0 3px rgba(37,99,235,0.08);
    background: #fff;
}
.ai-textarea {
    flex: 1;
    border: none;
    background: transparent;
    padding: 0;
    font-size: 0.875rem;
    line-height: 1.55;
    resize: none;
    outline: none;
    font-family: inherit;
    min-height: 24px;
    max-height: 180px;
    overflow-y: auto;
    color: #111;
}
.ai-textarea::placeholder { color: #9ca3af; }
.ai-send-btn {
    width: 36px;
    height: 36px;
    border-radius: 9px;
    background: #2563eb;
    border: none;
    color: #fff;
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    flex-shrink: 0;
    transition: background 0.15s, transform 0.1s;
    align-self: flex-end;
}
.ai-send-btn:hover { background: #1d4ed8; transform: scale(1.03); }
.ai-send-btn:disabled { background: #d1d5db; cursor: not-allowed; transform: none; }
.ai-send-btn i { font-size: 0.8rem; }

.ai-input-footer {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-top: 7px;
    font-size: 0.7rem;
    color: #c4c9d4;
}
.ai-streaming-badge {
    display: none;
    align-items: center;
    gap: 5px;
    color: #2563eb;
    font-size: 0.7rem;
    font-weight: 500;
}
.pulse-dot {
    width: 6px; height: 6px;
    border-radius: 50%;
    background: #2563eb;
    animation: thinking 1.3s infinite;
}

/* ── Offline banner ──────────────────────────────────────────────────────── */
.ai-offline-banner {
    background: #fff7ed;
    border: 1px solid #fed7aa;
    color: #9a3412;
    border-radius: 9px;
    padding: 9px 14px;
    font-size: 0.78rem;
    margin-bottom: 10px;
    display: flex;
    align-items: center;
    gap: 8px;
}
.ai-offline-banner i { color: #f97316; }
.ai-offline-banner code { background: #fef3c7; padding: 1px 5px; border-radius: 3px; font-size: 0.75rem; }

/* ── Welcome screen ──────────────────────────────────────────────────────── */
.ai-welcome {
    max-width: 680px;
    margin: 40px auto 0;
    padding: 0 28px;
}

.ai-welcome-hero {
    display: flex;
    flex-direction: column;
    align-items: center;
    text-align: center;
    margin-bottom: 32px;
}
.ai-welcome-avatar {
    width: 52px;
    height: 52px;
    border-radius: 14px;
    background: #1a1d23;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1rem;
    font-weight: 800;
    color: #4c8bf5;
    letter-spacing: -1px;
    margin-bottom: 14px;
    box-shadow: 0 4px 16px rgba(0,0,0,0.12);
}
.ai-welcome-hero h2 {
    font-size: 1.4rem;
    font-weight: 700;
    color: #111;
    margin-bottom: 7px;
}
.ai-welcome-hero p {
    font-size: 0.875rem;
    color: #6b7280;
    line-height: 1.6;
    max-width: 460px;
}

.suggestion-grid {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 10px;
}
.suggestion-card {
    background: #fff;
    border: 1px solid #e9eaec;
    border-radius: 11px;
    padding: 14px 16px;
    text-align: left;
    cursor: pointer;
    transition: border-color 0.15s, box-shadow 0.15s, transform 0.1s;
    display: flex;
    gap: 11px;
    align-items: flex-start;
}
.suggestion-card:hover {
    border-color: #93c5fd;
    box-shadow: 0 4px 16px rgba(37,99,235,0.08);
    transform: translateY(-1px);
}
.suggestion-icon {
    width: 32px;
    height: 32px;
    border-radius: 8px;
    background: #eff6ff;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
    color: #2563eb;
    font-size: 0.8rem;
}
.suggestion-body { flex: 1; min-width: 0; }
.suggestion-body strong {
    display: block;
    font-size: 0.8rem;
    font-weight: 600;
    color: #111;
    margin-bottom: 3px;
}
.suggestion-body span {
    font-size: 0.76rem;
    color: #6b7280;
    line-height: 1.4;
}
</style>
@endpush

@section('content')
<div class="kore-ai-layout">

    {{-- ── Sidebar ─────────────────────────────────────────────────────────── --}}
    <div class="ai-sidebar">

        <div class="ai-sidebar-top">
            <a href="{{ route('ai.new') }}" class="ai-new-btn">
                <i class="bi bi-plus-lg"></i> New conversation
            </a>
        </div>

        <div class="ai-conv-list">
            @if($allConversations->isNotEmpty())
            <div class="ai-conv-section-label">Recent</div>
            @endif

            @forelse($allConversations as $conv)
            <a href="{{ route('ai.show', $conv) }}"
               class="ai-conv-item {{ $conv->id === $conversation->id ? 'active' : '' }}">
                <div class="ai-conv-icon">
                    <i class="bi bi-chat"></i>
                </div>
                <div class="ai-conv-info">
                    <span class="ai-conv-title">
                        {{ $conv->title ?? 'New conversation' }}
                    </span>
                    <div class="ai-conv-meta">
                        <span>{{ $conv->model }}</span>
                        <span>·</span>
                        <span>{{ $conv->updated_at->diffForHumans(short: true) }}</span>
                    </div>
                </div>
                @if($conv->id === $conversation->id)
                <form action="{{ route('ai.destroy', $conv) }}" method="POST"
                      onsubmit="return confirm('Delete this conversation?')" style="display:contents;">
                    @csrf @method('DELETE')
                    <button type="submit" class="ai-conv-delete" title="Delete" onclick="event.preventDefault();event.stopPropagation();this.closest('form').submit();">
                        <i class="bi bi-trash"></i>
                    </button>
                </form>
                @endif
            </a>
            @empty
            <div style="padding:20px 10px;font-size:0.78rem;color:#4b5563;text-align:center;line-height:1.5;">
                <i class="bi bi-chat-dots" style="font-size:1.2rem;display:block;margin-bottom:6px;color:#374151;"></i>
                No conversations yet.<br>Start a new one above.
            </div>
            @endforelse
        </div>

        <div class="ai-sidebar-footer">
            <div class="ai-status-dot" id="sidebarStatusDot"></div>
            <span id="sidebarStatusText">Connecting…</span>
        </div>
    </div>

    {{-- ── Main ────────────────────────────────────────────────────────────── --}}
    <div class="ai-main">

        {{-- Header --}}
        <div class="ai-header">
            <div class="ai-header-icon">AI</div>
            <div class="ai-header-title">
                <span id="conv-title">{{ $conversation->title ?? 'Kore AI' }}</span>
                @if($conversation->contextProject)
                <span class="context-badge">
                    <i class="bi bi-folder"></i>{{ $conversation->contextProject->project_number }}
                </span>
                @endif
            </div>

            {{-- Project scope --}}
            <div class="dropdown">
                <button class="ai-project-btn" type="button" data-bs-toggle="dropdown">
                    <i class="bi bi-folder"></i>
                    {{ $conversation->contextProject?->project_number ?? 'All projects' }}
                    <i class="bi bi-chevron-down" style="font-size:0.6rem;"></i>
                </button>
                <ul class="dropdown-menu dropdown-menu-end" style="font-size:0.82rem;min-width:200px;">
                    <li>
                        <button class="dropdown-item" onclick="setProjectScope(null)">
                            <i class="bi bi-globe2 me-2 text-muted"></i>All projects
                        </button>
                    </li>
                    <li><hr class="dropdown-divider"></li>
                    @foreach($projects as $p)
                    <li>
                        <button class="dropdown-item d-flex align-items-center gap-2" onclick="setProjectScope({{ $p->id }})">
                            <i class="bi bi-folder text-muted"></i>
                            <span>
                                <strong>{{ $p->project_number }}</strong>
                                <span class="text-muted ms-1">{{ Str::limit($p->title, 28) }}</span>
                            </span>
                        </button>
                    </li>
                    @endforeach
                </ul>
            </div>

            {{-- Model selector --}}
            <div style="position:relative;" id="modelSelector">
                <button class="model-pill" onclick="toggleModelDropdown(event)">
                    <i class="bi bi-cpu"></i>
                    <span id="selectedModelLabel">{{ $conversation->model }}</span>
                    <i class="bi bi-chevron-down"></i>
                </button>
                <div class="model-dropdown" id="modelDropdown">
                    <div class="model-dropdown-header">Available models</div>
                    <div id="modelList">
                        <div style="padding:12px 14px;font-size:0.8rem;color:#9ca3af;">Loading…</div>
                    </div>
                    <div class="model-dropdown-footer">
                        <a href="https://ollama.com/library" target="_blank">
                            <i class="bi bi-box-arrow-up-right"></i> Browse Ollama library
                        </a>
                    </div>
                </div>
            </div>
        </div>

        {{-- Messages --}}
        <div class="ai-messages" id="messagesArea">
            @if($messages->isEmpty())
            <div class="ai-welcome" id="welcomeScreen">
                <div class="ai-welcome-hero">
                    <div class="ai-welcome-avatar">AI</div>
                    <h2>Kore AI</h2>
                    <p>Ask anything about your projects, proposals, team, or finances.
                       I have live access to all data in the system.</p>
                </div>

                <div class="suggestion-grid">
                    <div class="suggestion-card" onclick="useSuggestion(this)">
                        <div class="suggestion-icon"><i class="bi bi-bar-chart-fill"></i></div>
                        <div class="suggestion-body">
                            <strong>Portfolio health check</strong>
                            <span>Show me all active projects with budget risk flags</span>
                        </div>
                    </div>
                    <div class="suggestion-card" onclick="useSuggestion(this)">
                        <div class="suggestion-icon"><i class="bi bi-receipt"></i></div>
                        <div class="suggestion-body">
                            <strong>Outstanding invoices</strong>
                            <span>What invoices are overdue and how much is outstanding?</span>
                        </div>
                    </div>
                    <div class="suggestion-card" onclick="useSuggestion(this)">
                        <div class="suggestion-icon"><i class="bi bi-check2-square"></i></div>
                        <div class="suggestion-body">
                            <strong>My tasks</strong>
                            <span>What are my open tasks and upcoming deadlines?</span>
                        </div>
                    </div>
                    <div class="suggestion-card" onclick="useSuggestion(this)">
                        <div class="suggestion-icon"><i class="bi bi-graph-up-arrow"></i></div>
                        <div class="suggestion-body">
                            <strong>Proposal pipeline</strong>
                            <span>Summarise all open proposals and their total estimated fees</span>
                        </div>
                    </div>
                    <div class="suggestion-card" onclick="useSuggestion(this)">
                        <div class="suggestion-icon"><i class="bi bi-people-fill"></i></div>
                        <div class="suggestion-body">
                            <strong>Team utilization</strong>
                            <span>Who has the most hours logged this month and on which projects?</span>
                        </div>
                    </div>
                    <div class="suggestion-card" onclick="useSuggestion(this)">
                        <div class="suggestion-icon"><i class="bi bi-search"></i></div>
                        <div class="suggestion-body">
                            <strong>Document search</strong>
                            <span>Search project documents for structural engineering comments</span>
                        </div>
                    </div>
                </div>
            </div>
            @else
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
                        <div style="flex:1;min-width:0;">
                            <div class="bubble">
                                <div class="msg-content">{!! nl2br(e($msg->content)) !!}</div>
                            </div>
                            @if($msg->sources && count($msg->sources) > 0)
                            <div class="sources-bar">
                                <button class="sources-toggle" onclick="toggleSources(this)">
                                    <i class="bi bi-database me-1"></i>
                                    {{ count($msg->sources) }} source{{ count($msg->sources) !== 1 ? 's' : '' }} used
                                    <i class="bi bi-chevron-down"></i>
                                </button>
                                <div class="sources-list">
                                    @foreach($msg->sources as $source)
                                    <span class="source-chip">
                                        <i class="bi bi-{{ $source['type'] === 'document' ? 'file-earmark' : ($source['type'] === 'project' ? 'folder' : 'database') }}"></i>
                                        {{ $source['label'] }}
                                    </span>
                                    @endforeach
                                </div>
                            </div>
                            @endif
                            <div class="ai-msg-meta">
                                <span>{{ $msg->model }}</span>
                                @if($msg->processing_time_ms) <span>·</span><span>{{ round($msg->processing_time_ms/1000,1) }}s</span> @endif
                                @if($msg->token_count) <span>·</span><span>{{ $msg->token_count }} tokens</span> @endif
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
            <div class="ai-input-wrap">
                <div id="ollamaOfflineBanner" class="ai-offline-banner d-none">
                    <i class="bi bi-exclamation-triangle"></i>
                    <span><strong>Ollama is not running.</strong> Start it with <code>ollama serve</code> then refresh. Model: <span id="offlineModel"></span></span>
                </div>
                <div class="ai-input-box">
                    <textarea class="ai-textarea" id="chatInput"
                              placeholder="Ask about any project, proposal, invoice, or team member…"
                              rows="1"
                              onkeydown="handleInputKeydown(event)"
                              oninput="autoResize(this)"></textarea>
                    <button class="ai-send-btn" id="sendBtn" onclick="sendMessage()" title="Send (Enter)">
                        <i class="bi bi-send-fill"></i>
                    </button>
                </div>
                <div class="ai-input-footer">
                    <span>Enter to send &middot; Shift+Enter for new line</span>
                    <span class="ai-streaming-badge" id="streamingStatus">
                        <span class="pulse-dot"></span> Generating…
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

// ── Markdown ──────────────────────────────────────────────────────────────────
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

// ── Init ──────────────────────────────────────────────────────────────────────
document.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('.msg-assistant .msg-content').forEach(el => {
        const raw = el.innerText;
        el.innerHTML = marked.parse(raw);
        el.querySelectorAll('pre code').forEach(b => hljs.highlightElement(b));
    });

    loadModels();
    scrollToBottom();
    document.getElementById('chatInput').focus();
});

// ── Model selector ────────────────────────────────────────────────────────────
async function loadModels() {
    const dot  = document.getElementById('sidebarStatusDot');
    const text = document.getElementById('sidebarStatusText');
    try {
        const res  = await fetch(MODELS_URL);
        const data = await res.json();

        if (!data.available) {
            dot.classList.add('offline');
            text.textContent = 'Ollama offline';
            document.getElementById('ollamaOfflineBanner').classList.remove('d-none');
            document.getElementById('offlineModel').textContent = selectedModel;
        } else {
            dot.classList.remove('offline');
            text.textContent = 'Ollama connected';
        }

        const list = document.getElementById('modelList');
        if (!data.enabled || data.enabled.length === 0) {
            list.innerHTML = `<div style="padding:12px 14px;font-size:0.8rem;color:#ef4444;">
                No models installed. Run: <code>ollama pull llama3</code></div>`;
            return;
        }

        list.innerHTML = data.enabled.map(name => {
            const inst  = data.installed?.find(m => m.name.startsWith(name) || m.name === name);
            const size  = inst ? `${inst.size_gb}GB` : '';
            const param = inst?.params ?? '';
            const meta  = [param, size].filter(Boolean).join(' · ');
            return `<div class="model-option ${name === selectedModel ? 'selected' : ''}"
                        onclick="selectModel('${name}', event)">
                <div>
                    <div class="model-name">${name}</div>
                    ${meta ? `<div class="model-meta">${meta}</div>` : ''}
                </div>
                <i class="bi bi-check2 model-check"></i>
            </div>`;
        }).join('');

    } catch (e) {
        dot.classList.add('offline');
        text.textContent = 'Ollama offline';
        document.getElementById('modelList').innerHTML =
            '<div style="padding:12px 14px;font-size:0.8rem;color:#9ca3af;">Could not reach Ollama.</div>';
    }
}

function toggleModelDropdown(e) {
    e.stopPropagation();
    document.getElementById('modelDropdown').classList.toggle('open');
}

document.addEventListener('click', () => {
    document.getElementById('modelDropdown').classList.remove('open');
});

function selectModel(name, e) {
    selectedModel = name;
    document.getElementById('selectedModelLabel').textContent = name;
    document.querySelectorAll('.model-option').forEach(el => el.classList.remove('selected'));
    if (e && e.currentTarget) e.currentTarget.classList.add('selected');
    document.getElementById('modelDropdown').classList.remove('open');

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

// ── Input ─────────────────────────────────────────────────────────────────────
function handleInputKeydown(e) {
    if (e.key === 'Enter' && !e.shiftKey) {
        e.preventDefault();
        sendMessage();
    }
}

function autoResize(el) {
    el.style.height = 'auto';
    el.style.height = Math.min(el.scrollHeight, 180) + 'px';
}

function useSuggestion(card) {
    const text = card.querySelector('span').textContent.trim();
    const input = document.getElementById('chatInput');
    input.value = text;
    autoResize(input);
    sendMessage();
}

// ── Send / stream ─────────────────────────────────────────────────────────────
async function sendMessage() {
    if (isStreaming) return;

    const input   = document.getElementById('chatInput');
    const message = input.value.trim();
    if (!message) return;

    input.value = '';
    input.style.height = 'auto';

    const welcome = document.getElementById('welcomeScreen');
    if (welcome) welcome.remove();

    appendUserMessage(message);
    setStreaming(true);

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

        if (!response.ok) throw new Error(`HTTP ${response.status}`);

        const reader  = response.body.getReader();
        const decoder = new TextDecoder();
        let   buffer  = '';
        let   firstChunk = true;

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
                        if (firstChunk) { contentEl.innerHTML = ''; firstChunk = false; }
                        fullText += data.content;
                        contentEl.textContent = fullText;
                        scrollToBottom();
                    }

                    if (data.done) {
                        sourcesData = data.sources || [];
                        contentEl.innerHTML = marked.parse(fullText);
                        contentEl.querySelectorAll('pre code').forEach(b => hljs.highlightElement(b));

                        let meta = selectedModel;
                        if (data.ms)          meta += ` · ${(data.ms/1000).toFixed(1)}s`;
                        if (data.token_count) meta += ` · ${data.token_count} tokens`;
                        metaEl.textContent = meta;

                        if (sourcesData.length > 0) appendSources(wrapper, sourcesData);

                        if (firstMessage) {
                            firstMessage = false;
                            const title = message.length > 55 ? message.slice(0, 55) + '…' : message;
                            document.getElementById('conv-title').textContent = title;
                        }
                    }
                } catch (e) { /* ignore */ }
            }
        }

    } catch (err) {
        contentEl.innerHTML = `<span style="color:#ef4444;display:flex;align-items:center;gap:6px;">
            <i class="bi bi-exclamation-circle"></i>
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
    div.innerHTML = `<div class="msg-user"><div class="bubble">${escHtml(text)}</div></div>`;
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
            <div style="flex:1;min-width:0;">
                <div class="bubble">
                    <div class="msg-content">
                        <div class="thinking-dots"><span></span><span></span><span></span></div>
                    </div>
                </div>
                <div class="ai-msg-meta"></div>
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
    const metaEl = wrapper.querySelector('.ai-msg-meta');
    const chips  = sources.map(s => {
        const icon = s.type === 'document' ? 'file-earmark'
                   : s.type === 'project'  ? 'folder' : 'database';
        return `<span class="source-chip"><i class="bi bi-${icon}"></i>${escHtml(s.label)}</span>`;
    }).join('');

    const div = document.createElement('div');
    div.className = 'sources-bar';
    div.innerHTML = `
        <button class="sources-toggle" onclick="toggleSources(this)">
            <i class="bi bi-database me-1"></i>${sources.length} source${sources.length !== 1 ? 's' : ''} used
            <i class="bi bi-chevron-down"></i>
        </button>
        <div class="sources-list">${chips}</div>`;
    metaEl.parentNode.insertBefore(div, metaEl);
}

function toggleSources(btn) {
    const list = btn.nextElementSibling;
    const icon = btn.querySelector('[class*="chevron"]');
    const open = list.style.display === 'flex';
    list.style.display = open ? 'none' : 'flex';
    if (icon) icon.className = icon.className.replace(
        open ? 'chevron-up' : 'chevron-down',
        open ? 'chevron-down' : 'chevron-up'
    );
}

function setStreaming(state) {
    isStreaming = state;
    document.getElementById('sendBtn').disabled = state;
    const badge = document.getElementById('streamingStatus');
    badge.style.display = state ? 'flex' : 'none';
    document.getElementById('chatInput').placeholder = state
        ? 'Waiting for response…'
        : 'Ask about any project, proposal, invoice, or team member…';
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
