<?php

declare(strict_types=1);

namespace LLMesh\Laravel;

use LLMesh\Core\Contracts\ProviderInterface;
use LLMesh\Core\Embeddings\EmbeddingResponse;
use LLMesh\Core\Generators\GenerateObjectOptions;
use LLMesh\Core\Generators\GenerateTextOptions;
use LLMesh\Core\Generators\ObjectResponse;
use LLMesh\Core\Generators\StreamResponse;
use LLMesh\Core\Generators\TextResponse;
use LLMesh\Core\LLMesh as CoreLLMesh;
use LLMesh\Core\RAG\Pipeline;

/**
 * LLMesh Manager — the object resolved by the LLMesh Facade.
 *
 * Acts as a thin, non-static bridge to `LLMesh\Core\LLMesh`.
 * Because `LLMesh\Core\LLMesh` is a static class, this class simply
 * delegates every call to it, allowing the Facade to operate as expected
 * while preserving full type-safety and IDE completion.
 *
 * The manager is registered as a singleton in the container by
 * `LLMeshServiceProvider`.
 */
final class LLMeshManager
{
    /**
     * @param ProviderInterface $defaultProvider The default provider resolved from config
     */
    public function __construct(
        private readonly ProviderInterface $defaultProvider,
    ) {
    }

    /**
     * Generate text using the provided (or default) provider.
     *
     * @param GenerateTextOptions $options     Generation options
     * @param ProviderInterface|null $provider Override provider (optional)
     * @return TextResponse
     */
    public function generateText(
        GenerateTextOptions $options,
        ?ProviderInterface  $provider = null,
    ): TextResponse {
        return CoreLLMesh::generateText($provider ?? $this->defaultProvider, $options);
    }

    /**
     * Stream text using the provided (or default) provider.
     *
     * @param GenerateTextOptions $options     Generation options
     * @param ProviderInterface|null $provider Override provider (optional)
     * @return StreamResponse
     */
    public function streamText(
        GenerateTextOptions $options,
        ?ProviderInterface  $provider = null,
    ): StreamResponse {
        return CoreLLMesh::streamText($provider ?? $this->defaultProvider, $options);
    }

    /**
     * Generate a structured object using the provided (or default) provider.
     *
     * @param GenerateObjectOptions $options     Generation options
     * @param ProviderInterface|null $provider   Override provider (optional)
     * @return ObjectResponse
     */
    public function generateObject(
        GenerateObjectOptions $options,
        ?ProviderInterface    $provider = null,
    ): ObjectResponse {
        return CoreLLMesh::generateObject($provider ?? $this->defaultProvider, $options);
    }

    /**
     * Generate an embedding for the given text.
     *
     * @param string                 $input    Text to embed
     * @param array                  $options  Provider-specific options
     * @param ProviderInterface|null $provider Override provider (optional)
     * @return EmbeddingResponse
     */
    public function embed(
        string             $input,
        array              $options  = [],
        ?ProviderInterface $provider = null,
    ): EmbeddingResponse {
        return CoreLLMesh::embed($provider ?? $this->defaultProvider, $input, $options);
    }

    /**
     * Batch-embed multiple texts.
     *
     * @param string[]               $inputs   Texts to embed
     * @param array                  $options  Provider-specific options
     * @param ProviderInterface|null $provider Override provider (optional)
     * @return EmbeddingResponse[]
     */
    public function embedBatch(
        array              $inputs,
        array              $options  = [],
        ?ProviderInterface $provider = null,
    ): array {
        return CoreLLMesh::embedBatch($provider ?? $this->defaultProvider, $inputs, $options);
    }

    /**
     * Create a new (empty) RAG Pipeline.
     *
     * @return Pipeline
     */
    public function pipeline(): Pipeline
    {
        return CoreLLMesh::pipeline();
    }

    /**
     * Return the default provider for direct use.
     */
    public function getDefaultProvider(): ProviderInterface
    {
        return $this->defaultProvider;
    }
}
