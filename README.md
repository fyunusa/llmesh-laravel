# LLMesh Laravel Adapter

[![Latest Stable Version](https://poser.pugx.org/llmesh/laravel/v)](https://packagist.org/packages/llmesh/laravel)
[![PHP Version](https://img.shields.io/badge/php-%5E8.1-blue.svg)](https://packagist.org/packages/llmesh/laravel)
[![Framework Version](https://img.shields.io/badge/laravel-10.x%20%7C%2011.x-orange.svg)](https://packagist.org/packages/llmesh/laravel)
[![License](https://img.shields.io/badge/license-MIT-green.svg)](LICENSE)

A first-class Laravel adapter package to integrate [LLMesh Core](https://github.com/fyunusa/llmesh) seamlessly into your Laravel applications.

---

## Installation

Install via Composer:

```bash
composer require llmesh/laravel
```

---

## Configuration

Publish the config file:

```bash
php artisan vendor:publish --tag="llmesh-config"
```

Configure your environment keys in `.env`:

```env
LLMESH_PROVIDER=openai
OPENAI_API_KEY=your-api-key
```

---

## Quick Start

You can use the `LLMesh` facade directly in your controllers or services:

```php
use LLMesh\Laravel\Facades\LLMesh;
use LLMesh\Core\Generators\GenerateTextOptions;

// Text Generation using the default configured provider
$response = LLMesh::generateText(
    GenerateTextOptions::make()->withPrompt('Tell me a programming joke.')
);

echo $response->getText();
```

---

## Queueable Agent Jobs

The package provides a built-in `RunAgentJob` queueable job to delegate autonomous agent execution into the background:

```php
use LLMesh\Laravel\Jobs\RunAgentJob;
use LLMesh\Core\Agents\Agent;

$agent = Agent::make($provider, 'You are a server reviewer.');

// Dispatch background agent execution
RunAgentJob::dispatch('user-session-123', $agent, 'Analyze the main server configuration.');
```
