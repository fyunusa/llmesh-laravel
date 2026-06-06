<?php

declare(strict_types=1);

namespace LLMesh\Laravel\Tests\Fakes;

use LLMesh\Core\Contracts\EmbeddingResponseInterface;
use LLMesh\Core\Contracts\ProviderInterface;
use LLMesh\Core\Contracts\ResponseInterface;
use LLMesh\Core\Contracts\StreamInterface;
use LLMesh\Core\Contracts\UsageInterface;
use LLMesh\Core\Generators\TextResponse;
use LLMesh\Core\Generators\Usage;

/**
 * Fake LLM provider for testing.
 *
 * Returns predictable responses without making any real API calls.
 * Accepts any constructor arguments so it can be instantiated generically
 * by the service provider resolution code.
 */
final class FakeProvider implements ProviderInterface
{
    private string $fixedResponse;

    public function __construct(
        string $apiKey   = 'fake-key',
        array  $options  = [],
    ) {
        $this->fixedResponse = 'Fake response from test provider.';
    }

    public function setFixedResponse(string $response): void
    {
        $this->fixedResponse = $response;
    }

    public function chat(array $messages, array $options = []): ResponseInterface
    {
        return new TextResponse(
            text:         $this->fixedResponse,
            usage:        new Usage(10, 20),
            finishReason: 'stop',
            raw:          [],
        );
    }

    public function stream(array $messages, array $options = []): StreamInterface
    {
        throw new \RuntimeException('FakeProvider does not support streaming.');
    }

    public function embed(string|array $input, array $options = []): EmbeddingResponseInterface
    {
        return new FakeEmbeddingResponse([0.1, 0.2, 0.3]);
    }

    public function supports(string $capability): bool
    {
        return match ($capability) {
            'embeddings' => true,
            default      => false,
        };
    }
}

/**
 * Minimal fake EmbeddingResponse for testing.
 */
final class FakeEmbeddingResponse implements EmbeddingResponseInterface
{
    public function __construct(private readonly array $embedding) {}

    public function getEmbedding(): array { return $this->embedding; }
    public function getDimensions(): int  { return count($this->embedding); }
    public function getUsage(): UsageInterface { return new Usage(1, 0); }
}
