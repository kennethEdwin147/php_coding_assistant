<?php

namespace AssistantPhp;

use League\CLImate\CLImate;

class CLI
{
    private CLImate $climate;
    private array $config;
    private string $currentPath;
    private OllamaService $ollama;
    
    public function __construct(array $config, OllamaService $ollama)
    {
        $this->climate = new CLImate();
        $this->config = $config;
        $this->ollama = $ollama;
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
            ->out(" 🧠 {$appName} v{$version} - AUTONOME ");
        $this->climate->out('');
        
        // Info projet actuel
        $this->climate->green()->inline('📁 Projet: ');
        $this->climate->white()->out(basename($this->currentPath));
        
        // Statut Ollama
        $this->climate->cyan()->inline('🤖 IA: ');
        $status = $this->ollama->testConnection();
        if ($status['status'] === 'connected') {
            $this->climate->green()->out('Connecté (' . $this->ollama->getCurrentModel() . ')');
        } else {
            $this->climate->red()->out('Déconnecté');
        }
        
        $this->climate->out('');
        $this->climate->dim()->out('Parlez naturellement - l\'assistant comprend tout ! 🧠');
        $this->climate->dim()->out('Exemples: "créer une API", "ajouter JWT", "analyser mon code"');
        $this->climate->out('');
    }
    
    private function runInteractiveMode(): void
    {
        while (true) {
            // Afficher le prompt
            $this->climate->inline(basename($this->currentPath) . ' > ');
            
            // Lire l'input
            $input = trim(fgets(STDIN));
            
            if ($this->shouldExit($input)) {
                $this->climate->green()->out('👋 Au revoir !');
                break;
            }
            
            $this->processCommand($input);
        }
    }
    
    // =============================================================================
    // INTELLIGENCE AUTONOME - MÉTHODE PRINCIPALE
    // =============================================================================
    
    private function processCommand(string $input): void
    {
        $input = trim($input);
        
        if (empty($input)) {
            return;
        }
        
        // Commandes système spécifiques (gardées pour fonctionnalités de base)
        $systemCommands = ['help', 'status', 'cd', 'scan', 'exit', 'quit', 'q', 
                        'clear', 'pwd', 'version', 'test-ollama', 'models'];
        
        $firstWord = strtolower(explode(' ', $input)[0]);
        
        if (in_array($firstWord, $systemCommands)) {
            // Traiter les commandes système existantes
            switch ($firstWord) {
                case 'help': $this->showHelp(); break;
                case 'version': $this->showVersion(); break;
                case 'status': $this->showStatus(); break;
                case 'clear': $this->climate->clear(); $this->showWelcome(); break;
                case 'pwd': $this->climate->white()->out($this->currentPath); break;
                case 'cd': $this->changeDirectory(substr($input, 3)); break;
                case 'test-ollama': $this->testOllama(); break;
                case 'models': $this->showModels(); break;
                case 'scan': $this->scanProject(); break;
                case 'exit': case 'quit': case 'q': break;
            }
        } else {
            // TOUT LE RESTE = TRAITEMENT AUTONOME INTELLIGENT
            $this->handleIntelligentRequest($input);
        }
    }
    
    /**
     * Traitement intelligent et autonome de n'importe quelle demande
     */
    private function handleIntelligentRequest(string $input): void
    {
        $this->climate->out('');
        $this->climate->cyan()->inline('🧠 Analyse: ');
        $this->climate->white()->out($input);
        $this->climate->yellow()->out('🤔 Réflexion...');
        
        try {
            // Analyser l'intention avec l'IA
            $intention = $this->analyzeUserIntention($input);
            
            // Effacer l'indicateur de réflexion
            $this->climate->out("\r" . str_repeat(' ', 50) . "\r");
            
            // Exécuter l'action appropriée
            $this->executeBasedOnIntention($intention, $input);
            
        } catch (\Exception $e) {
            $this->climate->out('');
            $this->climate->error('❌ Erreur: ' . $e->getMessage());
        }
        
        $this->climate->out('');
    }
    
    /**
     * Analyser l'intention de l'utilisateur avec l'IA
     */
    private function analyzeUserIntention(string $input): array
    {
        $prompt = "Tu es un assistant de développement PHP intelligent. Analyse cette demande et détermine l'ACTION à effectuer.

DEMANDE: \"$input\"

Retourne UNIQUEMENT ce JSON (sans texte avant/après):
{
    \"action\": \"create_project|add_feature|generate_code|ask_question|modify_file|analyze_code|run_command\",
    \"confidence\": 0.9,
    \"reasoning\": \"pourquoi tu as choisi cette action\",
    \"parameters\": {
        \"project_type\": \"api|web|cli|package|bot\",
        \"framework\": \"slim|laravel|symfony|vanilla\",
        \"libraries\": [\"liste des packages\"],
        \"file_path\": \"chemin/fichier.php\",
        \"component\": \"UserController|middleware|service\",
        \"question_type\": \"how_to|explanation|best_practice|troubleshooting\"
    }
}

EXEMPLES D'ACTIONS:
- \"créer une API\" → create_project
- \"ajouter JWT\" → add_feature  
- \"générer un UserController\" → generate_code
- \"comment faire X\" → ask_question
- \"modifier ce fichier\" → modify_file
- \"analyser mon code\" → analyze_code
- \"lancer le serveur\" → run_command";

        try {
            $response = $this->ollama->ask($prompt);
            $cleanResponse = $this->extractJsonFromResponse($response);
            $intention = json_decode($cleanResponse, true);
            
            if (!$intention || !isset($intention['action'])) {
                throw new \Exception('Intention non comprise');
            }
            
            return $intention;
            
        } catch (\Exception $e) {
            // Fallback avec détection basique
            return $this->detectIntentionBasic($input);
        }
    }
    
    /**
     * Détection d'intention basique en cas d'échec IA
     */
    private function detectIntentionBasic(string $input): array
    {
        $input = strtolower($input);
        
        // Mots-clés pour créer un projet
        $createKeywords = ['créer', 'faire', 'nouveau', 'projet', 'api', 'application', 'site', 'build', 'create'];
        // Mots-clés pour ajouter quelque chose
        $addKeywords = ['ajouter', 'installer', 'intégrer', 'mettre', 'add', 'install'];
        // Mots-clés pour générer du code
        $generateKeywords = ['générer', 'controller', 'middleware', 'service', 'model', 'generate'];
        // Mots-clés pour des questions
        $questionKeywords = ['comment', 'pourquoi', 'qu\'est-ce', 'quelle', 'quel', 'explain', 'how', 'what', 'why'];
        
        foreach ($createKeywords as $keyword) {
            if (strpos($input, $keyword) !== false) {
                return [
                    'action' => 'create_project',
                    'confidence' => 0.7,
                    'reasoning' => 'Détection de mots-clés de création',
                    'parameters' => ['project_type' => 'api', 'framework' => 'slim']
                ];
            }
        }
        
        foreach ($addKeywords as $keyword) {
            if (strpos($input, $keyword) !== false) {
                return [
                    'action' => 'add_feature',
                    'confidence' => 0.7,
                    'reasoning' => 'Détection de mots-clés d\'ajout',
                    'parameters' => []
                ];
            }
        }
        
        foreach ($generateKeywords as $keyword) {
            if (strpos($input, $keyword) !== false) {
                return [
                    'action' => 'generate_code',
                    'confidence' => 0.7,
                    'reasoning' => 'Détection de mots-clés de génération',
                    'parameters' => []
                ];
            }
        }
        
        foreach ($questionKeywords as $keyword) {
            if (strpos($input, $keyword) !== false) {
                return [
                    'action' => 'ask_question',
                    'confidence' => 0.8,
                    'reasoning' => 'Détection de question',
                    'parameters' => ['question_type' => 'how_to']
                ];
            }
        }
        
        // Par défaut = question
        return [
            'action' => 'ask_question',
            'confidence' => 0.5,
            'reasoning' => 'Intention non claire, traité comme question',
            'parameters' => ['question_type' => 'general']
        ];
    }
    
    /**
     * Exécuter l'action basée sur l'intention détectée
     */
    private function executeBasedOnIntention(array $intention, string $originalInput): void
    {
        $this->climate->dim()->out('🎯 Action: ' . $intention['action'] . ' (confiance: ' . ($intention['confidence'] * 100) . '%)');
        $this->climate->out('');
        
        switch ($intention['action']) {
            case 'create_project':
                $this->autonomousCreateProject($originalInput);
                break;
                
            case 'add_feature':
                $this->autonomousAddFeature($originalInput);
                break;
                
            case 'generate_code':
                $this->autonomousGenerateCode($originalInput);
                break;
                
            case 'modify_file':
                $this->autonomousModifyFile($originalInput);
                break;
                
            case 'analyze_code':
                $this->autonomousAnalyzeCode($originalInput);
                break;
                
            case 'run_command':
                $this->autonomousRunCommand($originalInput);
                break;
                
            case 'ask_question':
            default:
                $this->autonomousAnswerQuestion($originalInput);
                break;
        }
    }
    
    // =============================================================================
    // MÉTHODES D'ACTIONS AUTONOMES
    // =============================================================================
    
    /**
     * Création autonome de projet
     */
    private function autonomousCreateProject(string $input): void
    {
        $this->climate->blue()->out('🚀 Création automatique de projet détectée !');
        $this->climate->out('');
        
        // Déléguer à l'IA pour créer le projet complet
        $this->createProjectWithAI($input);
    }
    
    /**
     * Ajout autonome de fonctionnalité
     */
    private function autonomousAddFeature(string $input): void
    {
        $this->climate->blue()->out('📦 Ajout de fonctionnalité détecté !');
        
        if (!file_exists('composer.json')) {
            $this->climate->yellow()->out('⚠️  Aucun projet détecté. Création d\'un projet de base...');
            $this->autonomousCreateProject('Projet PHP avec ' . $input);
            return;
        }
        
        // Analyser quoi ajouter avec l'IA
        $prompt = "L'utilisateur veut ajouter: \"$input\"

Retourne UNIQUEMENT ce JSON:
{\"packages\": [\"liste\", \"des\", \"packages\"], \"description\": \"courte description\"}";

        try {
            $response = $this->ollama->ask($prompt);
            $analysis = json_decode($this->extractJsonFromResponse($response), true);
            
            if (!empty($analysis['packages'])) {
                $this->climate->yellow()->out('📚 Installation: ' . implode(', ', $analysis['packages']));
                
                foreach ($analysis['packages'] as $package) {
                    $this->executeCommand('composer require ' . $package, false);
                    $this->climate->green()->out('  ✅ ' . $package);
                }
                
                $this->climate->green()->out('🎉 Fonctionnalité ajoutée avec succès !');
            }
            
        } catch (\Exception $e) {
            $this->climate->error('❌ Impossible d\'analyser la demande: ' . $e->getMessage());
        }
    }
    
    /**
     * Génération autonome de code
     */
    private function autonomousGenerateCode(string $input): void
    {
        $this->climate->blue()->out('⚡ Génération de code détectée !');
        
        // Utiliser le système existant de création de fichiers
        try {
            $analyzer = new \AssistantPhp\Services\ProjectAnalyzer($this->currentPath);
            $fileManager = new \AssistantPhp\Services\FileManager($this->currentPath);
            $codeGenerator = new \AssistantPhp\Services\CodeGenerator($analyzer, $fileManager, $this->ollama);
            
            $result = $codeGenerator->createFile($input);
            
            if ($result['success']) {
                $this->climate->green()->out('✅ Code généré: ' . $result['file_created']);
            } else {
                $this->climate->error('❌ Erreur: ' . $result['error']);
            }
            
        } catch (\Exception $e) {
            $this->climate->error('❌ Erreur: ' . $e->getMessage());
        }
    }
    
    /**
     * Modification autonome de fichier
     */
    private function autonomousModifyFile(string $input): void
    {
        $this->climate->blue()->out('✏️  Modification de fichier détectée !');
        
        // Pour l'instant, déléguer à la réponse question
        $this->autonomousAnswerQuestion($input);
    }
    
    /**
     * Analyse autonome de code
     */
    private function autonomousAnalyzeCode(string $input): void
    {
        $this->climate->blue()->out('🔍 Analyse de code détectée !');
        
        // Analyser tous les fichiers PHP du projet
        $phpFiles = $this->findPhpFiles();
        
        if (empty($phpFiles)) {
            $this->climate->yellow()->out('⚠️  Aucun fichier PHP trouvé à analyser');
            return;
        }
        
        $this->climate->yellow()->out('📄 Analyse de ' . count($phpFiles) . ' fichiers...');
        
        try {
            $analyzer = new \AssistantPhp\Services\ProjectAnalyzer($this->currentPath);
            $fileManager = new \AssistantPhp\Services\FileManager($this->currentPath);
            $codeGenerator = new \AssistantPhp\Services\CodeGenerator($analyzer, $fileManager, $this->ollama);
            
            foreach (array_slice($phpFiles, 0, 3) as $file) { // Limiter à 3 fichiers
                $relativePath = str_replace($this->currentPath . '/', '', $file);
                $result = $codeGenerator->analyzeFile($relativePath);
                
                if ($result['success']) {
                    $this->climate->green()->out('✅ ' . $result['file']);
                    $this->climate->white()->out(substr($result['suggestions'], 0, 200) . '...');
                    $this->climate->out('');
                }
            }
            
        } catch (\Exception $e) {
            $this->climate->error('❌ Erreur lors de l\'analyse: ' . $e->getMessage());
        }
    }
    
    /**
     * Exécution autonome de commande
     */
    private function autonomousRunCommand(string $input): void
    {
        $this->climate->blue()->out('⚡ Commande détectée !');
        
        // Détecter les commandes courantes
        $input = strtolower($input);
        
        if (strpos($input, 'serveur') !== false || strpos($input, 'server') !== false || 
            strpos($input, 'démarrer') !== false || strpos($input, 'start') !== false ||
            strpos($input, 'lancer') !== false || strpos($input, 'run') !== false) {
            
            $this->climate->yellow()->out('🚀 Démarrage du serveur de développement...');
            
            if (file_exists('public/index.php')) {
                $this->climate->green()->out('✅ Serveur démarré sur http://localhost:8000');
                $this->climate->yellow()->out('💡 Appuyez sur Ctrl+C pour arrêter');
                $this->climate->out('');
                passthru('php -S localhost:8000 -t public');
            } else {
                $this->climate->error('❌ Pas de fichier public/index.php trouvé');
            }
        } elseif (strpos($input, 'install') !== false || strpos($input, 'composer') !== false) {
            $this->climate->yellow()->out('📦 Installation des dépendances...');
            $this->executeCommand('composer install');
        } else {
            $this->autonomousAnswerQuestion($input);
        }
    }
    
    /**
     * Réponse autonome aux questions
     */
    private function autonomousAnswerQuestion(string $input): void
    {
        // Utiliser la méthode existante handleAsk
        $this->handleAsk($input);
    }
    
    // =============================================================================
    // MÉTHODES UTILITAIRES
    // =============================================================================
    
    /**
     * Créer un projet avec l'IA
     */
    private function createProjectWithAI(string $input): void
    {
        $this->climate->yellow()->out('🤖 L\'IA analyse votre demande et crée le projet...');
        
        $prompt = "Crée un projet PHP complet pour: \"$input\"

Génère les fichiers nécessaires avec leur contenu complet.
Utilise les meilleures pratiques PHP modernes.
Structure le projet de manière professionnelle.";

        try {
            $response = $this->ollama->ask($prompt);
            
            $this->climate->green()->out('✅ Projet créé par l\'IA !');
            $this->climate->white()->out($response);
            
        } catch (\Exception $e) {
            $this->climate->error('❌ Erreur: ' . $e->getMessage());
        }
    }
    
    /**
     * Trouver les fichiers PHP dans le projet
     */
    private function findPhpFiles(): array
    {
        $files = [];
        if (!is_dir($this->currentPath)) return $files;
        
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($this->currentPath, \RecursiveDirectoryIterator::SKIP_DOTS)
        );
        
        foreach ($iterator as $file) {
            if ($file->getExtension() === 'php' && 
                !str_contains($file->getPath(), 'vendor') &&
                !str_contains($file->getPath(), '.git')) {
                $files[] = $file->getPathname();
            }
        }
        
        return array_slice($files, 0, 10); // Limiter pour éviter surcharge
    }
    
    /**
     * Extraire JSON de la réponse IA
     */
    private function extractJsonFromResponse(string $response): string
    {
        // Enlever les blocs markdown
        $response = preg_replace('/```json\s*/', '', $response);
        $response = preg_replace('/```\s*$/', '', $response);
        
        // Chercher le JSON
        if (preg_match('/\{.*\}/s', $response, $matches)) {
            return $matches[0];
        }
        
        throw new \Exception('Aucun JSON trouvé dans la réponse');
    }
    
    /**
     * Exécuter une commande système
     */
    private function executeCommand(string $command, bool $showOutput = true): void
    {
        if ($showOutput) {
            $this->climate->dim()->out('  ⚡ ' . $command);
        }
        
        $output = [];
        $returnCode = 0;
        
        exec($command . ' 2>&1', $output, $returnCode);
        
        if ($returnCode !== 0 && $showOutput) {
            $this->climate->error('⚠️ Commande échouée: ' . $command);
            foreach ($output as $line) {
                $this->climate->dim()->out('     ' . $line);
            }
        }
    }
    
    // =============================================================================
    // MÉTHODES EXISTANTES (CONSERVÉES)
    // =============================================================================
    
    /**
     * Poser une question à l'IA
     */
    private function handleAsk(string $question): void
    {
        if (empty($question)) {
            return;
        }
        
        try {
            // Construire le contexte
            $context = [
                'project_path' => $this->currentPath,
                'framework' => $this->detectFramework()
            ];
            
            // Poser la question
            $response = $this->ollama->ask($question, $context);
            
            // Afficher la réponse
            $this->climate->backgroundGreen()->black()->bold()->out(' 🤖 RÉPONSE ');
            $this->climate->out('');
            $this->climate->out($response);
            
        } catch (\Exception $e) {
            $this->climate->error('❌ Erreur: ' . $e->getMessage());
            
            if (strpos($e->getMessage(), 'non connecté') !== false) {
                $this->climate->dim()->out('💡 Conseil: Vérifiez que votre serveur Ollama est accessible');
            }
        }
    }
    
    private function showHelp(): void
    {
        $this->climate->out('');
        $this->climate->backgroundCyan()->black()->bold()->out(' 📋 ASSISTANT PHP AUTONOME ');
        $this->climate->out('');
        
        $this->climate->yellow()->out('🧠 INTELLIGENCE AUTONOME:');
        $this->climate->white()->out('   Parlez naturellement - l\'assistant comprend et agit automatiquement !');
        $this->climate->out('');
        
        $this->climate->green()->out('💬 EXEMPLES DE DEMANDES:');
        $this->climate->white()->out('   "créer une API REST moderne"');
        $this->climate->white()->out('   "ajouter l\'authentification JWT"');
        $this->climate->white()->out('   "générer un UserController"');
        $this->climate->white()->out('   "analyser mon code"');
        $this->climate->white()->out('   "comment optimiser cette API ?"');
        $this->climate->white()->out('   "démarrer le serveur"');
        $this->climate->out('');
        
        $this->climate->cyan()->out('⚙️  COMMANDES SYSTÈME:');
        $commands = [
            'help' => 'Afficher cette aide',
            'status' => 'Statut de l\'application',
            'test-ollama' => 'Tester la connexion IA',
            'models' => 'Lister les modèles disponibles',
            'scan' => 'Scanner le projet actuel',
            'clear' => 'Nettoyer l\'écran',
            'exit' => 'Quitter l\'application'
        ];
        
        foreach ($commands as $cmd => $desc) {
            $this->climate->green()->inline('  ' . str_pad($cmd, 12));
            $this->climate->white()->out($desc);
        }
        
        $this->climate->out('');
        $this->climate->yellow()->out('🎯 L\'assistant s\'adapte automatiquement à vos demandes !');
        $this->climate->out('');
    }
    
    private function showVersion(): void
    {
        $version = $this->config['version'] ?? '1.0.0';
        $this->climate->green()->out("Version: {$version}");
        $this->climate->dim()->out('PHP ' . PHP_VERSION);
        $this->climate->dim()->out('Mode: Autonome 🧠');
    }
    
    private function showStatus(): void
    {
        $this->climate->out('');
        $this->climate->blue()->out('📊 Statut du système:');
        $this->climate->out('');
        
        // Statut PHP
        $this->climate->green()->inline('✅ PHP: ');
        $this->climate->white()->out(PHP_VERSION);
        
        // Statut Ollama
        $status = $this->ollama->testConnection();
        if ($status['status'] === 'connected') {
            $this->climate->green()->inline('✅ IA: ');
            $this->climate->white()->out('Connecté (' . $this->ollama->getCurrentModel() . ')');
        } else {
            $this->climate->red()->inline('❌ IA: ');
            $this->climate->white()->out('Déconnecté');
        }
        
        // Statut répertoire
        $this->climate->green()->inline('✅ Répertoire: ');
        $this->climate->white()->out($this->currentPath);
        
        // Mode
        $this->climate->green()->inline('✅ Mode: ');
        $this->climate->yellow()->out('Autonome 🧠');
        
        $this->climate->out('');
    }
    
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
                break;
                
            case 'error':
                $this->climate->error('❌ Connexion échouée');
                $this->climate->dim()->out('   Erreur: ' . $status['message']);
                break;
        }
        
        $this->climate->out('');
    }
    
    private function showModels(): void
    {
        $this->climate->out('');
        $this->climate->blue()->out('🤖 Modèles Ollama disponibles:');
        $this->climate->out('');
        
        try {
            $models = $this->ollama->listAvailableModels();
            
            if (empty($models)) {
                $this->climate->yellow()->out('Aucun modèle trouvé');
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
        
        $this->climate->out('');
        $this->climate->green()->out("✅ Ready! Context loaded.");
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
        // Gestion des arguments en ligne de commande
        $this->climate->yellow()->out('Mode ligne de commande');
        $this->climate->dim()->out('Arguments: ' . implode(' ', array_slice($argv, 1)));
    }
    
    private function detectFramework(): ?string
    {
        if (file_exists($this->currentPath . '/artisan')) return 'Laravel';
        if (file_exists($this->currentPath . '/bin/console')) return 'Symfony';
        if (file_exists($this->currentPath . '/wp-config.php')) return 'WordPress';
        if (file_exists($this->currentPath . '/composer.json')) {
            $composer = json_decode(file_get_contents($this->currentPath . '/composer.json'), true);
            if (isset($composer['require']['slim/slim'])) return 'Slim';
        }
        return null;
    }
    
    private function countPhpFiles(): int
    {
        $count = 0;
        if (!is_dir($this->currentPath)) return 0;
        
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($this->currentPath, \RecursiveDirectoryIterator::SKIP_DOTS)
        );
        
        foreach ($iterator as $file) {
            if ($file->getExtension() === 'php' && !str_contains($file->getPath(), 'vendor')) {
                $count++;
            }
        }
        
        return $count;
    }
}

    