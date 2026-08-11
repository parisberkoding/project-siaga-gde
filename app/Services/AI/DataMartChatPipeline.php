<?php

namespace App\Services\AI;

use App\Services\AI\DataMart\DataMartManager;
use App\Services\AI\DataMart\DataMartQueryEngine;
use App\Services\AI\Query\SemanticQuery;
use App\Services\AI\Query\SemanticQueryBuilder;
use App\Services\AI\RAG\RAGContextBuilder;
use App\Services\AI\Resolvers\IntentResolver;
use App\Services\AI\Resolvers\ModuleResolver;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Pipeline Data Mart untuk AI Assistant:
 *
 *   intent -> module -> semantic query -> data mart -> query engine -> context.
 *
 * Pipeline TIDAK memanggil Gemini. GeminiAssistantService yang memanggil Gemini
 * dengan konteks terverifikasi hasil pipeline ini.
 */
class DataMartChatPipeline
{
    public function __construct(
        protected IntentResolver $intents,
        protected ModuleResolver $modules,
        protected SemanticQueryBuilder $builder,
        protected DataMartManager $manager,
        protected DataMartQueryEngine $engine,
        protected RAGContextBuilder $context,
    ) {}

    /**
     * @param  array<string, mixed>|null  $previous  Konteks sesi (module/filters/measure pesan sebelumnya)
     * @return array<string, mixed>
     */
    public function run(string $question, ?array $previous = null): array
    {
        if (! config('ai.datamart.enabled', true)) {
            return ['resolved' => false, 'reason' => 'disabled'];
        }

        $intent = $this->intents->resolve($question);

        try {
            $module = $this->modules->resolve($question, $previous);
        } catch (Throwable $e) {
            Log::warning('DataMartChatPipeline: module resolution error: '.$e->getMessage());
            $module = null;
        }

        if ($module === null) {
            return [
                'resolved' => false,
                'reason' => 'module_not_resolved',
                'metadata' => [
                    'intent' => $intent['intent'],
                    'intent_confidence' => $intent['confidence'],
                    'module' => null,
                    'confidence' => 0.0,
                ],
            ];
        }

        $moduleName = $module['module'];
        $start = hrtime(true);

        try {
            $semanticQuery = $this->builder->build($question, $moduleName, $intent, $previous);
            $queryArray = $this->toEngineQuery($semanticQuery);
            $result = $this->engine->execute($queryArray);
            $context = $this->context->build($semanticQuery, $result);
        } catch (Throwable $e) {
            Log::error('DataMartChatPipeline: query engine error: '.$e->getMessage());

            return [
                'resolved' => false,
                'reason' => 'datamart_failure',
                'error' => $e->getMessage(),
                'metadata' => [
                    'module' => $moduleName,
                    'intent' => $intent['intent'],
                    'confidence' => $module['confidence'],
                ],
            ];
        }

        $duration = (int) round((hrtime(true) - $start) / 1e6);

        $metadata = [
            'module' => $moduleName,
            'module_confidence' => round((float) $module['confidence'], 2),
            'module_reused' => (bool) ($module['reused'] ?? false),
            'intent' => $semanticQuery->intent,
            'intent_confidence' => round((float) $intent['confidence'], 2),
            'filters' => $semanticQuery->filters,
            'aggregation' => $semanticQuery->aggregation,
            'measure' => $semanticQuery->measure,
            'group_by' => $semanticQuery->groupBy,
            'limit' => $semanticQuery->limit,
            'records_matched' => (int) ($result['matched'] ?? 0),
            'records_injected' => in_array($semanticQuery->intent, ['detail', 'listing'], true)
                ? (int) count($result['records'] ?? [])
                : 0,
            'truncated' => (bool) ($result['truncated'] ?? false),
            'execution_ms' => $duration,
            'source' => $result['source'] ?? config('ai.source', 'database'),
            'semantic_query' => $semanticQuery->toArray(),
        ];

        Log::info('AI DataMart resolved', [
            'module' => $moduleName,
            'module_confidence' => $metadata['module_confidence'],
            'module_reused' => $metadata['module_reused'],
            'intent' => $metadata['intent'],
            'intent_confidence' => $metadata['intent_confidence'],
            'filters' => $metadata['filters'],
            'aggregation' => $metadata['aggregation'],
            'records_matched' => $metadata['records_matched'],
            'records_injected' => $metadata['records_injected'],
            'truncated' => $metadata['truncated'],
            'execution_ms' => $duration,
            'source' => $metadata['source'],
        ]);

        return [
            'resolved' => true,
            'semantic_query' => $semanticQuery->toArray(),
            'result' => $result,
            'context' => $context,
            'metadata' => $metadata,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    protected function toEngineQuery(SemanticQuery $query): array
    {
        $engineQuery = [
            'module' => $query->module,
            'filters' => $query->filters,
        ];

        if ($query->aggregation !== null) {
            $engineQuery['aggregation'] = [
                'function' => $query->aggregation,
                'field' => $query->measure,
            ];
        }

        if ($query->groupBy !== null) {
            $engineQuery['group_by'] = $query->groupBy;
        }

        if ($query->sortBy !== null) {
            $engineQuery['sort_by'] = $query->sortBy;
            $engineQuery['sort_dir'] = $query->sortDir;
        }

        if ($query->limit !== null) {
            $engineQuery['limit'] = $query->limit;
        }

        return $engineQuery;
    }
}
