<?php

use App\Services\AI\Query\SemanticQuery;
use App\Services\AI\RAG\RAGContextBuilder;
use App\Services\GeminiAssistantService;
// use ReflectionMethod;

uses(Tests\TestCase::class);

it('system prompt jalur Data Mart memuat aturan anti-halusinasi inti', function () {
    $method = new ReflectionMethod(GeminiAssistantService::class, 'verifiedSystemPrompt');
    $prompt = $method->invoke(app(GeminiAssistantService::class));

    expect($prompt)->toContain('Jangan mengarang angka')
        ->and($prompt)->toContain('source of truth')
        ->and($prompt)->toContain('TRUNCATED')
        ->and($prompt)->toContain('tidak ditemukan data')
        ->and($prompt)->toContain('jangan menyatakan');
});

it('system prompt function calling juga memuat larangan mengarang angka', function () {
    $method = new ReflectionMethod(GeminiAssistantService::class, 'systemPrompt');
    $prompt = $method->invoke(app(GeminiAssistantService::class));

    expect($prompt)->toContain('JANGAN membuat-buat angka')
        ->and($prompt)->toContain('ATURAN ANTI-HALUSINASI');
});

it('konteks RAG kosong melaporkan nol record tanpa karangan', function () {
    $query = new SemanticQuery(
        module: 'permohonan_bbm',
        intent: 'aggregation',
        measure: 'bill_amounts',
        aggregation: 'SUM',
        filters: ['date_from' => '2026-07-01', 'date_to' => '2026-07-31'],
    );

    $result = [
        'aggregation' => ['function' => 'SUM', 'field' => 'bill_amounts', 'value' => 0.0],
        'matched' => 0,
        'returned' => 0,
        'truncated' => false,
        'records' => [],
    ];

    $context = app(RAGContextBuilder::class)->build($query, $result);

    expect($context)->toContain('VERIFIED RESULT')
        ->and($context)->toContain('RECORD COUNT: 0')
        ->and($context)->not->toContain('RESULTS:');
});

it('konteks RAG menandai truncation dan tidak menyertakan semua data', function () {
    $query = new SemanticQuery(
        module: 'permohonan_bbm',
        intent: 'detail',
        limit: 2,
    );

    $result = [
        'matched' => 10,
        'returned' => 2,
        'truncated' => true,
        'records' => [
            ['request_number' => 'REQ1', 'bill_amounts' => 100],
            ['request_number' => 'REQ2', 'bill_amounts' => 90],
        ],
    ];

    $context = app(RAGContextBuilder::class)->build($query, $result);

    expect($context)->toContain('TRUNCATED: true')
        ->and($context)->toContain('RECORD COUNT: 10')
        ->and($context)->toContain('RETURNED TO MODEL: 2')
        ->and($context)->toContain('RESULTS:');
});

it('konteks RAG tidak membocorkan id internal', function () {
    $query = new SemanticQuery(module: 'permohonan_bbm', intent: 'detail');
    $result = [
        'matched' => 1,
        'returned' => 1,
        'truncated' => false,
        'records' => [
            ['id' => 99, 'requester_id' => 5, 'consumerial_tools_id' => 7, 'request_number' => 'REQ1'],
        ],
    ];

    $context = app(RAGContextBuilder::class)->build($query, $result);

    expect($context)->not->toContain('"requester_id"')
        ->and($context)->not->toContain('"consumerial_tools_id"')
        ->and($context)->not->toContain('"id"');
});
