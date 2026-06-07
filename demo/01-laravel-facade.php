<?php

/**
 * LLMesh Demo: Laravel Facade Usage
 *
 * This script demonstrates how to interact with LLMesh via the Laravel Facade.
 * Under the hood, LLMesh resolves your default provider from config automatically.
 */

// Load the bootstrap file to handle autoloading and Laravel application setup.
$app = require __DIR__ . '/bootstrap.php';

use LLMesh\Laravel\Facades\LLMesh;
use LLMesh\Core\Generators\GenerateTextOptions;

echo "=== LLMesh Laravel Facade Demo ===\n\n";

try {
    $defaultDriver = $app['config']->get('llmesh.default');
    echo "Resolved default provider: '{$defaultDriver}'\n";

    $prompt = 'Explain the Laravel facade pattern in exactly one sentence.';
    echo "Prompt: \"{$prompt}\"\n\n";

    echo "Sending request via LLMesh Laravel Facade...\n";
    
    // Call generateText directly on the LLMesh Facade.
    // Notice that you don't need to construct or pass the provider instance manually;
    // LLMesh's Laravel manager resolves it automatically from your configuration!
    $response = LLMesh::generateText(
        GenerateTextOptions::make()->withPrompt($prompt)
    );

    echo "\n=== Response Details ===\n";
    echo "Generated Text:\n";
    echo "------------------------------------------------\n";
    echo $response->getText() . "\n";
    echo "------------------------------------------------\n\n";

    // Metadata details
    $usage = $response->getUsage();
    echo "Metadata & Token Usage:\n";
    echo " - Finish Reason:   " . $response->getFinishReason() . "\n";
    echo " - Input Tokens:    " . $usage->getInputTokens() . "\n";
    echo " - Output Tokens:   " . $usage->getOutputTokens() . "\n";
    echo " - Total Tokens:    " . $usage->getTotalTokens() . "\n";
    echo "=========================================\n";

} catch (\Throwable $e) {
    echo "❌ Error occurred: " . $e->getMessage() . "\n";
}
