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