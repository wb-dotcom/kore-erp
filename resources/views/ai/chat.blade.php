@extends('layouts.app')

@section('title', 'Kore AI')

@push('styles')
<link rel="stylesheet" href="https://cdn.jsdelivr.net/gh/highlightjs/cdn-release@11.9.0/build/styles/github.min.css">
<style>
/* ── Override page chrome for full-height AI layout ───────────────────── */
#page-content { padding: 0 !important; }
footer { display: none !important; }

/* ── Root layout ─────────────────────────────────────────────────────────*/
.kai-wrap {
    display: flex;
    height: calc(100vh - 64px);
    overflow: hidden;
    background: #fff;
}

/* ════════════════════════════════════════════════════════════════════════
   LEFT SIDEBAR
   ════════════════════════════════════════════════════════════════════════ */
.kai-sidebar {
    width: 260px;
    min-width: 260px;
    background: #f9fafb;
    border-right: 1px solid #e5e7eb;
    display: flex;
    flex-direction: column;
    overflow: hidden;
}

/* New chat button */
.kai-new {
    margin: 12px;
    display: flex;
    align-items: center;
    gap: 8px;
    padding: 9px 14px;
    background: #1a1d23;
    color: #fff;
    border: none;
    border-radius: 9px;
    font-size: 0.8rem;
    font-weight: 600;
    text-decoration: none;
    cursor: pointer;
    transition: background .15s;
}
.kai-new:hover { background: #2d3139; color: #fff; text-decoration: none; }
.kai-new i { font-size: 0.75rem; }

/* Search conversations */
.kai-search {
    margin: 0 12px 8px;
    position: relative;
}
.kai-search input {
    width: 100%;
    background: #fff;
    border: 1px solid #e5e7eb;
    border-radius: 7px;
    padding: 7px 10px 7px 30px;
    font-size: 0.76rem;
    outline: none;
    font-family: inherit;
    color: #374151;
}
.kai-search input:focus { border-color: #d1d5db; }
.kai-search i {
    position: absolute;
    left: 9px; top: 50%;
    transform: translateY(-50%);
    color: #9ca3af; font-size: 0.72rem;
}

/* Conversation list */
.kai-conv-list {
    flex: 1;
    overflow-y: auto;
    padding: 4px 8px 8px;
}
.kai-conv-list::-webkit-scrollbar { width: 3px; }
.kai-conv-list::-webkit-scrollbar-thumb { background: #d1d5db; border-radius: 3px; }

.kai-conv-group-label {
    font-size: 0.63rem;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: .07em;
    color: #9ca3af;
    padding: 8px 6px 4px;
}

.kai-conv-item {
    display: flex;
    align-items: center;
    gap: 8px;
    padding: 8px 10px;
    border-radius: 8px;
    cursor: pointer;
    text-decoration: none;
    color: #374151;
    transition: background .12s;
    position: relative;
    margin-bottom: 1px;
}
.kai-conv-item:hover { background: #f0f1f3; color: #111; text-decoration: none; }
.kai-conv-item.active { background: #eff6ff; color: #1d4ed8; }

.kai-conv-icon {
    width: 26px; height: 26px;
    border-radius: 6px;
    background: #e9eaec;
    display: flex; align-items: center; justify-content: center;
    flex-shrink: 0;
    font-size: 0.65rem;
    color: #6b7280;
}
.kai-conv-item.active .kai-conv-icon { background: #dbeafe; color: #2563eb; }

.kai-conv-body { flex: 1; min-width: 0; }
.kai-conv-title {
    font-size: 0.78rem;
    font-weight: 500;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
    display: block;
    color: #1f2937;
}
.kai-conv-item.active .kai-conv-title { color: #1d4ed8; }
.kai-conv-time {
    font-size: 0.66rem;
    color: #9ca3af;
    margin-top: 1px;
    display: flex; gap: 4px;
}

/* Delete icon — appears on hover */
.kai-conv-del {
    opacity: 0;
    background: none; border: none;
    color: #9ca3af;
    padding: 3px 4px;
    border-radius: 5px;
    cursor: pointer;
    font-size: 0.7rem;
    transition: opacity .12s, color .12s, background .12s;
    flex-shrink: 0;
}
.kai-conv-item:hover .kai-conv-del { opacity: 1; }
.kai-conv-del:hover { color: #ef4444; background: #fee2e2; }

/* Sidebar footer */
.kai-sidebar-foot {
    padding: 10px 14px;
    border-top: 1px solid #e5e7eb;
    display: flex;
    align-items: center;
    gap: 7px;
    font-size: 0.7rem;
    color: #9ca3af;
}
.kai-ollama-dot {
    width: 7px; height: 7px;
    border-radius: 50%;
    background: #22c55e;
    flex-shrink: 0;
    box-shadow: 0 0 0 2px #dcfce7;
}
.kai-ollama-dot.off { background: #f87171; box-shadow: 0 0 0 2px #fee2e2; }

/* ════════════════════════════════════════════════════════════════════════
   MAIN PANEL
   ════════════════════════════════════════════════════════════════════════ */
.kai-main {
    flex: 1;
    display: flex;
    flex-direction: column;
    overflow: hidden;
    background: #fff;
    min-width: 0;
}

/* ── Top bar ─────────────────────────────────────────────────────────── */
.kai-topbar {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 0 20px;
    height: 54px;
    border-bottom: 1px solid #f0f1f3;
    background: #fff;
    flex-shrink: 0;
}

.kai-topbar-title {
    flex: 1;
    font-size: 0.85rem;
    font-weight: 600;
    color: #1f2937;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
    min-width: 0;
}
.kai-topbar-badge {
    display: inline-flex; align-items: center; gap: 4px;
    background: #f3f4f6; border: 1px solid #e5e7eb;
    border-radius: 5px; padding: 2px 8px;
    font-size: 0.67rem; color: #6b7280; font-weight: 500;
    margin-left: 8px; vertical-align: middle;
}

/* Toolbar buttons */
.kai-btn {
    display: inline-flex; align-items: center; gap: 5px;
    padding: 5px 11px;
    border: 1px solid #e5e7eb;
    border-radius: 7px;
    font-size: 0.75rem;
    color: #374151;
    background: #fff;
    cursor: pointer;
    transition: border-color .15s, background .15s;
    white-space: nowrap;
    font-family: inherit;
}
.kai-btn i { font-size: 0.7rem; color: #9ca3af; }
.kai-btn:hover { border-color: #d1d5db; background: #f9fafb; }

/* Model pill */
.kai-model-wrap { position: relative; }
.kai-model-btn {
    display: inline-flex; align-items: center; gap: 6px;
    padding: 5px 11px;
    background: #1a1d23;
    border: none;
    border-radius: 7px;
    font-size: 0.75rem;
    color: #c9cdd6;
    cursor: pointer;
    transition: background .15s;
    font-family: inherit;
}
.kai-model-btn:hover { background: #252830; color: #fff; }
.kai-model-btn i.bi-cpu { color: #4c8bf5; }
.kai-model-btn i.bi-chevron-down { font-size: 0.58rem; color: #6b7280; }

.kai-model-dd {
    position: absolute;
    top: calc(100% + 6px); right: 0;
    background: #fff;
    border: 1px solid #e5e7eb;
    border-radius: 10px;
    box-shadow: 0 8px 32px rgba(0,0,0,.1), 0 2px 8px rgba(0,0,0,.06);
    min-width: 220px;
    z-index: 2000;
    display: none;
    overflow: hidden;
}
.kai-model-dd.open { display: block; }

.kai-model-dd-hdr {
    padding: 9px 14px 7px;
    font-size: 0.63rem; font-weight: 700;
    color: #9ca3af; letter-spacing: .08em; text-transform: uppercase;
    border-bottom: 1px solid #f3f4f6;
}
.kai-model-opt {
    padding: 9px 14px;
    cursor: pointer;
    display: flex; align-items: center; justify-content: space-between; gap: 10px;
    transition: background .1s;
}
.kai-model-opt:hover { background: #f9fafb; }
.kai-model-opt.sel { background: #eff6ff; }
.kai-model-name { font-size: 0.82rem; font-weight: 600; color: #111; }
.kai-model-meta { font-size: 0.69rem; color: #9ca3af; }
.kai-model-check { color: #2563eb; display: none; }
.kai-model-opt.sel .kai-model-check { display: block; }
.kai-model-dd-ftr {
    padding: 8px 14px;
    border-top: 1px solid #f3f4f6;
}
.kai-model-dd-ftr a {
    font-size: 0.7rem; color: #9ca3af; text-decoration: none;
    display: inline-flex; align-items: center; gap: 4px;
}
.kai-model-dd-ftr a:hover { color: #4b5563; }

/* ── Messages ─────────────────────────────────────────────────────────── */
.kai-messages {
    flex: 1; overflow-y: auto;
    padding: 32px 0 20px;
    scroll-behavior: smooth;
}
.kai-messages::-webkit-scrollbar { width: 5px; }
.kai-messages::-webkit-scrollbar-track { background: transparent; }
.kai-messages::-webkit-scrollbar-thumb { background: #e5e7eb; border-radius: 5px; }

.kai-msg-row {
    max-width: 780px;
    margin: 0 auto 20px;
    padding: 0 32px;
}

/* User bubble */
.kai-user { display: flex; justify-content: flex-end; }
.kai-user-bubble {
    background: #2563eb;
    color: #fff;
    border-radius: 18px 18px 4px 18px;
    padding: 11px 16px;
    max-width: 65%;
    font-size: 0.875rem;
    line-height: 1.55;
    white-space: pre-wrap;
    box-shadow: 0 2px 10px rgba(37,99,235,.2);
}

/* AI message */
.kai-ai-row { display: flex; gap: 12px; align-items: flex-start; }
.kai-ai-avatar {
    width: 28px; height: 28px;
    border-radius: 7px;
    background: #1a1d23;
    display: flex; align-items: center; justify-content: center;
    flex-shrink: 0;
    font-size: 0.6rem; font-weight: 800;
    color: #4c8bf5; letter-spacing: -.5px;
    margin-top: 1px;
}
.kai-ai-body { flex: 1; min-width: 0; }
.kai-ai-bubble {
    font-size: 0.875rem;
    line-height: 1.7;
    color: #1f2937;
}

/* Markdown in AI bubble */
.kai-ai-bubble h1, .kai-ai-bubble h2 { font-size: 1rem; font-weight: 700; margin: 14px 0 6px; }
.kai-ai-bubble h3 { font-size: 0.9rem; font-weight: 600; margin: 12px 0 5px; }
.kai-ai-bubble p { margin-bottom: 8px; }
.kai-ai-bubble p:last-child { margin-bottom: 0; }
.kai-ai-bubble ul, .kai-ai-bubble ol { padding-left: 20px; margin-bottom: 8px; }
.kai-ai-bubble li { margin-bottom: 3px; }
.kai-ai-bubble code {
    background: #f1f5f9; border-radius: 4px;
    padding: 1px 5px; font-size: 0.79rem;
    font-family: ui-monospace, 'Cascadia Code', monospace;
    color: #1e293b;
}
.kai-ai-bubble pre {
    background: #0f172a;
    border-radius: 9px;
    padding: 14px 16px;
    overflow-x: auto;
    margin: 10px 0;
    font-size: 0.79rem;
}
.kai-ai-bubble pre code { background: none; color: #e2e8f0; padding: 0; font-size: inherit; }
.kai-ai-bubble table { width: 100%; border-collapse: collapse; margin: 10px 0; font-size: 0.82rem; }
.kai-ai-bubble th { background: #f8fafc; padding: 8px 10px; text-align: left; font-weight: 600; border-bottom: 2px solid #e2e8f0; }
.kai-ai-bubble td { padding: 7px 10px; border-bottom: 1px solid #f1f5f9; }
.kai-ai-bubble strong { font-weight: 600; }
.kai-ai-bubble blockquote { border-left: 3px solid #e2e8f0; padding-left: 12px; color: #6b7280; margin: 8px 0; }
.kai-ai-bubble a { color: #2563eb; }

/* Message meta */
.kai-ai-meta {
    display: flex; align-items: center; gap: 6px;
    font-size: 0.67rem; color: #d1d5db;
    margin-top: 6px;
}

/* Sources */
.kai-sources { margin-top: 5px; }
.kai-sources-btn {
    display: inline-flex; align-items: center; gap: 4px;
    font-size: 0.69rem; color: #9ca3af;
    background: none; border: none; padding: 0;
    cursor: pointer;
    transition: color .12s;
}
.kai-sources-btn:hover { color: #4b5563; }
.kai-source-chips { display: none; flex-wrap: wrap; gap: 5px; margin-top: 6px; }
.kai-source-chip {
    display: inline-flex; align-items: center; gap: 4px;
    background: #f8fafc; border: 1px solid #e2e8f0;
    border-radius: 20px; padding: 3px 10px;
    font-size: 0.67rem; color: #4b5563;
}

/* Thinking dots */
.kai-thinking {
    display: inline-flex; gap: 5px; align-items: center; padding: 4px 0;
}
.kai-thinking span {
    width: 7px; height: 7px; border-radius: 50%;
    background: #d1d5db;
    animation: kaiThink 1.3s infinite;
}
.kai-thinking span:nth-child(2) { animation-delay: .2s; }
.kai-thinking span:nth-child(3) { animation-delay: .4s; }
@keyframes kaiThink {
    0%,60%,100% { transform: translateY(0); opacity: .3; }
    30%          { transform: translateY(-5px); opacity: 1; }
}

/* ── Input bar ────────────────────────────────────────────────────────── */
.kai-input-area {
    background: #fff;
    padding: 14px 32px 18px;
    flex-shrink: 0;
}
.kai-input-inner { max-width: 780px; margin: 0 auto; }

.kai-input-shell {
    display: flex; align-items: flex-end; gap: 0;
    background: #fff;
    border: 1.5px solid #e5e7eb;
    border-radius: 14px;
    padding: 10px 10px 10px 16px;
    transition: border-color .2s, box-shadow .2s;
    box-shadow: 0 2px 8px rgba(0,0,0,.04);
}
.kai-input-shell:focus-within {
    border-color: #2563eb;
    box-shadow: 0 0 0 3px rgba(37,99,235,.09), 0 2px 8px rgba(0,0,0,.04);
}
.kai-textarea {
    flex: 1; border: none; background: transparent; padding: 0;
    font-size: 0.875rem; line-height: 1.55;
    resize: none; outline: none; font-family: inherit;
    min-height: 24px; max-height: 180px; overflow-y: auto;
    color: #111;
}
.kai-textarea::placeholder { color: #adb5bd; }
.kai-send {
    width: 36px; height: 36px;
    border-radius: 9px; border: none;
    background: #2563eb; color: #fff;
    display: flex; align-items: center; justify-content: center;
    cursor: pointer; flex-shrink: 0;
    align-self: flex-end;
    transition: background .15s, transform .1s;
    font-size: 0.82rem;
}
.kai-send:hover { background: #1d4ed8; transform: scale(1.04); }
.kai-send:disabled { background: #d1d5db; cursor: not-allowed; transform: none; }

.kai-input-foot {
    display: flex; align-items: center; justify-content: space-between;
    margin-top: 7px; font-size: 0.68rem; color: #c4c9d4;
}
.kai-gen-badge {
    display: none; align-items: center; gap: 5px;
    color: #2563eb; font-size: 0.7rem; font-weight: 500;
}
.kai-gen-dot {
    width: 6px; height: 6px; border-radius: 50%;
    background: #2563eb; animation: kaiThink 1.3s infinite;
}

/* ── Offline warning ─────────────────────────────────────────────────── */
.kai-offline {
    display: flex; align-items: flex-start; gap: 10px;
    background: #fff7ed; border: 1px solid #fed7aa;
    border-radius: 9px; padding: 10px 14px;
    font-size: 0.78rem; color: #9a3412; margin-bottom: 10px;
}
.kai-offline i { color: #f97316; margin-top: 1px; flex-shrink: 0; }
.kai-offline code { background: #fef3c7; padding: 1px 5px; border-radius: 3px; }

/* ── Welcome screen ──────────────────────────────────────────────────── */
.kai-welcome {
    max-width: 720px; margin: 0 auto;
    padding: 48px 32px 0;
}
.kai-welcome-hero {
    text-align: center; margin-bottom: 36px;
}
.kai-logo {
    width: 56px; height: 56px; border-radius: 16px;
    background: #1a1d23;
    display: inline-flex; align-items: center; justify-content: center;
    font-size: 1.1rem; font-weight: 800; color: #4c8bf5;
    letter-spacing: -1px; margin-bottom: 18px;
    box-shadow: 0 4px 20px rgba(0,0,0,.12);
}
.kai-welcome-hero h2 {
    font-size: 1.6rem; font-weight: 700; color: #0f172a; margin-bottom: 10px;
}
.kai-welcome-hero p {
    font-size: 0.9rem; color: #6b7280; line-height: 1.65; max-width: 460px; margin: 0 auto;
}

.kai-suggestions {
    display: grid; grid-template-columns: repeat(3, 1fr); gap: 10px;
}
.kai-sug {
    background: #fff; border: 1px solid #e9eaec;
    border-radius: 12px; padding: 14px 15px;
    cursor: pointer; text-align: left;
    transition: border-color .15s, box-shadow .15s, transform .12s;
    display: flex; flex-direction: column; gap: 6px;
}
.kai-sug:hover {
    border-color: #93c5fd;
    box-shadow: 0 4px 16px rgba(37,99,235,.08);
    transform: translateY(-2px);
}
.kai-sug-icon {
    width: 30px; height: 30px; border-radius: 8px;
    background: #eff6ff; color: #2563eb;
    display: flex; align-items: center; justify-content: center;
    font-size: 0.78rem;
}
.kai-sug strong {
    font-size: 0.78rem; font-weight: 600; color: #0f172a; display: block;
}
.kai-sug span {
    font-size: 0.73rem; color: #6b7280; line-height: 1.4;
}
</style>
@endpush

@section('content')
<div class="kai-wrap">

    {{-- ════════ SIDEBAR ════════ --}}
    <div class="kai-sidebar">

        <a href="{{ route('ai.new') }}" class="kai-new">
            <i class="bi bi-plus-lg"></i> New chat
        </a>

        <div class="kai-search">
            <i class="bi bi-search"></i>
            <input type="text" placeholder="Search conversations…" id="convSearch" oninput="filterConvs(this.value)">
        </div>

        <div class="kai-conv-list" id="convList">
            @if($allConversations->isNotEmpty())
            <div class="kai-conv-group-label">Recent</div>
            @endif

            @forelse($allConversations as $conv)
            <a href="{{ route('ai.show', $conv) }}"
               class="kai-conv-item {{ $conv->id === $conversation->id ? 'active' : '' }}"
               data-title="{{ strtolower($conv->title ?? 'new conversation') }}">
                <div class="kai-conv-icon"><i class="bi bi-chat"></i></div>
                <div class="kai-conv-body">
                    <span class="kai-conv-title">{{ $conv->title ?? 'New conversation' }}</span>
                    <div class="kai-conv-time">
                        <span>{{ $conv->model }}</span><span>·</span>
                        <span>{{ $conv->updated_at->diffForHumans(short: true) }}</span>
                    </div>
                </div>
                @if($conv->id === $conversation->id)
                <form action="{{ route('ai.destroy', $conv) }}" method="POST" style="display:contents"
                      onsubmit="return confirm('Delete this conversation?')">
                    @csrf @method('DELETE')
                    <button type="submit" class="kai-conv-del" title="Delete"
                            onclick="event.stopPropagation()">
                        <i class="bi bi-trash"></i>
                    </button>
                </form>
                @endif
            </a>
            @empty
            <div style="padding:24px 12px;text-align:center;color:#9ca3af;font-size:0.78rem;line-height:1.6;">
                <i class="bi bi-chat-dots" style="font-size:1.4rem;display:block;margin-bottom:8px;color:#d1d5db;"></i>
                No conversations yet
            </div>
            @endforelse
        </div>

        <div class="kai-sidebar-foot">
            <div class="kai-ollama-dot" id="ollamaDot"></div>
            <span id="ollamaLabel">Connecting…</span>
        </div>
    </div>

    {{-- ════════ MAIN ════════ --}}
    <div class="kai-main">

        {{-- Top bar --}}
        <div class="kai-topbar">
            <div class="kai-topbar-title">
                <span id="conv-title">{{ $conversation->title ?? 'Kore AI' }}</span>
                @if($conversation->contextProject)
                <span class="kai-topbar-badge">
                    <i class="bi bi-folder"></i>{{ $conversation->contextProject->project_number }}
                </span>
                @endif
            </div>

            {{-- Project scope --}}
            <div class="dropdown">
                <button class="kai-btn" type="button" data-bs-toggle="dropdown">
                    <i class="bi bi-folder"></i>
                    {{ $conversation->contextProject?->project_number ?? 'All projects' }}
                    <i class="bi bi-chevron-down" style="font-size:.58rem;"></i>
                </button>
                <ul class="dropdown-menu dropdown-menu-end" style="font-size:.82rem;min-width:210px;">
                    <li>
                        <button class="dropdown-item" onclick="setProjectScope(null)">
                            <i class="bi bi-globe2 me-2 text-muted"></i>All projects
                        </button>
                    </li>
                    <li><hr class="dropdown-divider"></li>
                    @foreach($projects as $p)
                    <li>
                        <button class="dropdown-item" onclick="setProjectScope({{ $p->id }})">
                            <i class="bi bi-folder me-2 text-muted"></i>
                            <strong>{{ $p->project_number }}</strong>
                            <span class="text-muted ms-1">{{ Str::limit($p->title, 26) }}</span>
                        </button>
                    </li>
                    @endforeach
                </ul>
            </div>

            {{-- Model selector --}}
            <div class="kai-model-wrap" id="modelWrap">
                <button class="kai-model-btn" onclick="toggleModelDd(event)">
                    <i class="bi bi-cpu"></i>
                    <span id="modelLabel">{{ $conversation->model }}</span>
                    <i class="bi bi-chevron-down"></i>
                </button>
                <div class="kai-model-dd" id="modelDd">
                    <div class="kai-model-dd-hdr">Available models</div>
                    <div id="modelList">
                        <div style="padding:12px 14px;font-size:.8rem;color:#9ca3af;">Loading…</div>
                    </div>
                    <div class="kai-model-dd-ftr">
                        <a href="https://ollama.com/library" target="_blank">
                            <i class="bi bi-box-arrow-up-right"></i> Ollama library
                        </a>
                    </div>
                </div>
            </div>
        </div>

        {{-- Messages --}}
        <div class="kai-messages" id="messagesArea">
            @if($messages->isEmpty())
            {{-- Welcome --}}
            <div id="welcomeScreen" class="kai-welcome">
                <div class="kai-welcome-hero">
                    <div class="kai-logo">AI</div>
                    <h2>Kore AI</h2>
                    <p>Ask anything about your projects, proposals, team, or finances.
                       I have live access to all data in the system.</p>
                </div>

                <div class="kai-suggestions">
                    <div class="kai-sug" onclick="useSug(this)">
                        <div class="kai-sug-icon"><i class="bi bi-bar-chart-fill"></i></div>
                        <strong>Portfolio health</strong>
                        <span>Show active projects with budget risk flags</span>
                    </div>
                    <div class="kai-sug" onclick="useSug(this)">
                        <div class="kai-sug-icon"><i class="bi bi-receipt-cutoff"></i></div>
                        <strong>Outstanding invoices</strong>
                        <span>What invoices are overdue and how much is owed?</span>
                    </div>
                    <div class="kai-sug" onclick="useSug(this)">
                        <div class="kai-sug-icon"><i class="bi bi-check2-square"></i></div>
                        <strong>My tasks</strong>
                        <span>Open tasks and upcoming deadlines</span>
                    </div>
                    <div class="kai-sug" onclick="useSug(this)">
                        <div class="kai-sug-icon"><i class="bi bi-graph-up-arrow"></i></div>
                        <strong>Proposal pipeline</strong>
                        <span>Summarise open proposals and total fees</span>
                    </div>
                    <div class="kai-sug" onclick="useSug(this)">
                        <div class="kai-sug-icon"><i class="bi bi-people-fill"></i></div>
                        <strong>Team utilization</strong>
                        <span>Who has the most hours logged this month?</span>
                    </div>
                    <div class="kai-sug" onclick="useSug(this)">
                        <div class="kai-sug-icon"><i class="bi bi-search"></i></div>
                        <strong>Document search</strong>
                        <span>Search project documents for engineering comments</span>
                    </div>
                </div>
            </div>

            @else
            {{-- Existing messages --}}
            @foreach($messages as $msg)
                @if($msg->role === 'user')
                <div class="kai-msg-row">
                    <div class="kai-user">
                        <div class="kai-user-bubble">{{ $msg->content }}</div>
                    </div>
                </div>
                @elseif($msg->role === 'assistant')
                <div class="kai-msg-row">
                    <div class="kai-ai-row">
                        <div class="kai-ai-avatar">AI</div>
                        <div class="kai-ai-body">
                            <div class="kai-ai-bubble">
                                <div class="msg-content">{!! nl2br(e($msg->content)) !!}</div>
                            </div>
                            @if($msg->sources && count($msg->sources) > 0)
                            <div class="kai-sources">
                                <button class="kai-sources-btn" onclick="toggleSources(this)">
                                    <i class="bi bi-database me-1"></i>
                                    {{ count($msg->sources) }} source{{ count($msg->sources) !== 1 ? 's' : '' }} used
                                    <i class="bi bi-chevron-down ms-1"></i>
                                </button>
                                <div class="kai-source-chips">
                                    @foreach($msg->sources as $src)
                                    <span class="kai-source-chip">
                                        <i class="bi bi-{{ $src['type'] === 'document' ? 'file-earmark' : ($src['type'] === 'project' ? 'folder' : 'database') }}"></i>
                                        {{ $src['label'] }}
                                    </span>
                                    @endforeach
                                </div>
                            </div>
                            @endif
                            <div class="kai-ai-meta">
                                <span>{{ $msg->model }}</span>
                                @if($msg->processing_time_ms)<span>·</span><span>{{ round($msg->processing_time_ms/1000,1) }}s</span>@endif
                                @if($msg->token_count)<span>·</span><span>{{ $msg->token_count }} tok</span>@endif
                            </div>
                        </div>
                    </div>
                </div>
                @endif
            @endforeach
            @endif
        </div>

        {{-- Input --}}
        <div class="kai-input-area">
            <div class="kai-input-inner">
                <div id="offlineBanner" class="kai-offline d-none">
                    <i class="bi bi-exclamation-triangle"></i>
                    <span><strong>Ollama is not running.</strong> Start it with <code>ollama serve</code> and refresh.
                    Model: <span id="offlineModel"></span></span>
                </div>
                <div class="kai-input-shell">
                    <textarea class="kai-textarea" id="chatInput" rows="1"
                              placeholder="Message Kore AI…"
                              onkeydown="handleKey(event)"
                              oninput="autoResize(this)"></textarea>
                    <button class="kai-send" id="sendBtn" onclick="sendMsg()" title="Send (Enter)">
                        <i class="bi bi-arrow-up-circle-fill"></i>
                    </button>
                </div>
                <div class="kai-input-foot">
                    <span>Enter to send &middot; Shift+Enter for new line</span>
                    <span class="kai-gen-badge" id="genBadge">
                        <span class="kai-gen-dot"></span> Generating…
                    </span>
                </div>
            </div>
        </div>

    </div>{{-- /.kai-main --}}
</div>{{-- /.kai-wrap --}}
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/marked@9.1.6/marked.min.js"></script>
<script src="https://cdn.jsdelivr.net/gh/highlightjs/cdn-release@11.9.0/build/highlight.min.js"></script>
<script>
const CONVERSATION_ID = {{ $conversation->id }};
const CHAT_URL   = '{{ route('ai.chat',   $conversation) }}';
const UPDATE_URL = '{{ route('ai.update', $conversation) }}';
const MODELS_URL = '{{ route('api.ai.models') }}';
const CSRF       = document.querySelector('meta[name="csrf-token"]').content;

let selectedModel = '{{ $conversation->model }}';
let isStreaming   = false;
let firstMsg      = {{ $messages->isEmpty() ? 'true' : 'false' }};

/* ── Markdown ──────────────────────────────────────────────────────────── */
marked.setOptions({ breaks: true, gfm: true });

/* ── Init ──────────────────────────────────────────────────────────────── */
document.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('.kai-ai-bubble .msg-content').forEach(el => {
        el.innerHTML = marked.parse(el.innerText);
        el.querySelectorAll('pre code').forEach(b => hljs.highlightElement(b));
    });
    loadModels();
    scrollBottom();
    document.getElementById('chatInput').focus();
});

/* ── Model selector ────────────────────────────────────────────────────── */
async function loadModels() {
    const dot  = document.getElementById('ollamaDot');
    const lbl  = document.getElementById('ollamaLabel');
    try {
        const data = await fetch(MODELS_URL).then(r => r.json());

        if (!data.available) {
            dot.classList.add('off'); lbl.textContent = 'Ollama offline';
            document.getElementById('offlineBanner').classList.remove('d-none');
            document.getElementById('offlineModel').textContent = selectedModel;
        } else {
            dot.classList.remove('off'); lbl.textContent = 'Ollama connected';
        }

        const list = document.getElementById('modelList');
        if (!data.enabled?.length) {
            list.innerHTML = `<div style="padding:12px 14px;font-size:.8rem;color:#ef4444;">
                No models installed. Run: <code>ollama pull llama3</code></div>`;
            return;
        }
        list.innerHTML = data.enabled.map(name => {
            const inst = data.installed?.find(m => m.name.startsWith(name) || m.name === name);
            const meta = [inst?.params, inst ? inst.size_gb+'GB' : ''].filter(Boolean).join(' · ');
            return `<div class="kai-model-opt${name===selectedModel?' sel':''}" onclick="pickModel('${name}',event)">
                <div><div class="kai-model-name">${name}</div>${meta?`<div class="kai-model-meta">${meta}</div>`:''}</div>
                <i class="bi bi-check2 kai-model-check"></i>
            </div>`;
        }).join('');
    } catch {
        document.getElementById('ollamaDot').classList.add('off');
        document.getElementById('ollamaLabel').textContent = 'Ollama offline';
        document.getElementById('modelList').innerHTML =
            '<div style="padding:12px 14px;font-size:.8rem;color:#9ca3af;">Could not reach Ollama.</div>';
    }
}

function toggleModelDd(e) {
    e.stopPropagation();
    document.getElementById('modelDd').classList.toggle('open');
}
document.addEventListener('click', () => document.getElementById('modelDd').classList.remove('open'));

function pickModel(name, e) {
    e.stopPropagation();
    selectedModel = name;
    document.getElementById('modelLabel').textContent = name;
    document.querySelectorAll('.kai-model-opt').forEach(el => el.classList.remove('sel'));
    e.currentTarget.classList.add('sel');
    document.getElementById('modelDd').classList.remove('open');
    fetch(UPDATE_URL, { method:'PUT', headers:{'Content-Type':'application/json','X-CSRF-TOKEN':CSRF}, body: JSON.stringify({model:name}) });
}

/* ── Project scope ─────────────────────────────────────────────────────── */
function setProjectScope(id) {
    fetch(UPDATE_URL, { method:'PUT', headers:{'Content-Type':'application/json','X-CSRF-TOKEN':CSRF}, body: JSON.stringify({context_project_id:id}) })
        .then(() => location.reload());
}

/* ── Conversation search ───────────────────────────────────────────────── */
function filterConvs(q) {
    document.querySelectorAll('.kai-conv-item').forEach(el => {
        el.style.display = el.dataset.title?.includes(q.toLowerCase()) ? '' : 'none';
    });
}

/* ── Input ─────────────────────────────────────────────────────────────── */
function handleKey(e) {
    if (e.key === 'Enter' && !e.shiftKey) { e.preventDefault(); sendMsg(); }
}
function autoResize(el) {
    el.style.height = 'auto';
    el.style.height = Math.min(el.scrollHeight, 180) + 'px';
}
function useSug(card) {
    const text = card.querySelector('span').textContent.trim();
    const inp = document.getElementById('chatInput');
    inp.value = text; autoResize(inp); sendMsg();
}

/* ── Send / stream ─────────────────────────────────────────────────────── */
async function sendMsg() {
    if (isStreaming) return;
    const inp = document.getElementById('chatInput');
    const msg = inp.value.trim();
    if (!msg) return;
    inp.value = ''; inp.style.height = 'auto';

    document.getElementById('welcomeScreen')?.remove();
    appendUser(msg);
    setStreaming(true);
    const { wrapper, contentEl, metaEl } = appendAI();

    let fullText = '', firstChunk = true, sources = [];
    try {
        const res = await fetch(CHAT_URL, {
            method:'POST',
            headers:{'Content-Type':'application/json','X-CSRF-TOKEN':CSRF,'Accept':'text/event-stream'},
            body: JSON.stringify({ message:msg, model:selectedModel }),
        });
        if (!res.ok) throw new Error(`HTTP ${res.status}`);

        const reader = res.body.getReader();
        const dec = new TextDecoder();
        let buf = '';

        while (true) {
            const { done, value } = await reader.read();
            if (done) break;
            buf += dec.decode(value, { stream:true });
            const lines = buf.split('\n');
            buf = lines.pop();
            for (const line of lines) {
                if (!line.startsWith('data: ')) continue;
                const raw = line.slice(6).trim();
                if (!raw) continue;
                try {
                    const d = JSON.parse(raw);
                    if (d.content !== undefined) {
                        if (firstChunk) { contentEl.innerHTML = ''; firstChunk = false; }
                        fullText += d.content;
                        contentEl.textContent = fullText;
                        scrollBottom();
                    }
                    if (d.done) {
                        sources = d.sources || [];
                        contentEl.innerHTML = marked.parse(fullText);
                        contentEl.querySelectorAll('pre code').forEach(b => hljs.highlightElement(b));
                        let meta = selectedModel;
                        if (d.ms)          meta += ` · ${(d.ms/1000).toFixed(1)}s`;
                        if (d.token_count) meta += ` · ${d.token_count} tok`;
                        metaEl.textContent = meta;
                        if (sources.length) appendSources(wrapper, sources);
                        if (firstMsg) {
                            firstMsg = false;
                            document.getElementById('conv-title').textContent =
                                msg.length > 50 ? msg.slice(0, 50) + '…' : msg;
                        }
                    }
                } catch {}
            }
        }
    } catch (err) {
        contentEl.innerHTML = `<span style="color:#ef4444;display:flex;align-items:center;gap:6px;">
            <i class="bi bi-exclamation-circle"></i>${esc(err.message)}</span>`;
    } finally {
        setStreaming(false);
        scrollBottom();
    }
}

/* ── DOM helpers ───────────────────────────────────────────────────────── */
function appendUser(text) {
    const a = document.getElementById('messagesArea');
    const d = document.createElement('div');
    d.className = 'kai-msg-row';
    d.innerHTML = `<div class="kai-user"><div class="kai-user-bubble">${esc(text)}</div></div>`;
    a.appendChild(d); scrollBottom();
}

function appendAI() {
    const a = document.getElementById('messagesArea');
    const w = document.createElement('div');
    w.className = 'kai-msg-row';
    w.innerHTML = `<div class="kai-ai-row">
        <div class="kai-ai-avatar">AI</div>
        <div class="kai-ai-body">
            <div class="kai-ai-bubble">
                <div class="msg-content">
                    <div class="kai-thinking"><span></span><span></span><span></span></div>
                </div>
            </div>
            <div class="kai-ai-meta"></div>
        </div>
    </div>`;
    a.appendChild(w); scrollBottom();
    return { wrapper: w, contentEl: w.querySelector('.msg-content'), metaEl: w.querySelector('.kai-ai-meta') };
}

function appendSources(wrapper, sources) {
    const metaEl = wrapper.querySelector('.kai-ai-meta');
    const chips = sources.map(s => {
        const ic = s.type==='document'?'file-earmark':s.type==='project'?'folder':'database';
        return `<span class="kai-source-chip"><i class="bi bi-${ic}"></i>${esc(s.label)}</span>`;
    }).join('');
    const div = document.createElement('div');
    div.className = 'kai-sources';
    div.innerHTML = `<button class="kai-sources-btn" onclick="toggleSources(this)">
        <i class="bi bi-database me-1"></i>${sources.length} source${sources.length!==1?'s':''} used
        <i class="bi bi-chevron-down ms-1"></i>
    </button><div class="kai-source-chips">${chips}</div>`;
    metaEl.parentNode.insertBefore(div, metaEl);
}

function toggleSources(btn) {
    const list = btn.nextElementSibling;
    const icon = btn.querySelector('[class*="chevron"]');
    const open = list.style.display === 'flex';
    list.style.display = open ? 'none' : 'flex';
    if (icon) icon.className = icon.className
        .replace(open?'chevron-up':'chevron-down', open?'chevron-down':'chevron-up');
}

function setStreaming(s) {
    isStreaming = s;
    document.getElementById('sendBtn').disabled = s;
    document.getElementById('genBadge').style.display = s ? 'flex' : 'none';
    document.getElementById('chatInput').placeholder = s ? 'Waiting for response…' : 'Message Kore AI…';
}

function scrollBottom() {
    const a = document.getElementById('messagesArea');
    a.scrollTop = a.scrollHeight;
}

function esc(s) {
    return String(s).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/\n/g,'<br>');
}
</script>
@endpush
