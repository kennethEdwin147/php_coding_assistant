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

            case 'ask':
                $this->handleAsk($args);
                break;
            
            case 'test-ollama':
                $this->testOllama();
                break;

            case 'models':
                $this->showModels();
                break;  
                
            case 'exit':
                
            default:
                $this->climate->error("❌ Commande inconnue: {$command}");
                $this->climate->dim()->out('Tapez "help" pour voir les commandes disponibles');
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

}
