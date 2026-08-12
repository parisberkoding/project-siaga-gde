@php
    $isUser = $msg->role === 'user';
    $content = $msg->content;
    if (!$isUser) {
        $content = \App\Support\AiMarkdown::render($content);
    }
@endphp

@if ($isUser)
<div class="ai-row user">
    <div class="ai-bubble user">{{ $content }}</div>
    <div class="ai-avatar user">YOU</div>
</div>
@else
<div class="ai-row assistant">
    <div class="ai-avatar assistant"><i class="bi bi-stars"></i></div>
    <div class="ai-bubble assistant ai-md">{!! $content !!}</div>
</div>
@endif
