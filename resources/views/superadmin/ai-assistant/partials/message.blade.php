@php
    $isUser = $msg->role === 'user';
    $content = $msg->content;
    $content = nl2br(e($content));
    if (!$isUser) {
        $content = preg_replace('/\*\*(.+?)\*\*/', '<strong>$1</strong>', $content);
        $content = preg_replace('/^- (.+)$/m', '• $1', $content);
    }
@endphp
<div class="ai-msg {{ $isUser ? 'user' : 'assistant' }}">
    @if (!$isUser)
        <span class="role-tag">AI</span><br>
    @endif
    {!! $content !!}
</div>
