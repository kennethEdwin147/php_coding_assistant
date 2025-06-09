<?php

namespace AssistantPhp\Services;

use Symfony\Component\Filesystem\Filesystem;

class FileManager
{
    private Filesystem $filesystem;
    private string $projectPath;
    
    public function __construct(string $projectPath)
    {
        $this->filesystem = new Filesystem();
        $this->projectPath = $projectPath;
    }
    
    /**
     * Lire un fichier avec gestion d'erreurs
     */
    public function readFile(string $relativePath): ?string
    {
        $fullPath = $this->projectPath . '/' . $relativePath;
        
        if (!$this->filesystem->exists($fullPath)) {
            return null;
        }
        
        return file_get_contents($fullPath);
    }
    
    /**
     * Écrire/créer un fichier généré par le LLM
     */
    public function writeFile(string $relativePath, string $content): bool
    {
        $fullPath = $this->projectPath . '/' . $relativePath;
        
        try {
            // Créer le dossier si nécessaire
            $this->filesystem->mkdir(dirname($fullPath));
            
            // Nettoyer le contenu généré par le LLM
            $content = $this->cleanLLMContent($content);
            
            // Écrire le fichier
            $this->filesystem->dumpFile($fullPath, $content);
            
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }
    
    /**
     * Modifier un fichier existant avec le LLM
     */
    public function editFileWithLLM(string $relativePath, string $instruction, OllamaService $ollama, ProjectAnalyzer $analyzer): array
    {
        $currentContent = $this->readFile($relativePath);
        
        if ($currentContent === null) {
            return ['success' => false, 'error' => 'Fichier non trouvé'];
        }
        
        // Construire le prompt pour modification
        $prompt = $this->buildEditPrompt($instruction, $currentContent, $relativePath, $analyzer);
        
        try {
            // Demander au LLM de modifier le code
            $response = $ollama->ask($prompt);
            
            // Extraire le code de la réponse
            $newContent = $this->extractCodeFromLLMResponse($response);
            
            // Backup du fichier original
            $this->backupFile($relativePath);
            
            // Sauvegarder la modification
            $success = $this->writeFile($relativePath, $newContent);
            
            return [
                'success' => $success,
                'file_modified' => $relativePath,
                'backup_created' => $relativePath . '.backup.' . time(),
                'changes' => $this->calculateChanges($currentContent, $newContent)
            ];
            
        } catch (\Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }
    
    /**
     * Créer un nouveau fichier avec le LLM
     */
    public function createFileWithLLM(string $instruction, OllamaService $ollama, ProjectAnalyzer $analyzer): array
    {
        // Construire le prompt contextuel
        $prompt = $analyzer->buildContextPrompt($instruction);
        
        try {
            // Demander au LLM de générer le code
            $response = $ollama->ask($prompt);
            
            // Extraire le code et le chemin
            $result = $this->parseLLMCreationResponse($response);
            
            if (!$result['success']) {
                return $result;
            }
            
            // Créer le fichier
            $success = $this->writeFile($result['file_path'], $result['content']);
            
            return [
                'success' => $success,
                'file_created' => $result['file_path'],
                'content_preview' => substr($result['content'], 0, 200) . '...',
                'suggestions' => $result['suggestions'] ?? []
            ];
            
        } catch (\Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }
    
    /**
     * Nettoyer le contenu généré par le LLM
     */
    private function cleanLLMContent(string $content): string
    {
        // Supprimer les blocs markdown ```php
        $content = preg_replace('/```php\s*/', '', $content);
        $content = preg_replace('/```\s*$/', '', $content);
        
        // Supprimer les explications en début/fin
        $lines = explode("\n", $content);
        $phpStarted = false;
        $cleanLines = [];
        
        foreach ($lines as $line) {
            if (str_starts_with(trim($line), '<?php')) {
                $phpStarted = true;
            }
            
            if ($phpStarted) {
                $cleanLines[] = $line;
            }
        }
        
        return implode("\n", $cleanLines);
    }
    
    /**
     * Extraire le code de la réponse du LLM
     */
    private function extractCodeFromLLMResponse(string $response): string
    {
        // Le LLM peut retourner du texte + code
        // Extraire seulement la partie code PHP
        
        if (preg_match('/```php\s*(.*?)```/s', $response, $matches)) {
            return trim($matches[1]);
        }
        
        // Si pas de blocs markdown, chercher <?php
        if (strpos($response, '<?php') !== false) {
            $start = strpos($response, '<?php');
            return trim(substr($response, $start));
        }
        
        return $response;
    }
    
    /**
     * Parser la réponse de création du LLM
     */
    private function parseLLMCreationResponse(string $response): array
    {
        $result = ['success' => false];
        
        // Extraire le code
        $code = $this->extractCodeFromLLMResponse($response);
        
        if (empty($code)) {
            return ['success' => false, 'error' => 'Aucun code généré'];
        }
        
        // Deviner le chemin du fichier basé sur le contenu
        $filePath = $this->guessFilePathFromCode($code);
        
        if (!$filePath) {
            return ['success' => false, 'error' => 'Impossible de déterminer le chemin du fichier'];
        }
        
        return [
            'success' => true,
            'content' => $code,
            'file_path' => $filePath,
            'suggestions' => $this->extractSuggestions($response)
        ];
    }
    
    /**
     * Deviner le chemin du fichier basé sur le code généré
     */
    private function guessFilePathFromCode(string $code): ?string
    {
        // Extraire le namespace et le nom de classe
        if (preg_match('/namespace\s+([^;]+);/', $code, $namespaceMatch) &&
            preg_match('/class\s+(\w+)/', $code, $classMatch)) {
            
            $namespace = $namespaceMatch[1];
            $className = $classMatch[1];
            
            // Convertir namespace en chemin
            $path = str_replace('\\', '/', $namespace);
            $path = str_replace('App/', 'app/', $path);
            
            return $path . '/' . $className . '.php';
        }
        
        // Fallback: demander à l'utilisateur ou utiliser un nom générique
        return null;
    }
    
    /**
     * Construire le prompt pour édition
     */
    private function buildEditPrompt(string $instruction, string $currentContent, string $filePath, ProjectAnalyzer $analyzer): string
    {
        $context = $analyzer->getContextForLLM();
        
        $prompt = "Tu es un développeur PHP expert qui modifie du code existant.\n\n";
        
        $prompt .= "CONTEXTE DU PROJET:\n";
        $prompt .= "Framework: " . $context['framework']['name'] . "\n";
        $prompt .= "Fichier à modifier: " . $filePath . "\n\n";
        
        $prompt .= "CODE ACTUEL:\n";
        $prompt .= "```php\n" . $currentContent . "\n```\n\n";
        
        $prompt .= "INSTRUCTION: " . $instruction . "\n\n";
        
        $prompt .= "RÈGLES:\n";
        $prompt .= "- Modifie SEULEMENT ce qui est demandé\n";
        $prompt .= "- Préserve le code existant fonctionnel\n";
        $prompt .= "- Respecte le style de code existant\n";
        $prompt .= "- Ajoute les imports nécessaires\n";
        $prompt .= "- Retourne le code complet modifié\n\n";
        
        $prompt .= "CODE MODIFIÉ:\n";
        
        return $prompt;
    }
    
    private function backupFile(string $relativePath): bool
    {
        $content = $this->readFile($relativePath);
        
        if ($content === null) {
            return false;
        }
        
        $backupPath = $relativePath . '.backup.' . time();
        return $this->writeFile($backupPath, $content);
    }
    
    private function calculateChanges(string $oldContent, string $newContent): array
    {
        $oldLines = explode("\n", $oldContent);
        $newLines = explode("\n", $newContent);
        
        return [
            'lines_added' => count($newLines) - count($oldLines),
            'total_lines' => count($newLines)
        ];
    }
    
    private function extractSuggestions(string $response): array
    {
        $suggestions = [];
        
        // Chercher des suggestions dans la réponse
        if (preg_match_all('/(?:suggestion|recommandation|conseil):\s*(.+)/i', $response, $matches)) {
            $suggestions = $matches[1];
        }
        
        return array_slice($suggestions, 0, 3); // Max 3 suggestions
    }
    
    /**
     * Vérifier si un fichier peut être modifié en sécurité
     */
    public function canSafelyEdit(string $relativePath): bool
    {
        $fullPath = $this->projectPath . '/' . $relativePath;
        
        if (!$this->filesystem->exists($fullPath)) {
            return false;
        }
        
        if (!is_writable($fullPath)) {
            return false;
        }
        
        // Ne pas modifier les fichiers système
        $forbidden = ['/vendor/', '/node_modules/', '/.git/', '/storage/'];
        
        foreach ($forbidden as $forbiddenPath) {
            if (strpos($relativePath, $forbiddenPath) !== false) {
                return false;
            }
        }
        
        return true;
    }
}
