# llmesh/laravel

> First-class Laravel integration for **LLMesh** — the flexible PHP SDK for interacting with multiple LLM providers.

[![PHP ^8.1](https://img.shields.io/badge/PHP-%5E8.1-blue)](https://www.php.net)
[![Laravel 10|11](https://img.shields.io/badge/Laravel-10%7C11-red)](https://laravel.com)
[![License MIT](https://img.shields.io/badge/License-MIT-green)](LICENSE)

---

## Features

- ✅ **Auto-discovered** Laravel service provider
- ✅ **Facade** `LLMesh::generateText()`, `LLMesh::streamText()`, etc.
- ✅ **Eloquent Memory Store** — store conversation history in your database
- ✅ **Artisan commands** — `llmesh:test`, `llmesh:providers`, `llmesh:clear-memory`
- ✅ **Queue support** — `RunAgentJob` for background agent execution
- ✅ **PSR-14 bridge** — LLMesh core events fire through Laravel's event system
- ✅ **Lazy provider instantiation** — never fails on missing optional packages

---

## Installation

```bash
composer require llmesh/laravel
```

> The package is auto-discovered. No manual registration needed.

### Publish config

```bash
php artisan vendor:publish --tag=llmesh-config
```

### Publish and run migration

```bash
php artisan vendor:publish --tag=llmesh-migrations
php artisan migrate
```

---

## Configuration

Set your provider API key in `.env`:

```env
LLMESH_PROVIDER=openai
OPENAI_API_KEY=sk-...
OPENAI_MODEL=gpt-4o
```

Available providers (each requires its own package):
- `openai` → `llmesh/openai`
- `anthropic` → `llmesh/anthropic`
- `groq` → `llmesh/groq`

---

## Usage

### Facade

```php
use LLMesh\Laravel\Facades\LLMesh;
use LLMesh\Core\Generators\GenerateTextOptions;

$response = LLMesh::generateText(
    GenerateTextOptions::make()->withPrompt('Hello, world!')
);

echo $response->getText();
```

### Streaming

```php
$stream = LLMesh::streamText(
    GenerateTextOptions::make()->withPrompt('Tell me a story'),
);

foreach ($stream->getChunks() as $chunk) {
    echo $chunk->getDelta();
    ob_flush();
}
```

### RAG Pipeline

```php
use LLMesh\Core\RAG\Loaders\DirectoryLoader;
use LLMesh\Core\RAG\Splitters\RecursiveCharacterSplitter;
use LLMesh\Core\RAG\VectorStores\InMemoryVectorStore;

$result = LLMesh::pipeline()
    ->load(new DirectoryLoader(storage_path('docs')))
    ->split(new RecursiveCharacterSplitter(512, 50))
    ->embed(app(ProviderInterface::class))
    ->store(new InMemoryVectorStore())
    ->run();

echo "Indexed {$result->chunksStored} chunks";
```

### Eloquent Memory Store

```php
use LLMesh\Core\Agents\Agent;
use LLMesh\Core\Contracts\MemoryStoreInterface;
use LLMesh\Core\Contracts\ProviderInterface;

$agent = Agent::make(
    provider:     app(ProviderInterface::class),
    systemPrompt: 'You are a helpful assistant.',
)->withMemory(app(MemoryStoreInterface::class), $sessionId);

$result = $agent->run('What did I ask you before?');
```

### Background Agent (Queue)

```php
use LLMesh\Laravel\Jobs\RunAgentJob;
use LLMesh\Laravel\Jobs\AgentOptions;

RunAgentJob::dispatch(
    new AgentOptions(
        prompt:       'Analyse our codebase.',
        systemPrompt: 'You are a senior developer.',
        providerKey:  'openai',
        maxSteps:     8,
    ),
    sessionId: 'user-' . auth()->id(),
)->onQueue('llmesh');
```

Listen for the result:

```php
use LLMesh\Laravel\Events\AgentCompleted;

Event::listen(AgentCompleted::class, function (AgentCompleted $event) {
    logger()->info('Agent done', ['text' => $event->result->finalText]);
});
```

---

## Artisan Commands

| Command | Description |
|---|---|
| `php artisan llmesh:test` | Send a test prompt to the default provider |
| `php artisan llmesh:test --provider=anthropic` | Test a specific provider |
| `php artisan llmesh:providers` | List all providers and their status |
| `php artisan llmesh:clear-memory {sessionId}` | Clear conversation memory |

---

## Testing

```bash
composer test
```

---

## License

MIT
