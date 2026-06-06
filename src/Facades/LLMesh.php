<?php

declare(strict_types=1);

namespace LLMesh\Laravel\Facades;

use Illuminate\Support\Facades\Facade;
use LLMesh\Core\Contracts\ProviderInterface;
use LLMesh\Core\Embeddings\EmbeddingResponse;
use LLMesh\Core\Generators\GenerateObjectOptions;
use LLMesh\Core\Generators\GenerateTextOptions;
use LLMesh\Core\Generators\ObjectResponse;
use LLMesh\Core\Generators\StreamResponse;
use LLMesh\Core\Generators\TextResponse;
use LLMesh\Core\RAG\Pipeline;

/**
 * Laravel Facade for LLMesh operations.
 *
 * This facade proxies static calls to the `LLMesh\Laravel\LLMeshManager`
 * accessor which in turn delegates to `LLMesh\Core\LLMesh`.
 *
 * Usage:
 * ```php
 * use LLMesh\Laravel\Facades\LLMesh;
 *
 * $response = LLMesh::generateText(
 *     options: GenerateTextOptions::make()->withPrompt('Hello!')
 * );
 *
 * $response = LLMesh::generateText($provider, $options);
 * $stream   = LLMesh::streamText($provider, $options);
 * $obj      = LLMesh::generateObject($provider, $options);
 * $emb      = LLMesh::embed($provider, 'text to embed');
 * $pipeline = LLMesh::pipeline();
 * ```
 *
 * @method static TextResponse    generateText(ProviderInterface $provider, GenerateTextOptions $options)
 * @method static StreamResponse  streamText(ProviderInterface $provider, GenerateTextOptions $options)
 * @method static ObjectResponse  generateObject(ProviderInterface $provider, GenerateObjectOptions $options)
 * @method static EmbeddingResponse embed(ProviderInterface $provider, string $input, array $options = [])
 * @method static EmbeddingResponse[] embedBatch(ProviderInterface $provider, array $inputs, array $options = [])
 * @method static Pipeline        pipeline()
 *
 * @see \LLMesh\Laravel\LLMeshManager
 */
class LLMesh extends Facade
{
    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor(): string
    {
        return 'llmesh';
    }
}
