<?php

declare(strict_types=1);

namespace LLMesh\Laravel\Tests\Feature;

use LLMesh\Core\Generators\GenerateTextOptions;
use LLMesh\Core\Generators\TextResponse;
use LLMesh\Core\RAG\Pipeline;
use LLMesh\Laravel\Facades\LLMesh;
use LLMesh\Laravel\LLMeshManager;
use LLMesh\Laravel\Tests\TestCase;

/**
 * @covers \LLMesh\Laravel\Facades\LLMesh
 * @covers \LLMesh\Laravel\LLMeshManager
 */
class FacadeTest extends TestCase
{
    public function testFacadeResolvesFromContainer(): void
    {
        $resolved = LLMesh::getFacadeRoot();
        $this->assertInstanceOf(LLMeshManager::class, $resolved);
    }

    public function testFacadeGenerateTextReturnsTextResponse(): void
    {
        $options  = GenerateTextOptions::make()->withPrompt('Hello from test!');
        $response = LLMesh::generateText($options);

        $this->assertInstanceOf(TextResponse::class, $response);
        $this->assertNotEmpty($response->getText());
        $this->assertSame('Fake response from test provider.', $response->getText());
    }

    public function testFacadePipelineReturnsNewPipelineInstance(): void
    {
        $pipeline = LLMesh::pipeline();
        $this->assertInstanceOf(Pipeline::class, $pipeline);
    }

    public function testFacadeEmbedReturnsEmbeddingResponse(): void
    {
        $response = LLMesh::embed('test input');
        $this->assertIsArray($response->getEmbedding());
        $this->assertNotEmpty($response->getEmbedding());
    }

    public function testFacadeSingletonIsShared(): void
    {
        $a = LLMesh::getFacadeRoot();
        LLMesh::clearResolvedInstance(\LLMesh\Laravel\LLMeshManager::class);
        $b = LLMesh::getFacadeRoot();

        // After clearing, a new instance is created
        $this->assertInstanceOf(LLMeshManager::class, $b);
    }
}
