<?php

namespace AssistantPhp;

class App
{
    private CLI $cli;
    private array $config;
    
    public function __construct()
    {
        $this->loadConfig();
        $this->ollama = new OllamaService($this->config);  // ← 2. Créer OllamaService
        $this->cli = new CLI($this->config, $this->ollama);  // ← 3. MODIFIER CETTE LIGN
    }
    
    public function run(array $argv): void
    {
        // Pour l'instant, on démarre juste le CLI
        $this->cli->start($argv);
    }
    
    private function loadConfig(): void
    {
        $configFile = __DIR__ . '/../config/config.php';
        
        if (file_exists($configFile)) {
            $this->config = require $configFile;
        } else {
            // Configuration par défaut
            $this->config = [
                'app_name' => 'Assistant PHP',
                'version' => '1.0.0',
                'ollama' => [
                    'host' => 'http://localhost:11434',
                    'model' => 'deepseek-coder:6.7b',
                    'timeout' => 60
                ],
                'paths' => [
                    'storage' => __DIR__ . '/../storage',
                    'config' => __DIR__ . '/../config'
                ]
            ];
        }
    }
    
    public function getConfig(): array
    {
        return $this->config;
    }
}