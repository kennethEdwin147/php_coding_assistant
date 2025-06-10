<?php

// Charger les secrets si le fichier existe
$secrets = [];
if (file_exists(__DIR__ . '/secrets.php')) {
    $secrets = require __DIR__ . '/secrets.php';
}

return [
    'app_name' => 'Assistant PHP',
    'version' => '1.0.0',
    
    'ollama' => [
        'host' => $secrets['ollama_host'] ?? 'http://localhost:11434',  // ← Fallback local
        'model' => 'qwen2.5-coder:3b',
        'timeout' => 120,
        'temperature' => 0.3,
        'max_tokens' => 3000,
    ],

      'paths' => [
        'storage' => __DIR__ . '/../storage',
        'config' => __DIR__ . '/../config',
        'cache' => __DIR__ . '/../storage/cache'
    ],
    
    'features' => [
        'auto_analyze' => true,
        'save_conversations' => true,
        'context_aware' => true
    ]
    
];

?>