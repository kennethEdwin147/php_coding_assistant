<?php

return [
    'app_name' => 'Assistant PHP',
    'version' => '1.0.0',
    
    'ollama' => [
        'host' => 'http://147.93.47.47:11434',
        'model' => 'qwen2.5-coder:1.5b',
        'timeout' => 30,
        'temperature' => 0.3,
        'max_tokens' => 3000,  // ← Mettre ça !

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