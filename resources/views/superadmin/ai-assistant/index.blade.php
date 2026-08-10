@extends('templates.main')

@section('title', 'AI Assistant')

@section('content')

<style>
    .ai-chat-shell {
        display: grid;
        grid-template-columns: 280px 1fr;
        gap: 16px;
        height: calc(100vh - 180px);
        min-height: 480px;
    }
    .ai-session-panel {
        background: #fff;
        border: 1px solid #e9ecef;
        border-radius: 12px;
        display: flex;
        flex-direction: column;
        overflow: hidden;
    }
    .ai-chat-panel {
        background: #fff;
        border: 1px solid #e9ecef;
        border-radius: 12px;
        display: flex;
        flex-direction: column;
        overflow: hidden;
    }
    .ai-session-list {
        overflow-y: auto;
        flex: 1;
    }
    .ai-session-item {
        display: flex;
        align-items: center;
        gap: 8px;
        padding: 10px 14px;
        border-bottom: 1px solid #f1f3f5;
        cursor: pointer;
        text-decoration: none;
        color: #333;
    }
    .ai-session-item.active { background: #e7f1ff; border-left: 3px solid #0d6efd; }
    .ai-session-item:hover:not(.active) { background: #f8f9fa; }
    .ai-session-item .title {
        flex: 1;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
        font-size: 13px;
    }
    .ai-session-item .meta { font-size: 11px; color: #6c757d; }

    .ai-messages {
        flex: 1;
        overflow-y: auto;
        padding: 20px;
        background: #f8f9fa;
        display: flex;
        flex-direction: column;
        gap: 14px;
    }
    .ai-msg {
        max-width: 82%;
        padding: 10px 14px;
        border-radius: 12px;
        font-size: 14px;
        line-height: 1.55;
        white-space: pre-wrap;
        word-break: break-word;
    }
    .ai-msg.user {
        align-self: flex-end;
        background: #0d6efd;
        color: #fff;
        border-bottom-right-radius: 4px;
    }
    .ai-msg.assistant {
        align-self: flex-start;
        background: #fff;
        border: 1px solid #e9ecef;
        border-bottom-left-radius: 4px;
    }
    .ai-msg.assistant .role-tag {
        display: inline-block;
        font-size: 10px;
        background: #e7f1ff;
        color: #0d6efd;
        border-radius: 4px;
        padding: 1px 6px;
        margin-bottom: 4px;
        font-weight: 600;
    }
    .ai-typing { color: #6c757d; font-style: italic; }
    .ai-input-row {
        display: flex;
        gap: 8px;
        padding: 12px;
        border-top: 1px solid #e9ecef;
        background: #fff;
    }
    .ai-suggestions {
        display: flex;
        flex-wrap: wrap;
        gap: 6px;
        padding: 10px 12px 0;
    }
    .ai-suggestions .chip {
        font-size: 12px;
        background: #f1f3f5;
        border: 1px solid #dee2e6;
        border-radius: 20px;
        padding: 4px 10px;
        cursor: pointer;
        color: #495057;
    }
    .ai-suggestions .chip:hover { background: #e7f1ff; border-color: #0d6efd; color: #0d6efd; }
    .ai-empty {
        margin: auto;
        text-align: center;
        color: #6c757d;
        max-width: 420px;
    }
    .ai-empty .bi { font-size: 48px; color: #ced4da; }
</style>

<div class="pagetitle d-flex justify-content-between align-items-center mb-3">
    <div>
        <h1>AI Assistant <span class="badge bg-warning text-dark">Beta</span></h1>
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

<div class="alert alert-warning d-flex align-items-start gap-2">
    <i class="bi bi-exclamation-triangle-fill fs-5"></i>
    <div>
        <strong>Fitur masih dalam tahap pengembangan / belum stabil.</strong> Jawaban AI dihasilkan oleh model
        <em>Gemini 3 Flash Live</em> dan dapat membaca data dashboard secara real-time, namun hasilnya
        <strong>tidak 100% akurat</strong> dan tidak boleh dijadikan satu-satunya dasar keputusan.
    </div>
</div>

<div class="ai-chat-shell">
    {{-- Panel Sesi --}}
    <div class="ai-session-panel">
        <div class="d-flex align-items-center justify-content-between px-3 py-2 border-bottom bg-light">
            <span class="fw-semibold small">Riwayat Chat</span>
            <button type="button" class="btn btn-sm btn-outline-primary" id="btnNewChat">
                <i class="bi bi-plus-lg"></i> Baru
            </button>
        </div>
        <div class="ai-session-list">
            @forelse ($sessions as $s)
                <a href="{{ route('ai-assistant.index') }}#{{ $s->id }}"
                   class="ai-session-item {{ $activeSession && $activeSession->id === $s->id ? 'active' : '' }}"
                   data-session-id="{{ $s->id }}">
                    <i class="bi bi-chat-dots text-primary"></i>
                    <span class="title">{{ $s->title }}</span>
                    <form action="{{ route('ai-assistant.sessions.destroy', $s->id) }}" method="POST" class="ms-1"
                          onsubmit="return confirm('Hapus sesi chat ini?')">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="btn btn-link btn-sm p-0 text-danger" title="Hapus sesi">
                            <i class="bi bi-trash"></i>
                        </button>
                    </form>
                </a>
            @empty
                <div class="p-3 text-muted small">Belum ada sesi chat.</div>
            @endforelse
        </div>
    </div>

    {{-- Panel Chat --}}
    <div class="ai-chat-panel">
        <div class="px-3 py-2 border-bottom bg-light d-flex align-items-center gap-2">
            <i class="bi bi-robot text-primary"></i>
            <span class="fw-semibold small">AI BBM Assistant</span>
            <span class="badge bg-light text-secondary border ms-auto" id="sessionLabel">
                {{ $activeSession ? $activeSession->title : 'Sesi baru' }}
            </span>
        </div>

        <div class="ai-messages" id="aiMessages">
            @forelse ($messages as $msg)
                @include('superadmin.ai-assistant.partials.message', ['msg' => $msg])
            @empty
                <div class="ai-empty" id="aiEmpty">
                    <i class="bi bi-chat-square-text"></i>
                    <p class="mt-2 mb-1"><strong>Tanyakan apa saja tentang data BBM.</strong></p>
                    <p class="small mb-0">Contoh: berapa total pengeluaran BBM bulan ini? Siapa driver dengan pelaporan paling rajin? Kendaraan mana yang paling boros?</p>
                </div>
            @endforelse
        </div>

        <div class="ai-suggestions" id="aiSuggestions">
            @php $suggestions = [
                'Total pengeluaran BBM bulan ini?',
                'Driver dengan pelaporan paling rajin?',
                'Kendaraan dengan jarak tempuh tertinggi?',
                'Harga Pertamax per region?',
                'Permohonan yang masih pending?',
            ]; @endphp
            @foreach ($suggestions as $sugg)
                <button type="button" class="chip" data-suggestion="{{ $sugg }}">{{ $sugg }}</button>
            @endforeach
        </div>

        <div class="ai-input-row">
            <input type="text" id="aiInput" class="form-control" placeholder="Ketik pertanyaan... (mis. berapa total pengeluaran BBM bulan ini?)"
                   autocomplete="off" maxlength="2000">
            <button type="button" id="btnSend" class="btn btn-primary">
                <i class="bi bi-send"></i>
            </button>
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

        function addMessage(role, content) {
            if (emptyEl) emptyEl.remove();
            const div = document.createElement('div');
            div.className = 'ai-msg ' + role;
            if (role === 'assistant') {
                div.innerHTML = '<span class="role-tag">AI</span><br>' + nl2br(escapeHtml(content));
            } else {
                div.textContent = content;
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
            typing.id = 'typingMsg';

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
                    sessionLabel.textContent = sessionId ? sessionLabel.textContent : message.slice(0, 50);
                    addMessage('assistant', data.reply);
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
        });
    });
</script>
@endpush
