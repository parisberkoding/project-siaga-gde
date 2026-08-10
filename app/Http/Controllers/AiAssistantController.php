<?php

namespace App\Http\Controllers;

use App\Models\AiChatSession;
use App\Services\GeminiAssistantService;
use Illuminate\Http\Request;
use Throwable;

class AiAssistantController extends Controller
{
    public function index()
    {
        $title = 'AI Assistant';

        $sessions = AiChatSession::where('user_id', auth()->id())
            ->withCount('messages')
            ->orderByDesc('updated_at')
            ->get();

        $activeSession = $sessions->first();

        $messages = $activeSession
            ? $activeSession->messages()->orderBy('id')->get()
            : collect();

        return view('superadmin.ai-assistant.index', compact('title', 'sessions', 'activeSession', 'messages'));
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
                'reply'      => $result['reply'],
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
}
