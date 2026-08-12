@extends('templates.main')

@section('title', 'AI Assistant · Gaga')

@section('content')

@php
    $userName = trim(auth()->user()?->nama_lengkap ?? '');
    $firstName = $userName !== '' ? explode(' ', $userName)[0] : '';
@endphp

<style>
    :root {
        --ai-emerald: #0d754b;
        --ai-emerald-dark: #0a5d3c;
        --ai-ink: #111827;
        --ai-border: #e5e7eb;
    }

    .ai-page { max-width: 1500px; }

    .ai-shell {
        display: flex;
        gap: 16px;
        height: calc(100vh - 190px);
        min-height: 520px;
    }

    /* ---------- Chat panel (70%) ---------- */
    .ai-chat-panel {
        flex: 7;
        min-width: 0;
        background: #f9fafb;
        border: 1px solid var(--ai-border);
        border-radius: 16px;
        display: flex;
        flex-direction: column;
        overflow: hidden;
        box-shadow: 0 1px 3px rgba(17, 24, 39, 0.06);
    }

    .ai-chat-head {
        display: flex;
        align-items: center;
        gap: 10px;
        padding: 12px 16px;
        background: #fff;
        border-bottom: 1px solid var(--ai-border);
    }
    .ai-chat-head .brand {
        width: 32px; height: 32px;
        border-radius: 9px;
        background: var(--ai-emerald);
        color: #fff;
        display: flex; align-items: center; justify-content: center;
        font-size: 15px;
    }
    .ai-chat-head .name { font-weight: 600; color: var(--ai-ink); font-size: 14px; }
    .ai-chat-head .status-dot {
        width: 7px; height: 7px; border-radius: 50%;
        background: #22c55e; display: inline-block; margin-right: 5px;
    }

    .ai-messages {
        flex: 1;
        overflow-y: auto;
        padding: 20px 18px;
        display: flex;
        flex-direction: column;
        gap: 18px;
    }

    .ai-row {
        display: flex;
        align-items: flex-start;
        gap: 10px;
        max-width: 88%;
    }
    .ai-row.user { align-self: flex-end; }
    .ai-row.assistant { align-self: flex-start; }

    .ai-avatar {
        width: 32px; height: 32px;
        border-radius: 50%;
        display: flex; align-items: center; justify-content: center;
        font-size: 10px; font-weight: 700;
        flex-shrink: 0;
    }
    .ai-avatar.user { background: #e5e7eb; color: #4b5563; }
    .ai-avatar.assistant { background: #d1fae5; color: var(--ai-emerald); }

    .ai-bubble {
        padding: 10px 14px;
        border-radius: 14px;
        font-size: 14px;
        line-height: 1.6;
        word-break: break-word;
    }
    .ai-bubble.user {
        background: #fff;
        border: 1px solid var(--ai-border);
        color: #1f2937;
        border-top-right-radius: 4px;
    }
    .ai-bubble.assistant {
        background: #fff;
        border: 1px solid var(--ai-border);
        color: #374151;
        border-top-left-radius: 4px;
        white-space: normal;
    }

    /* ---------- Markdown response (.ai-md) ---------- */
    .ai-md { line-height: 1.65; }
    .ai-md > :first-child { margin-top: 0; }
    .ai-md > :last-child { margin-bottom: 0; }
    .ai-md h1, .ai-md h2, .ai-md h3, .ai-md h4 { color: var(--ai-ink); font-weight: 700; margin: 14px 0 6px; line-height: 1.3; }
    .ai-md h1 { font-size: 17px; }
    .ai-md h2 { font-size: 15px; }
    .ai-md h3 { font-size: 14px; }
    .ai-md h4 { font-size: 13px; }
    .ai-md p { margin: 6px 0; }
    .ai-md ul, .ai-md ol { margin: 6px 0 6px 4px; padding-left: 20px; }
    .ai-md li { margin: 3px 0; }
    .ai-md ul li::marker { color: var(--ai-emerald); }
    .ai-md strong { font-weight: 700; color: var(--ai-ink); }
    .ai-md table { border-collapse: collapse; width: 100%; margin: 10px 0; font-size: 13px; }
    .ai-md th { background: #ecfdf5; color: var(--ai-ink); font-weight: 600; text-align: left; }
    .ai-md th, .ai-md td { border: 1px solid var(--ai-border); padding: 6px 10px; }
    .ai-md tr:nth-child(even) td { background: #fafafa; }
    .ai-md code { background: #f3f4f6; border-radius: 4px; padding: 1px 5px; font-size: 12.5px; color: #b91c1c; }
    .ai-md pre { background: #0f172a; color: #e2e8f0; border-radius: 8px; padding: 10px 12px; overflow-x: auto; margin: 8px 0; }
    .ai-md pre code { background: transparent; color: inherit; padding: 0; }
    .ai-md blockquote { border-left: 3px solid var(--ai-emerald); margin: 8px 0; padding: 2px 12px; color: #6b7280; background: #f9fafb; }
    .ai-md hr { border: none; border-top: 1px solid var(--ai-border); margin: 12px 0; }
    .ai-md a { color: var(--ai-emerald); text-decoration: underline; word-break: break-all; }

    .ai-suggestions {
        display: flex;
        flex-wrap: wrap;
        gap: 8px;
        padding: 0 16px 10px;
        background: #f9fafb;
    }
    .ai-suggestions .chip {
        font-size: 12px;
        background: #fff;
        border: 1px solid var(--ai-border);
        border-radius: 999px;
        padding: 5px 12px;
        cursor: pointer;
        color: #4b5563;
        transition: all .15s ease;
    }
    .ai-suggestions .chip:hover { border-color: var(--ai-emerald); color: var(--ai-emerald); background: #ecfdf5; }

    .ai-input-wrap {
        padding: 12px 16px;
        background: #fff;
        border-top: 1px solid var(--ai-border);
    }
    .ai-input-box {
        display: flex;
        align-items: center;
        gap: 10px;
        background: #fff;
        border: 1px solid var(--ai-border);
        border-radius: 999px;
        padding: 6px 6px 6px 18px;
        box-shadow: 0 1px 3px rgba(17, 24, 39, 0.07);
        transition: box-shadow .15s ease;
    }
    .ai-input-box:focus-within { box-shadow: 0 0 0 3px rgba(13, 117, 75, 0.12); border-color: var(--ai-emerald); }
    .ai-input-box input {
        flex: 1;
        border: none;
        outline: none;
        background: transparent;
        font-size: 14px;
        color: #1f2937;
        min-width: 0;
    }
    .ai-send {
        width: 36px; height: 36px;
        border-radius: 50%;
        background: var(--ai-emerald);
        color: #fff;
        border: none;
        display: flex; align-items: center; justify-content: center;
        transition: background .15s ease;
        flex-shrink: 0;
    }
    .ai-send:hover { background: var(--ai-emerald-dark); }
    .ai-send:disabled { opacity: .5; }

    .ai-typing-row .ai-bubble { color: #9ca3af; font-style: italic; }

    /* ---------- History panel (30%) ---------- */
    .ai-history-panel {
        flex: 3;
        min-width: 0;
        background: #fff;
        border: 1px solid var(--ai-border);
        border-radius: 16px;
        display: flex;
        flex-direction: column;
        overflow: hidden;
        box-shadow: 0 1px 3px rgba(17, 24, 39, 0.06);
    }
    .ai-history-head {
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding: 14px 16px;
        border-bottom: 1px solid var(--ai-border);
        background: #fff;
    }
    .ai-history-head h3 { font-size: 15px; font-weight: 600; color: var(--ai-ink); margin: 0; }
    .ai-history-list {
        flex: 1;
        overflow-y: auto;
        padding: 12px;
        display: flex;
        flex-direction: column;
        gap: 8px;
    }
    .ai-hist-card {
        border: 1px solid var(--ai-border);
        border-radius: 12px;
        padding: 12px;
        cursor: pointer;
        text-decoration: none;
        color: inherit;
        display: block;
        transition: box-shadow .15s ease, border-color .15s ease;
        background: #fff;
        position: relative;
    }
    .ai-hist-card:hover { box-shadow: 0 4px 12px rgba(17, 24, 39, 0.08); border-color: #d1d5db; }
    .ai-hist-card.active { border-color: var(--ai-emerald); box-shadow: 0 0 0 1px var(--ai-emerald) inset; }
    .ai-hist-card .title { font-weight: 600; color: #1f2937; font-size: 13px; margin-bottom: 3px; }
    .ai-hist-card .preview {
        font-size: 12px;
        color: #6b7280;
        display: -webkit-box;
        -webkit-line-clamp: 2;
        -webkit-box-orient: vertical;
        overflow: hidden;
        margin-bottom: 8px;
    }
    .ai-hist-card .meta { display: flex; align-items: center; justify-content: space-between; }
    .ai-hist-card .count { font-size: 11px; color: #9ca3af; }
    .ai-hist-card .del {
        border: none; background: transparent; color: #d1d5db; padding: 2px 4px;
        font-size: 14px; border-radius: 6px; line-height: 1;
    }
    .ai-hist-card .del:hover { color: #ef4444; background: #fef2f2; }

    .ai-empty {
        margin: auto;
        text-align: center;
        color: #9ca3af;
        max-width: 460px;
        padding: 20px;
    }
    .ai-empty .hero {
        width: 64px; height: 64px;
        margin: 0 auto 14px;
        border-radius: 18px;
        background: #ecfdf5;
        color: var(--ai-emerald);
        display: flex; align-items: center; justify-content: center;
        font-size: 28px;
    }
    .ai-empty h4 { color: var(--ai-ink); font-weight: 600; }

    .ai-scroll-hint { font-size: 11px; color: #9ca3af; text-align: center; padding-top: 2px; }

    @media (max-width: 991.98px) {
        .ai-shell { flex-direction: column; height: auto; }
        .ai-chat-panel { min-height: 520px; }
        .ai-history-panel { min-height: 260px; }
    }
</style>

<div class="pagetitle d-flex justify-content-between align-items-center mb-3 ai-page">
    <div>
        <h1>Gaga <span class="badge bg-warning text-dark">Beta</span></h1>
        <nav>
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="{{ route('superadmin-dashboard') }}">Home</a></li>
                <li class="breadcrumb-item active">AI Assistant</li>
            </ol>
        </nav>
    </div>
</div>

@if (session('error'))
    <div class="alert alert-danger">{{ session('error') }}</div>
@endif
@if (session('success'))
    <div class="alert alert-success">{{ session('success') }}</div>
@endif

<div class="alert alert-warning d-flex align-items-start gap-2 ai-page">
    <i class="bi bi-exclamation-triangle-fill fs-5"></i>
    <div>
        <strong>Fitur masih dalam tahap pengembangan / belum stabil.</strong> Jawaban dihasilkan oleh model
        <em>Llama 3.3 70B (Groq)</em> dan dapat membaca data dashboard secara real-time (BBM, kendaraan, SPD, LPJ, makan siang),
        namun hasilnya <strong>tidak 100% akurat</strong> dan tidak boleh dijadikan satu-satunya dasar keputusan.
    </div>
</div>

<div class="ai-shell ai-page">

    {{-- Panel Chat (70%) --}}
    <div class="ai-chat-panel">
        <div class="ai-chat-head">
            <div class="brand"><i class="bi bi-stars"></i></div>
            <div class="name">Gaga <span class="status-dot"></span></div>
            <span class="badge bg-light text-secondary border ms-auto" id="sessionLabel">
                {{ ($activeSession && $activeSession->title) ? $activeSession->title : 'Sesi baru' }}
            </span>
        </div>

        <div class="ai-messages" id="aiMessages">
            @forelse ($messages as $msg)
                @include('superadmin.ai-assistant.partials.message', ['msg' => $msg])
            @empty
                <div class="ai-empty" id="aiEmpty">
                    <div class="hero"><i class="bi bi-stars"></i></div>
                    <h4>Hai {{ $firstName }}, ada yang bisa Gaga bantu?</h4>
                    <p class="small mb-1">Mau Gaga bantu apa hari ini?</p>
                    <p class="small text-muted mb-0">Contoh: berapa total pengeluaran BBM bulan ini? Siapa yang paling sering SPD? Berapa porsi makan siang besok?</p>
                </div>
            @endforelse
        </div>

        <div class="ai-suggestions" id="aiSuggestions">
            @php $suggestions = [
                'Total pengeluaran BBM bulan ini?',
                'Siapa yang paling sering SPD?',
                'Berapa porsi makan siang besok?',
                'Harga Pertamax per region?',
                'Permohonan BBM yang masih pending?',
            ]; @endphp
            @foreach ($suggestions as $sugg)
                <button type="button" class="chip" data-suggestion="{{ $sugg }}">{{ $sugg }}</button>
            @endforeach
        </div>

        <div class="ai-input-wrap">
            <div class="ai-input-box">
                <input type="text" id="aiInput" placeholder="Mau Gaga bantu apa? (mis. berapa total pengeluaran BBM bulan ini?)"
                       autocomplete="off" maxlength="2000">
                <button type="button" id="btnSend" class="ai-send" title="Kirim">
                    <svg class="bi bi-send" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"></path>
                    </svg>
                </button>
            </div>
            <div class="ai-scroll-hint">Enter untuk kirim · model: Llama 3.3 70B (Groq)</div>
        </div>
    </div>

    {{-- Panel Riwayat (30%) --}}
    <div class="ai-history-panel">
        <div class="ai-history-head">
            <h3><i class="bi bi-clock-history me-1 text-muted"></i> Riwayat Chat</h3>
            <button type="button" class="btn btn-sm btn-outline-primary" id="btnNewChat">
                <i class="bi bi-plus-lg"></i> Baru
            </button>
        </div>
        <div class="ai-history-list">
            @forelse ($sessions as $s)
                <a href="{{ route('ai-assistant.index') }}#{{ $s->id }}"
                   class="ai-hist-card {{ $activeSession && $activeSession->id === $s->id ? 'active' : '' }}"
                   data-session-id="{{ $s->id }}">
                    <div class="title"><i class="bi bi-chat-dots text-primary me-1"></i>{{ Str::limit($s->title ?? 'Sesi baru', 40) }}</div>
                    <div class="preview">
                        @if ($s->latestMessage)
                            {{ Str::limit(strip_tags(\App\Support\AiMarkdown::render($s->latestMessage->content)), 90) }}
                        @else
                            Belum ada pesan.
                        @endif
                    </div>
                    <div class="meta">
                        <span class="count">{{ $s->messages_count }} pesan</span>
                        <form action="{{ route('ai-assistant.sessions.destroy', $s->id) }}" method="POST"
                              onsubmit="return confirm('Hapus sesi chat ini?')">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="del" title="Hapus sesi">
                                <i class="bi bi-trash"></i>
                            </button>
                        </form>
                    </div>
                </a>
            @empty
                <div class="p-3 text-muted small">Belum ada sesi chat.</div>
            @endforelse
        </div>
    </div>
</div>

<input type="hidden" id="aiSessionId" value="{{ $activeSession->id ?? '' }}">

@endsection

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const messagesEl  = document.getElementById('aiMessages');
        const emptyEl     = document.getElementById('aiEmpty');
        const inputEl     = document.getElementById('aiInput');
        const btnSend     = document.getElementById('btnSend');
        const sessionIdEl = document.getElementById('aiSessionId');
        const sessionLabel= document.getElementById('sessionLabel');

        function scrollToBottom() {
            messagesEl.scrollTop = messagesEl.scrollHeight;
        }
        scrollToBottom();

        function nl2br(text) {
            return text.replace(/\n/g, '<br>');
        }

        function addMessage(role, content, contentHtml) {
            if (emptyEl) emptyEl.remove();
            const div = document.createElement('div');
            div.className = 'ai-row ' + role;
            if (role === 'assistant') {
                const body = contentHtml ? contentHtml : nl2br(escapeHtml(content));
                div.innerHTML = '<div class="ai-avatar assistant"><i class="bi bi-stars"></i></div>'
                    + '<div class="ai-bubble assistant ai-md">' + body + '</div>';
            } else {
                div.innerHTML = '<div class="ai-bubble user">' + escapeHtml(content) + '</div>'
                    + '<div class="ai-avatar user">YOU</div>';
            }
            messagesEl.appendChild(div);
            scrollToBottom();
            return div;
        }

        function escapeHtml(text) {
            const el = document.createElement('div');
            el.textContent = text;
            return el.innerHTML;
        }

        function setBusy(busy) {
            btnSend.disabled = busy;
            inputEl.disabled = busy;
        }

        async function send() {
            const message = inputEl.value.trim();
            if (!message || btnSend.disabled) return;

            const sessionId = sessionIdEl.value || null;
            addMessage('user', message);
            inputEl.value = '';
            setBusy(true);

            const typing = addMessage('assistant', 'Memproses...');
            typing.classList.add('ai-typing-row');

            try {
                const res = await fetch('{{ route('ai-assistant.send') }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        'Accept': 'application/json',
                    },
                    body: JSON.stringify({ message, session_id: sessionId }),
                });

                const data = await res.json();
                typing.remove();

                if (data.ok) {
                    sessionIdEl.value = data.session_id;
                    sessionLabel.textContent = data.title || 'Sesi baru';
                    addMessage('assistant', data.reply, data.reply_html);
                } else {
                    addMessage('assistant', '⚠️ ' + (data.error || 'Terjadi kesalahan.'));
                }
            } catch (e) {
                typing.remove();
                addMessage('assistant', '⚠️ Gagal terhubung ke server. Coba lagi.');
            } finally {
                setBusy(false);
                inputEl.focus();
            }
        }

        btnSend.addEventListener('click', send);
        inputEl.addEventListener('keydown', function (e) {
            if (e.key === 'Enter') send();
        });

        document.querySelectorAll('.ai-suggestions .chip').forEach(function (chip) {
            chip.addEventListener('click', function () {
                inputEl.value = chip.dataset.suggestion;
                inputEl.focus();
            });
        });

        document.getElementById('btnNewChat').addEventListener('click', function () {
            sessionIdEl.value = '';
            sessionLabel.textContent = 'Sesi baru';
            messagesEl.innerHTML = '';
            if (emptyEl) messagesEl.appendChild(emptyEl);
            inputEl.focus();
        });
    });
</script>
@endpush
