<?php

namespace App\Http\Controllers;

use App\Models\AiChatSession;
use App\Services\GeminiAssistantService;
use Illuminate\Http\Request;
use Throwable;

class AiAssistantController extends Controller
{
    public function index(Request $request)
    {
        $title = 'AI Assistant';

        $sessions = AiChatSession::where('user_id', auth()->id())
            ->withCount('messages')
            ->with('latestMessage')
            ->orderByDesc('updated_at')
            ->get();

        $sessionId = $request->integer('session') ?: null;

        // Tanpa ?session=, buka sesi baru (kosong) — sidebar langsung fresh session.
        $activeSession = $sessionId !== null
            ? $sessions->firstWhere('id', $sessionId)
            : null;

        $messages = $activeSession
            ? $activeSession->messages()->orderBy('id')->get()
            : collect();

        $tokenUsage = $activeSession
            ? $this->tokenTotals($activeSession)
            : ['input' => 0, 'output' => 0];

        return view('superadmin.ai-assistant.index', compact('title', 'sessions', 'activeSession', 'messages', 'tokenUsage'));
    }

    public function send(Request $request, GeminiAssistantService $service)
    {
        $request->validate([
            'message'      => ['required', 'string', 'max:2000'],
            'session_id'   => ['nullable', 'integer', 'exists:ai_chat_sessions,id'],
        ]);

        try {
            $result = $service->ask(
                auth()->id(),
                $request->integer('session_id') ?: null,
                trim($request->input('message'))
            );

            return response()->json([
                'ok'         => true,
                'session_id' => $result['session']->id,
                'title'      => $result['session']->title,
                'reply'      => $result['reply'],
                'reply_html' => \App\Support\AiMarkdown::render($result['reply']),
                'chart'      => $result['chart'] ?? null,
                'token_usage' => $this->tokenTotals($result['session']),
            ]);
        } catch (Throwable $e) {
            return response()->json([
                'ok'    => false,
                'error' => 'Terjadi kesalahan saat memproses pertanyaan: ' . $e->getMessage(),
            ], 422);
        }
    }

    public function destroySession(Request $request, AiChatSession $session)
    {
        abort_if($session->user_id !== auth()->id(), 403);

        $session->delete();

        if ($request->expectsJson()) {
            return response()->json(['ok' => true]);
        }

        return redirect()->route('ai-assistant.index')->with('success', 'Sesi chat dihapus.');
    }

    /**
     * Total token input & output milik sebuah sesi (dijumlah dari metadata pesan).
     *
     * @return array{input: int, output: int}
     */
    protected function tokenTotals(AiChatSession $session): array
    {
        $meta = $session->messages()
            ->where('role', 'assistant')
            ->get(['metadata']);

        $input = $output = 0;
        foreach ($meta as $message) {
            $input += (int) ($message->metadata['token_input'] ?? 0);
            $output += (int) ($message->metadata['token_output'] ?? 0);
        }

        return ['input' => $input, 'output' => $output];
    }
}
