<?php

namespace AssistantPhp;

use League\CLImate\CLImate;

class CLI
{
    private CLImate $climate;
    private array $config;
    private string $currentPath;
    
    public function __construct(array $config, OllamaService $ollama)
    {
        $this->climate = new CLImate();
        $this->config = $config;
        $this->ollama = $ollama;  // ← Ajouter ça
        $this->currentPath = getcwd();
    }
    
    public function start(array $argv): void
    {
        $this->showWelcome();
        
        // Si on a des arguments en ligne de commande
        if (count($argv) > 1) {
            $this->handleCommandLine($argv);
            return;
        }
        
        // Sinon mode interactif
        $this->runInteractiveMode();
    }
    
    private function showWelcome(): void
    {
        $this->climate->clear();
        
        // Header principal
        $appName = $this->config['app_name'] ?? 'Assistant PHP';
        $version = $this->config['version'] ?? '1.0.0';
        
        $this->climate->backgroundBlue()->white()->bold()
            ->out(" 🧠 {$appName} v{$version} ");
        $this->climate->out('');
        
        // Info projet actuel
        $this->climate->green()->inline('📁 Projet: ');
        $this->climate->white()->out(basename($this->currentPath));
        
        // Statut Ollama (pour plus tard)
        $this->climate->cyan()->inline('🤖 IA: ');
        $this->climate->yellow()->out('En attente de connexion...');
        
        $this->climate->out('');
        $this->climate->dim()->out('Tapez "help" pour voir les commandes • "exit" pour quitter');
        $this->climate->out('');
    }
    
    private function runInteractiveMode(): void
    {
        while (true) {
            // Afficher le prompt manuellement
            $this->climate->inline(basename($this->currentPath) . ' > ');
            
            // Lire l'input avec fgets
            $input = trim(fgets(STDIN));
            
            if ($this->shouldExit($input)) {
                $this->climate->green()->out('👋 Au revoir !');
                break;
            }
            
            $this->processCommand($input);
        }
    }
    

    private function processCommand(string $input): void
    {
        $input = trim($input);
        
        if (empty($input)) {
            return;
        }
        
        $parts = explode(' ', $input, 2);
        $command = strtolower($parts[0]);
        $args = $parts[1] ?? '';
        
        // Commandes système spécifiques
        $systemCommands = ['help', 'status', 'cd', 'scan', 'exit', 'quit', 'q', 
                        'clear', 'pwd', 'version', 'test-ollama', 'models'];
        
        if (in_array($command, $systemCommands)) {
            // Traiter les commandes système
            switch ($command) {
                case 'help':
                    $this->showHelp();
                    break;
                    
                case 'version':
                    $this->showVersion();
                    break;
                    
                case 'status':
                    $this->showStatus();
                    break;
                    
                case 'clear':
                    $this->climate->clear();
                    $this->showWelcome();
                    break;
                    
                case 'pwd':
                    $this->climate->white()->out($this->currentPath);
                    break;
                    
                case 'cd':
                    $this->changeDirectory($args);
                    break;
                    
                case 'test-ollama':
                    $this->testOllama();
                    break;
                    
                case 'models':
                    $this->showModels();
                    break;
                    
                case 'scan':
                    $this->scanProject();
                    break;
                    
                case 'exit':
                case 'quit':
                case 'q':
                    // Cette logique est gérée dans shouldExit()
                    break;
            }
        } else {
            // Tout le reste = question directe à l'IA
            $this->handleAsk($input);  // Passer tout l'input, pas juste les args
        }
    }
    
    private function showHelp(): void
    {
        $this->climate->out('');
        $this->climate->backgroundCyan()->black()->bold()->out(' 📋 COMMANDES DISPONIBLES ');
        $this->climate->out('');
        
        $commands = [
            'help' => 'Afficher cette aide',
            'version' => 'Afficher la version',
            'status' => 'Statut de l\'application et des services',
            'clear' => 'Nettoyer l\'écran',
            'pwd' => 'Afficher le répertoire actuel',
            'cd <path>' => 'Changer de répertoire',
            'exit' => 'Quitter l\'application',
            'models' => 'Lister les modèles Ollama disponibles',
            'create "instruction"' => 'Créer un fichier avec l\'IA',
            'edit "file" "instruction"' => 'Modifier un fichier existant',
            'analyze-file "file"' => 'Analyser un fichier et suggérer des améliorations',
        ];
        
        foreach ($commands as $cmd => $desc) {
            $this->climate->green()->inline('  ' . str_pad($cmd, 15));
            $this->climate->white()->out($desc);
        }
        
        $this->climate->out('');
        $this->climate->yellow()->out('🚧 Plus de commandes à venir dans les prochaines versions !');
        $this->climate->out('');
    }
    
    private function showVersion(): void
    {
        $version = $this->config['version'] ?? '1.0.0';
        $this->climate->green()->out("Version: {$version}");
        $this->climate->dim()->out('PHP ' . PHP_VERSION);
        $this->climate->dim()->out('CLImate ' . $this->getClimateVersion());
    }
    
    private function showStatus(): void
    {
        $this->climate->out('');
        $this->climate->blue()->out('📊 Statut du système:');
        $this->climate->out('');
        
        // Statut PHP
        $this->climate->green()->inline('✅ PHP: ');
        $this->climate->white()->out(PHP_VERSION);
        
        // Statut CLImate
        $this->climate->green()->inline('✅ CLImate: ');
        $this->climate->white()->out('Fonctionnel');
        
        // Statut répertoire
        $this->climate->green()->inline('✅ Répertoire: ');
        $this->climate->white()->out($this->currentPath);
        
        // Statut Ollama (à implémenter plus tard)
        $this->climate->yellow()->inline('⏳ Ollama: ');
        $this->climate->dim()->out('Non testé (prochaine étape)');
        
        $this->climate->out('');
    }
    
    private function changeDirectory(string $path): void
    {
        if (empty($path)) {
            $this->climate->error('❌ Usage: cd <chemin>');
            return;
        }
        
        $newPath = realpath($path);
        
        if ($newPath === false || !is_dir($newPath)) {
            $this->climate->error("❌ Répertoire non trouvé: {$path}");
            return;
        }
        
        $this->currentPath = $newPath;
        chdir($this->currentPath);
        
        $this->climate->green()->inline('✅ Changé vers: ');
        $this->climate->white()->out(basename($this->currentPath));
    }
    
    private function shouldExit(string $input): bool
    {
        $exitCommands = ['exit', 'quit', 'q', 'bye'];
        return in_array(strtolower(trim($input)), $exitCommands);
    }
    
    private function handleCommandLine(array $argv): void
    {
        // Pour plus tard - gestion des arguments en ligne de commande
        $this->climate->yellow()->out('Mode ligne de commande - À implémenter');
        $this->climate->dim()->out('Arguments: ' . implode(' ', array_slice($argv, 1)));
    }
    
    private function getClimateVersion(): string
    {
        // CLImate n'expose pas sa version facilement, on met une version générique
        return '3.x';
    }

        
    /**
     * Afficher les modèles disponibles
     */
    private function showModels(): void
    {
        $this->climate->out('');
        $this->climate->blue()->out('🤖 Modèles Ollama disponibles:');
        $this->climate->out('');
        
        try {
            $models = $this->ollama->listAvailableModels();
            
            if (empty($models)) {
                $this->climate->yellow()->out('Aucun modèle trouvé');
                $this->climate->dim()->out('Installez un modèle avec: ollama pull phi3:mini');
                return;
            }
            
            $currentModel = $this->ollama->getCurrentModel();
            
            foreach ($models as $model) {
                $icon = $model['name'] === $currentModel ? '👉' : '  ';
                $this->climate->white()->inline($icon . ' ' . str_pad($model['name'], 25));
                $this->climate->dim()->out($model['size']);
            }
            
            $this->climate->out('');
            $this->climate->green()->inline('Modèle actuel: ');
            $this->climate->yellow()->out($currentModel);
            
        } catch (\Exception $e) {
            $this->climate->error('❌ Erreur: ' . $e->getMessage());
        }
        
        $this->climate->out('');
    }

    /**
     * NOUVELLE MÉTHODE: Tester la connexion Ollama
     */
    private function testOllama(): void
    {
        $this->climate->out('');
        $this->climate->blue()->out('🔍 Test de connexion Ollama...');
        
        $status = $this->ollama->testConnection();
        
        $this->climate->out('');
        
        switch ($status['status']) {
            case 'connected':
                $this->climate->green()->out('✅ Connexion réussie !');
                $this->climate->white()->inline('   Modèle actuel: ');
                $this->climate->yellow()->out($this->ollama->getCurrentModel());
                
                if (!empty($status['models'])) {
                    $this->climate->white()->out('   Modèles disponibles:');
                    foreach ($status['models'] as $model) {
                        $icon = $model === $this->ollama->getCurrentModel() ? '👉' : '  ';
                        $this->climate->dim()->out("   {$icon} {$model}");
                    }
                }
                
                if (!($status['model_available'] ?? false)) {
                    $this->climate->yellow()->out('⚠️  Modèle actuel non installé');
                    $this->climate->dim()->out('   Commande: ollama pull ' . $this->ollama->getCurrentModel());
                }
                break;
                
            case 'error':
                $this->climate->error('❌ Connexion échouée');
                $this->climate->dim()->out('   Erreur: ' . $status['message']);
                break;
        }
        
        $this->climate->out('');
    }

    /**
     * NOUVELLE MÉTHODE: Poser une question à l'IA
     */
    private function handleAsk(string $question): void
    {
        if (empty($question)) {
            $this->climate->error('❌ Usage: ask "votre question"');
            $this->climate->dim()->out('Exemple: ask "Comment créer un controller Laravel ?"');
            return;
        }
        
        $this->climate->out('');
        $this->climate->cyan()->inline('❓ Question: ');
        $this->climate->white()->out($question);
        $this->climate->out('');
        
        // Indicateur de chargement
        $this->climate->yellow()->inline('🤔 Réflexion en cours');
        
        try {
            // Construire le contexte
            $context = [
                'project_path' => $this->currentPath,
                'framework' => $this->detectFramework()
            ];
            
            // Poser la question
            $response = $this->ollama->ask($question, $context);
            
            // Effacer l'indicateur de chargement
            $this->climate->out("\r" . str_repeat(' ', 50) . "\r");
            
            // Afficher la réponse
            $this->climate->backgroundGreen()->black()->bold()->out(' 🤖 RÉPONSE ');
            $this->climate->out('');
            $this->climate->out($response);
            
        } catch (\Exception $e) {
            $this->climate->out('');
            $this->climate->error('❌ Erreur: ' . $e->getMessage());
            
            if (strpos($e->getMessage(), 'non connecté') !== false) {
                $this->climate->dim()->out('💡 Conseil: Vérifiez que votre serveur Ollama est accessible');
            }
        }
        
        $this->climate->out('');
    }

    /**
     * Détecter le framework (basique pour l'instant)
     */
    private function detectFramework(): ?string
    {
        if (file_exists($this->currentPath . '/artisan')) return 'Laravel';
        if (file_exists($this->currentPath . '/bin/console')) return 'Symfony';
        if (file_exists($this->currentPath . '/wp-config.php')) return 'WordPress';
        return null;
    }

    /**
     * Créer un fichier avec l'IA
     */
    private function handleCreate(string $instruction): void
    {
        if (empty($instruction)) {
            $this->climate->error('❌ Usage: create "instruction"');
            $this->climate->dim()->out('Exemple: create "UserController with CRUD methods"');
            return;
        }
        
        $this->climate->out('');
        $this->climate->blue()->out('🔨 Création en cours...');
        $this->climate->yellow()->inline('🤖 Le LLM génère le code');
        
        try {
            // Initialiser les services
            $analyzer = new \AssistantPhp\Services\ProjectAnalyzer($this->currentPath);
            $fileManager = new \AssistantPhp\Services\FileManager($this->currentPath);
            $codeGenerator = new \AssistantPhp\Services\CodeGenerator($analyzer, $fileManager, $this->ollama);
            
            $result = $codeGenerator->createFile($instruction);
            
            $this->climate->out("\r" . str_repeat(' ', 50) . "\r");
            
            if ($result['success']) {
                $this->climate->green()->out('✅ Fichier créé avec succès !');
                $this->climate->white()->out('📁 ' . $result['file_created']);
            } else {
                $this->climate->error('❌ Erreur: ' . $result['error']);
            }
            
        } catch (\Exception $e) {
            $this->climate->out('');
            $this->climate->error('❌ Erreur: ' . $e->getMessage());
        }
        
        $this->climate->out('');
    }

    /**
     * Modifier un fichier existant
     */
    private function handleEdit(string $args): void
    {
        $parts = explode(' ', $args, 2);
        
        if (count($parts) < 2) {
            $this->climate->error('❌ Usage: edit "fichier.php" "instruction"');
            $this->climate->dim()->out('Exemple: edit "app/Models/User.php" "add email validation"');
            return;
        }
        
        $filePath = trim($parts[0], '"');
        $instruction = trim($parts[1], '"');
        
        $this->climate->out('');
        $this->climate->blue()->out('✏️  Modification en cours...');
        $this->climate->yellow()->inline('🤖 Le LLM modifie le code');
        
        try {
            $analyzer = new \AssistantPhp\Services\ProjectAnalyzer($this->currentPath);
            $fileManager = new \AssistantPhp\Services\FileManager($this->currentPath);
            $codeGenerator = new \AssistantPhp\Services\CodeGenerator($analyzer, $fileManager, $this->ollama);
            
            $result = $codeGenerator->editFile($filePath, $instruction);
            
            $this->climate->out("\r" . str_repeat(' ', 50) . "\r");
            
            if ($result['success']) {
                $this->climate->green()->out('✅ Fichier modifié avec succès !');
                $this->climate->white()->out('📁 ' . $result['file_modified']);
                $this->climate->dim()->out('💾 Backup: ' . $result['backup_created']);
            } else {
                $this->climate->error('❌ Erreur: ' . $result['error']);
            }
            
        } catch (\Exception $e) {
            $this->climate->out('');
            $this->climate->error('❌ Erreur: ' . $e->getMessage());
        }
        
        $this->climate->out('');
    }

    /**
     * Analyser un fichier
     */
    private function handleAnalyzeFile(string $filePath): void
    {
        if (empty($filePath)) {
            $this->climate->error('❌ Usage: analyze-file "fichier.php"');
            $this->climate->dim()->out('Exemple: analyze-file "app/Http/Controllers/UserController.php"');
            return;
        }
        
        $filePath = trim($filePath, '"');
        
        $this->climate->out('');
        $this->climate->blue()->out('🔍 Analyse en cours...');
        
        try {
            $analyzer = new \AssistantPhp\Services\ProjectAnalyzer($this->currentPath);
            $fileManager = new \AssistantPhp\Services\FileManager($this->currentPath);
            $codeGenerator = new \AssistantPhp\Services\CodeGenerator($analyzer, $fileManager, $this->ollama);
            
            $result = $codeGenerator->analyzeFile($filePath);
            
            if ($result['success']) {
                $this->climate->green()->out('✅ Analyse terminée !');
                $this->climate->white()->out('📁 ' . $result['file']);
                $this->climate->out('');
                $this->climate->yellow()->out('💡 Suggestions:');
                $this->climate->white()->out($result['suggestions']);
            } else {
                $this->climate->error('❌ Erreur: ' . $result['error']);
            }
            
        } catch (\Exception $e) {
            $this->climate->error('❌ Erreur: ' . $e->getMessage());
        }
        
        $this->climate->out('');
    }


    /**
 * Scanner le projet automatiquement
 */
private function scanProject(): void
{
    $this->climate->out('');
    $this->climate->blue()->out('🔍 Scanning project...');
    
    // Détection du framework
    $framework = $this->detectFramework();
    if ($framework) {
        $this->climate->green()->out("✅ {$framework} detected");
    } else {
        $this->climate->yellow()->out("⚠️  No framework detected");
    }
    
    // Compter les fichiers PHP
    $phpFiles = $this->countPhpFiles();
    $this->climate->green()->out("✅ {$phpFiles} PHP files found");
    
    // Détecter les modèles (si Laravel)
    if ($framework === 'Laravel') {
        $models = $this->findLaravelModels();
        if (!empty($models)) {
            $this->climate->green()->out("✅ " . count($models) . " models found: " . implode(', ', array_slice($models, 0, 5)) . (count($models) > 5 ? '...' : ''));
        }
        
        $controllers = $this->findLaravelControllers();
        if (!empty($controllers)) {
            $this->climate->green()->out("✅ " . count($controllers) . " controllers found");
        }
    }
    
    $this->climate->out('');
    $this->climate->green()->out("✅ Ready! Context loaded.");
    $this->climate->out('');
}

/**
 * Compter les fichiers PHP
 */
private function countPhpFiles(): int
{
    $count = 0;
    $iterator = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($this->currentPath));
    
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
private function findLaravelModels(): array
{
    $models = [];
    $modelsPath = $this->currentPath . '/app/Models';
    
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
private function findLaravelControllers(): array
{
    $controllers = [];
    $controllersPath = $this->currentPath . '/app/Http/Controllers';
    
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

}
