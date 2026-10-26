@php
    $isUser = $msg->role === 'user';
    $content = $msg->displayContent();
    if (!$isUser) {
        $content = \App\Support\AiMarkdown::render($content);
    }
    $chart = !$isUser ? $msg->chartSpec() : null;
@endphp

@if ($isUser)
<div class="ai-row user">
    <div class="ai-bubble user">{{ $content }}</div>
    <div class="ai-avatar user">YOU</div>
</div>
@else
<div class="ai-row assistant">
    <div class="ai-avatar assistant"><i class="bi bi-stars"></i></div>
    <div class="ai-bubble assistant ai-md">
        {!! $content !!}
        @if (!empty($chart))
        <div class="ai-chart-holder">
            <hr>
            <div class="ai-chart-box" data-chart="{{ json_encode($chart, JSON_UNESCAPED_UNICODE | JSON_HEX_APOS) }}"></div>
        </div>
        @endif
    </div>
</div>
@endif