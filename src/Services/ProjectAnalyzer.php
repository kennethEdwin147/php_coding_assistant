<?php

namespace AssistantPhp\Services;

class ProjectAnalyzer
{
    private string $projectPath;
    
    public function __construct(string $projectPath)
    {
        $this->projectPath = $projectPath;
    }


    /**
     * Construire un prompt avec contexte pour le LLM
     */
    public function buildContextPrompt(string $instruction): string
    {
        $context = $this->getContextForLLM();
        
        $prompt = "Tu es un développeur PHP expert spécialisé en " . $context['framework']['name'] . ".\n\n";
        
        $prompt .= "CONTEXTE DU PROJET:\n";
        $prompt .= "- Framework: " . $context['framework']['name'] . "\n";
        $prompt .= "- Structure: " . implode(', ', $context['patterns']) . "\n";
        $prompt .= "- Modèles disponibles: " . implode(', ', $context['models']) . "\n";
        $prompt .= "- Controllers: " . count($context['controllers']) . " trouvés\n\n";
        
        $prompt .= "INSTRUCTION: " . $instruction . "\n\n";
        
        $prompt .= "RÈGLES:\n";
        $prompt .= "- Respecte les conventions " . $context['framework']['name'] . "\n";
        $prompt .= "- Utilise les bonnes pratiques PHP 8+\n";
        $prompt .= "- Inclus tous les imports nécessaires\n";
        $prompt .= "- Code propre et commenté\n\n";
        
        return $prompt;
    }
    
    /**
     * Obtenir le contexte complet pour le LLM
     */
    public function getContextForLLM(): array
    {
        $framework = $this->detectFramework();
        
        return [
            'framework' => [
                'name' => $framework ?? 'PHP Vanilla',
                'version' => $this->getFrameworkVersion($framework)
            ],
            'models' => $this->findLaravelModels(),
            'controllers' => $this->findLaravelControllers(),
            'patterns' => $this->analyzeStructure(),
            'php_version' => PHP_VERSION
        ];
    }
    
    /**
     * Analyser un fichier spécifique
     */
    public function getFileContext(string $filePath): array
    {
        $fullPath = $this->projectPath . '/' . $filePath;
        
        if (!file_exists($fullPath)) {
            return ['exists' => false];
        }
        
        $content = file_get_contents($fullPath);
        $lines = count(explode("\n", $content));
        
        // Analyser le contenu PHP
        $classes = $this->extractClasses($content);
        $methods = $this->extractMethods($content);
        
        return [
            'exists' => true,
            'content' => $content,
            'lines' => $lines,
            'classes' => $classes,
            'methods' => $methods,
            'namespace' => $this->extractNamespace($content)
        ];
    }
    
    /**
     * Résumé complet du projet
     */
    public function getProjectSummary(): array
    {
        return [
            'framework' => $this->detectFramework(),
            'php_files_count' => $this->countPhpFiles(),
            'models' => $this->findLaravelModels(),
            'controllers' => $this->findLaravelControllers(),
            'structure' => $this->analyzeStructure()
        ];
    }
    
    /**
     * Détecter le framework
     */
    public function detectFramework(): ?string
    {
        if (file_exists($this->projectPath . '/artisan')) return 'Laravel';
        if (file_exists($this->projectPath . '/bin/console')) return 'Symfony';
        if (file_exists($this->projectPath . '/wp-config.php')) return 'WordPress';
        return null;
    }
    
    /**
     * Compter les fichiers PHP
     */
    public function countPhpFiles(): int
    {
        $count = 0;
        $iterator = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($this->projectPath));
        
        foreach ($iterator as $file) {
            if ($file->getExtension() === 'php' && !str_contains($file->getPath(), 'vendor')) {
                $count++;
            }
        }
        
        return $count;
    }
    
    /**
     * Trouver les modèles Laravel
     */
    public function findLaravelModels(): array
    {
        $models = [];
        $modelsPath = $this->projectPath . '/app/Models';
        
        if (is_dir($modelsPath)) {
            $files = scandir($modelsPath);
            foreach ($files as $file) {
                if (pathinfo($file, PATHINFO_EXTENSION) === 'php') {
                    $models[] = pathinfo($file, PATHINFO_FILENAME);
                }
            }
        }
        
        return $models;
    }
    
    /**
     * Trouver les controllers Laravel
     */
    public function findLaravelControllers(): array
    {
        $controllers = [];
        $controllersPath = $this->projectPath . '/app/Http/Controllers';
        
        if (is_dir($controllersPath)) {
            $files = scandir($controllersPath);
            foreach ($files as $file) {
                if (pathinfo($file, PATHINFO_EXTENSION) === 'php' && $file !== 'Controller.php') {
                    $controllers[] = pathinfo($file, PATHINFO_FILENAME);
                }
            }
        }
        
        return $controllers;
    }
    
    /**
     * Analyser la structure du projet
     */
    public function analyzeStructure(): array
    {
        $patterns = [];
        
        if (is_dir($this->projectPath . '/app/Repositories')) {
            $patterns[] = 'Repository Pattern';
        }
        
        if (is_dir($this->projectPath . '/app/Services')) {
            $patterns[] = 'Service Layer';
        }
        
        if (is_dir($this->projectPath . '/app/Http/Resources')) {
            $patterns[] = 'API Resources';
        }
        
        return $patterns;
    }
}