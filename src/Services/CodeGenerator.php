<?php

namespace AssistantPhp\Services;

class CodeGenerator
{
    private ProjectAnalyzer $analyzer;
    private FileManager $fileManager;
    private OllamaService $ollama;
    
    public function __construct(
        ProjectAnalyzer $analyzer,
        FileManager $fileManager,
        OllamaService $ollama
    ) {
        $this->analyzer = $analyzer;
        $this->fileManager = $fileManager;
        $this->ollama = $ollama;
    }
    
    /**
     * Créer un fichier avec l'IA - SIMPLE ET EFFICACE
     */
    public function createFile(string $instruction): array
    {
        return $this->fileManager->createFileWithLLM($instruction, $this->ollama, $this->analyzer);
    }
    
    /**
     * Modifier un fichier existant avec l'IA
     */
    public function editFile(string $filePath, string $instruction): array
    {
        if (!$this->fileManager->canSafelyEdit($filePath)) {
            return ['success' => false, 'error' => 'Fichier non modifiable'];
        }
        
        return $this->fileManager->editFileWithLLM($filePath, $instruction, $this->ollama, $this->analyzer);
    }
    
    /**
     * Analyser un fichier et suggérer des améliorations
     */
    public function analyzeAndSuggest(string $filePath): array
    {
        $fileContext = $this->analyzer->getFileContext($filePath);
        
        if (!$fileContext['exists']) {
            return ['success' => false, 'error' => 'Fichier non trouvé'];
        }
        
        $prompt = $this->analyzer->buildContextPrompt(
            "Analyse ce fichier et suggère des améliorations (performance, sécurité, bonnes pratiques): " . $filePath
        );
        
        $prompt .= "\n\nCODE À ANALYSER:\n```php\n" . $fileContext['content'] . "\n```\n";
        
        try {
            $suggestions = $this->ollama->ask($prompt);
            
            return [
                'success' => true,
                'file' => $filePath,
                'suggestions' => $suggestions,
                'file_info' => [
                    'lines' => $fileContext['lines'],
                    'classes' => $fileContext['classes'],
                    'methods' => $fileContext['methods']
                ]
            ];
            
        } catch (\Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }
    
    /**
     * Générer des tests pour un fichier
     */
    public function generateTests(string $filePath): array
    {
        $fileContext = $this->analyzer->getFileContext($filePath);
        
        if (!$fileContext['exists']) {
            return ['success' => false, 'error' => 'Fichier non trouvé'];
        }
        
        $instruction = "Génère des tests PHPUnit complets pour ce fichier: " . $filePath;
        
        $prompt = $this->analyzer->buildContextPrompt($instruction);
        $prompt .= "\n\nCODE À TESTER:\n```php\n" . $fileContext['content'] . "\n```\n";
        
        try {
            $testCode = $this->ollama->ask($prompt);
            
            // Déterminer le chemin du fichier de test
            $testPath = $this->generateTestPath($filePath);
            
            $success = $this->fileManager->writeFile($testPath, $testCode);
            
            return [
                'success' => $success,
                'test_file_created' => $testPath,
                'original_file' => $filePath
            ];
            
        } catch (\Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }
    
    private function generateTestPath(string $filePath): string
    {
        // Convertir app/Http/Controllers/UserController.php
        // en tests/Feature/UserControllerTest.php
        
        $fileName = basename($filePath, '.php');
        
        if (strpos($filePath, 'Controller') !== false) {
            return "tests/Feature/{$fileName}Test.php";
        }
        
        if (strpos($filePath, 'Model') !== false) {
            return "tests/Unit/{$fileName}Test.php";
        }
        
        return "tests/Unit/{$fileName}Test.php";
    }
}
