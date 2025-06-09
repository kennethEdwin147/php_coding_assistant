<?php

namespace AssistantPhp;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Exception\RequestException;

class OllamaService
{
    private Client $client;
    private array $config;
    private string $baseUrl;
    private string $model;
    private bool $connected = false;
    
    public function __construct(array $config)
    {
        $this->config = $config['ollama'] ?? [];
        $this->baseUrl = $this->config['host'] ?? 'http://localhost:11434';
        $this->model = $this->config['model'] ?? 'phi3:mini';
        
        // Initialiser Guzzle avec configuration
        $this->client = new Client([
            'base_uri' => $this->baseUrl,
            'timeout' => $this->config['timeout'] ?? 30,
            'connect_timeout' => 5,
            'headers' => [
                'Content-Type' => 'application/json',
                'Accept' => 'application/json'
            ]
        ]);
    }
    
    /**
     * Tester la connexion à Ollama
     */
    public function testConnection(): array
    {
        try {
            $response = $this->client->get('/api/tags', [
                'timeout' => 3
            ]);
            
            if ($response->getStatusCode() === 200) {
                $this->connected = true;
                $data = json_decode($response->getBody()->getContents(), true);
                $models = array_column($data['models'] ?? [], 'name');
                
                return [
                    'status' => 'connected',
                    'models' => $models,
                    'current_model' => $this->model,
                    'model_available' => in_array($this->model, $models)
                ];
            }
            
            return [
                'status' => 'error',
                'message' => 'Réponse inattendue: ' . $response->getStatusCode()
            ];
            
        } catch (ConnectException $e) {
            return [
                'status' => 'error',
                'message' => 'Impossible de se connecter à Ollama'
            ];
        } catch (RequestException $e) {
            return [
                'status' => 'error',
                'message' => 'Erreur de requête: ' . $e->getMessage()
            ];
        } catch (GuzzleException $e) {
            return [
                'status' => 'error',
                'message' => 'Erreur HTTP: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * Poser une question à l'IA
     */
    public function ask(string $question, array $context = []): string
    {
        // Vérifier la connexion d'abord
        $connectionStatus = $this->testConnection();
        if ($connectionStatus['status'] !== 'connected') {
            throw new \Exception('Ollama non connecté: ' . $connectionStatus['message']);
        }
        
        if (!($connectionStatus['model_available'] ?? false)) {
            throw new \Exception("Modèle '{$this->model}' non installé. Lancez: ollama pull {$this->model}");
        }
        
        $prompt = $this->buildPrompt($question, $context);
        
        $data = [
            'model' => $this->model,
            'prompt' => $prompt,
            'stream' => false,
            'options' => [
                   'temperature' => $this->config['temperature'] ?? 0.3,
                    'num_predict' => $this->config['max_tokens'] ?? 3000,  // ← Utiliser la config
                    'top_p' => 0.9
                    // Pas de 'stop' pour éviter les coupures
            ]
        ];
        
        try {
            $response = $this->client->post('/api/generate', [
                'json' => $data,
                'timeout' => $this->config['timeout'] ?? 30
            ]);
            
            if ($response->getStatusCode() !== 200) {
                throw new \Exception('Erreur Ollama: ' . $response->getStatusCode());
            }
            
            $result = json_decode($response->getBody()->getContents(), true);
            
            if (!isset($result['response'])) {
                throw new \Exception('Réponse invalide d\'Ollama');
            }
            
            return trim($result['response']);
            
        } catch (RequestException $e) {
            if ($e->hasResponse()) {
                $errorBody = $e->getResponse()->getBody()->getContents();
                $errorData = json_decode($errorBody, true);
                $errorMsg = $errorData['error'] ?? $e->getMessage();
            } else {
                $errorMsg = $e->getMessage();
            }
            
            throw new \Exception("Erreur lors de la génération: $errorMsg");
            
        } catch (GuzzleException $e) {
            throw new \Exception("Erreur de communication: " . $e->getMessage());
        }
    }
    
    /**
     * Construire le prompt avec contexte
     */
    private function buildPrompt(string $question, array $context): string
    {
        $prompt = "Tu es un assistant PHP expert et concis.\n\n";
        
        // Ajouter le contexte si disponible
        if (!empty($context['framework'])) {
            $prompt .= "CONTEXTE: Projet {$context['framework']}\n";
        }
        
        if (!empty($context['project_path'])) {
            $prompt .= "DOSSIER: " . basename($context['project_path']) . "\n";
        }
        
        if (!empty($context['recent_files'])) {
            $prompt .= "FICHIERS RÉCENTS: " . implode(', ', $context['recent_files']) . "\n";
        }
        
        $prompt .= "\nRègle: Réponds de manière concise et pratique. Donne des exemples de code si pertinent.\n";
        $prompt .= "Question: $question\n\nRéponse:";
        
        return $prompt;
    }
    
    /**
     * Lister les modèles disponibles
     */
    public function listAvailableModels(): array
    {
        try {
            $response = $this->client->get('/api/tags');
            
            if ($response->getStatusCode() === 200) {
                $data = json_decode($response->getBody()->getContents(), true);
                return array_map(function($model) {
                    return [
                        'name' => $model['name'],
                        'size' => $this->formatBytes($model['size'] ?? 0),
                        'modified' => $model['modified_at'] ?? null
                    ];
                }, $data['models'] ?? []);
            }
            
        } catch (GuzzleException $e) {
            // Ignorer les erreurs pour cette fonction
        }
        
        return [];
    }
    
    /**
     * Vérifier si le modèle est disponible
     */
    public function isModelAvailable(): bool
    {
        $status = $this->testConnection();
        return $status['status'] === 'connected' && 
               ($status['model_available'] ?? false);
    }
    
    /**
     * Getter pour l'état de connexion
     */
    public function isConnected(): bool
    {
        return $this->connected;
    }
    
    /**
     * Getter pour le modèle actuel
     */
    public function getCurrentModel(): string
    {
        return $this->model;
    }
    
    /**
     * Changer de modèle
     */
    public function setModel(string $model): void
    {
        $this->model = $model;
        $this->connected = false; // Force une reconnexion
    }
    
    /**
     * Formatter les tailles de fichier
     */
    private function formatBytes(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        
        for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
            $bytes /= 1024;
        }
        
        return round($bytes, 1) . ' ' . $units[$i];
    }
    
    /**
     * Obtenir des statistiques sur la connexion
     */
    public function getConnectionStats(): array
    {
        $status = $this->testConnection();
        
        return [
            'connected' => $status['status'] === 'connected',
            'model' => $this->model,
            'model_available' => $status['model_available'] ?? false,
            'total_models' => count($status['models'] ?? []),
            'base_url' => $this->baseUrl,
            'timeout' => $this->config['timeout'] ?? 30
        ];
    }
}