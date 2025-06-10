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
    private function showHelp(): void
    {
        $this->climate->out('');
        $this->climate->backgroundCyan()->black()->bold()->out(' 📋 COMMANDES DISPONIBLES ');
        $this->climate->out('');
        
        $commands = [
            'help' => 'Afficher cette aide',
            'version' => 'Afficher la version',
            'status' => 'Statut de l\'application et des services',
            'test-ollama' => 'Tester la connexion Ollama',
            'models' => 'Lister les modèles disponibles',
            'scan' => 'Scanner le projet actuel',
            'clear' => 'Nettoyer l\'écran',
            'cd <path>' => 'Changer de répertoire',
            '',
            '🚀 CRÉATION DE PROJETS:',
            'create-project "description"' => 'Créer n\'importe quel projet PHP',
            'init "framework"' => 'Initialiser un framework dans le dossier actuel',
            'add "fonctionnalité"' => 'Ajouter une bibliothèque/fonctionnalité',
            'scaffold "composant"' => 'Générer un composant (controller, etc.)',
            'suggest' => 'Suggérer des améliorations pour le projet',
            '',
            'exit' => 'Quitter l\'application'
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


    /**
 * Créer n'importe quel type de projet PHP avec intelligence contextuelle
 */
private function createUniversalProject(string $instruction): void
{
    if (empty($instruction)) {
        $this->climate->error('❌ Usage: create-project "description du projet"');
        $this->climate->dim()->out('Exemples:');
        $this->climate->dim()->out('  create-project "API REST avec Slim et JWT"');
        $this->climate->dim()->out('  create-project "microservice de paiement avec Stripe"');
        $this->climate->dim()->out('  create-project "bot Telegram avec base de données"');
        return;
    }

    $this->climate->out('');
    $this->climate->blue()->out('🤖 Analyse de la demande...');
    $this->climate->dim()->out('Instruction: ' . $instruction);
    $this->climate->out('');

    try {
        // Analyser la demande avec l'IA
        $analysis = $this->analyzeProjectInstruction($instruction);
        
        // Afficher l'analyse
        $this->displayAnalysis($analysis);
        
        // Demander confirmation
        if ($this->askConfirmation()) {
            $this->executeProjectCreation($analysis);
        } else {
            $this->climate->yellow()->out('❌ Création annulée');
        }
        
    } catch (\Exception $e) {
        $this->climate->error('❌ Erreur lors de l\'analyse: ' . $e->getMessage());
    }
}

/**
 * Analyser l'instruction avec l'IA pour déterminer le type de projet
 */
private function analyzeProjectInstruction(string $instruction): array
{
    $prompt = "Tu es un expert en développement PHP. Analyse cette demande de projet et retourne UNIQUEMENT un JSON valide:

DEMANDE: \"$instruction\"

Retourne ce format JSON exact (sans texte avant/après):
{
    \"project_name\": \"nom-du-projet-en-kebab-case\",
    \"framework\": \"slim|laravel|symfony|vanilla|lumen|codeigniter\",
    \"project_type\": \"api|web|cli|package|microservice|bot\",
    \"description\": \"Description courte du projet\",
    \"libraries\": [\"liste\", \"des\", \"packages\", \"composer\"],
    \"composer_commands\": [\"composer require slim/slim\", \"composer require firebase/php-jwt\"],
    \"folders\": [\"public\", \"src/Controllers\", \"config\"],
    \"main_files\": {
        \"public/index.php\": \"contenu_du_fichier\",
        \"composer.json\": \"contenu_composer\"
    }
}

Analyse intelligemment la demande et suggère les meilleures technologies PHP.";

    try {
        $response = $this->ollama->ask($prompt);
        
        // Nettoyer et extraire le JSON
        $cleanResponse = $this->extractJsonFromResponse($response);
        $analysis = json_decode($cleanResponse, true);
        
        if (!$analysis) {
            throw new \Exception('JSON invalide reçu de l\'IA');
        }
        
        // Validation et fallback
        return $this->validateAndEnrichAnalysis($analysis, $instruction);
        
    } catch (\Exception $e) {
        // Fallback avec détection basique
        return $this->basicProjectDetection($instruction);
    }
}

/**
 * Extraire le JSON de la réponse de l'IA
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
 * Validation et enrichissement de l'analyse
 */
private function validateAndEnrichAnalysis(array $analysis, string $instruction): array
{
    // Valeurs par défaut
    $defaults = [
        'project_name' => 'mon-projet-php',
        'framework' => 'slim',
        'project_type' => 'api',
        'description' => 'Projet PHP généré automatiquement',
        'libraries' => [],
        'composer_commands' => [],
        'folders' => ['public', 'src', 'config'],
        'main_files' => []
    ];
    
    $analysis = array_merge($defaults, $analysis);
    
    // Enrichir selon le framework
    switch ($analysis['framework']) {
        case 'slim':
            $analysis['libraries'] = array_merge(['slim/slim', 'slim/psr7', 'slim/http'], $analysis['libraries']);
            break;
        case 'laravel':
            $analysis['libraries'] = array_merge(['laravel/framework'], $analysis['libraries']);
            break;
        case 'symfony':
            $analysis['libraries'] = array_merge(['symfony/framework-bundle'], $analysis['libraries']);
            break;
    }
    
    // Enrichir selon le type
    if ($analysis['project_type'] === 'api') {
        $analysis['libraries'] = array_merge($analysis['libraries'], ['firebase/php-jwt', 'respect/validation']);
    }
    
    return $analysis;
}

/**
 * Détection basique en cas d'échec de l'IA
 */
private function basicProjectDetection(string $instruction): array
{
    $instruction = strtolower($instruction);
    
    // Détection par mots-clés
    $frameworks = [
        'slim' => ['slim', 'api', 'rest', 'microservice'],
        'laravel' => ['laravel', 'eloquent', 'artisan', 'blade'],
        'symfony' => ['symfony', 'doctrine', 'twig'],
        'vanilla' => ['vanilla', 'simple', 'basique']
    ];
    
    $detectedFramework = 'slim'; // Par défaut
    foreach ($frameworks as $framework => $keywords) {
        foreach ($keywords as $keyword) {
            if (strpos($instruction, $keyword) !== false) {
                $detectedFramework = $framework;
                break 2;
            }
        }
    }
    
    return [
        'project_name' => 'projet-' . date('Ymd-His'),
        'framework' => $detectedFramework,
        'project_type' => strpos($instruction, 'api') !== false ? 'api' : 'web',
        'description' => 'Projet détecté automatiquement: ' . $instruction,
        'libraries' => $this->getDefaultLibraries($detectedFramework),
        'composer_commands' => [],
        'folders' => ['public', 'src', 'config'],
        'main_files' => []
    ];
}

/**
 * Obtenir les bibliothèques par défaut selon le framework
 */
private function getDefaultLibraries(string $framework): array
{
    $libraries = [
        'slim' => ['slim/slim:^4.0', 'slim/psr7:^1.0', 'slim/http:^1.0'],
        'laravel' => ['laravel/framework'],
        'symfony' => ['symfony/framework-bundle', 'symfony/console'],
        'vanilla' => ['monolog/monolog', 'guzzlehttp/guzzle']
    ];
    
    return $libraries[$framework] ?? $libraries['slim'];
}

/**
 * Afficher l'analyse du projet
 */
private function displayAnalysis(array $analysis): void
{
    $this->climate->backgroundGreen()->black()->bold()->out(' 🎯 ANALYSE DU PROJET ');
    $this->climate->out('');
    
    $this->climate->green()->inline('📛 Nom: ');
    $this->climate->white()->out($analysis['project_name']);
    
    $this->climate->green()->inline('🚀 Framework: ');
    $this->climate->yellow()->out(ucfirst($analysis['framework']));
    
    $this->climate->green()->inline('📦 Type: ');
    $this->climate->cyan()->out(ucfirst($analysis['project_type']));
    
    $this->climate->green()->inline('📝 Description: ');
    $this->climate->white()->out($analysis['description']);
    
    if (!empty($analysis['libraries'])) {
        $this->climate->green()->inline('📚 Bibliothèques: ');
        $this->climate->dim()->out(implode(', ', array_slice($analysis['libraries'], 0, 5)) . 
                                  (count($analysis['libraries']) > 5 ? '...' : ''));
    }
    
    $this->climate->out('');
}

/**
 * Demander confirmation avant création
 */
private function askConfirmation(): bool
{
    $this->climate->yellow()->inline('❓ Créer ce projet ? [Y/n]: ');
    $response = trim(fgets(STDIN));
    return empty($response) || strtolower($response) === 'y';
}

/**
 * Exécuter la création du projet
 */
private function executeProjectCreation(array $analysis): void
{
    $projectName = $analysis['project_name'];
    
    try {
        $this->climate->out('');
        $this->climate->blue()->out('🚀 Création du projet: ' . $projectName);
        $this->climate->out('');
        
        // 1. Créer le dossier principal
        $this->createProjectDirectory($projectName);
        
        // 2. Initialiser Composer
        $this->initializeComposer($analysis);
        
        // 3. Installer les dépendances
        $this->installDependencies($analysis);
        
        // 4. Créer la structure
        $this->createProjectStructure($analysis);
        
        // 5. Générer les fichiers
        $this->generateProjectFiles($analysis);
        
        // 6. Finaliser
        $this->finalizeProject($analysis);
        
    } catch (\Exception $e) {
        $this->climate->error('❌ Erreur: ' . $e->getMessage());
    }
}

/**
 * Créer le dossier du projet
 */
private function createProjectDirectory(string $projectName): void
{
    if (!is_dir($projectName)) {
        mkdir($projectName, 0755, true);
        $this->climate->green()->out('✅ Dossier créé: ' . $projectName);
    }
    
    chdir($projectName);
    $this->currentPath = getcwd();
}

/**
 * Initialiser Composer
 */
private function initializeComposer(array $analysis): void
{
    $this->climate->yellow()->out('📦 Initialisation Composer...');
    
    $composerInit = sprintf(
        'composer init --name="%s" --type="project" --no-interaction',
        strtolower($analysis['project_name'])
    );
    
    $this->executeCommand($composerInit);
}

/**
 * Installer les dépendances
 */
private function installDependencies(array $analysis): void
{
    if (empty($analysis['libraries'])) return;
    
    $this->climate->yellow()->out('📚 Installation des bibliothèques...');
    
    foreach ($analysis['libraries'] as $library) {
        $this->climate->dim()->out('  → ' . $library);
        $this->executeCommand('composer require ' . $library, false);
    }
}

/**
 * Créer la structure de dossiers
 */
private function createProjectStructure(array $analysis): void
{
    $this->climate->yellow()->out('📁 Création de la structure...');
    
    foreach ($analysis['folders'] as $folder) {
        if (!is_dir($folder)) {
            mkdir($folder, 0755, true);
            $this->climate->green()->out('  ✅ ' . $folder);
        }
    }
}

/**
 * Générer les fichiers du projet
 */
private function generateProjectFiles(array $analysis): void
{
    $this->climate->yellow()->out('📄 Génération des fichiers...');
    
    // Générer selon le framework
    switch ($analysis['framework']) {
        case 'slim':
            $this->generateSlimFiles($analysis);
            break;
        case 'laravel':
            $this->generateLaravelFiles($analysis);
            break;
        case 'vanilla':
            $this->generateVanillaFiles($analysis);
            break;
        default:
            $this->generateGenericFiles($analysis);
    }
    
    // Fichiers communs
    $this->generateCommonFiles($analysis);
}

/**
 * Générer les fichiers Slim
 */
private function generateSlimFiles(array $analysis): void
{
    // public/index.php
    $indexContent = '<?php
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Factory\AppFactory;

require __DIR__ . "/../vendor/autoload.php";

$app = AppFactory::create();

// Middleware
$app->addBodyParsingMiddleware();
$app->addErrorMiddleware(true, true, true);

// Routes
$app->get("/", function (Request $request, Response $response) {
    $data = [
        "message" => "🚀 ' . $analysis['description'] . '",
        "framework" => "Slim 4",
        "timestamp" => date("c")
    ];
    
    $response->getBody()->write(json_encode($data, JSON_PRETTY_PRINT));
    return $response->withHeader("Content-Type", "application/json");
});

$app->get("/api/health", function (Request $request, Response $response) {
    $response->getBody()->write(json_encode(["status" => "OK"]));
    return $response->withHeader("Content-Type", "application/json");
});

$app->run();';

    file_put_contents('public/index.php', $indexContent);
    $this->climate->green()->out('  ✅ public/index.php');
}

/**
 * Générer les fichiers communs
 */
private function generateCommonFiles(array $analysis): void
{
    // .gitignore
    $gitignore = 'vendor/
.env
*.log
.DS_Store
Thumbs.db
composer.lock';
    
    file_put_contents('.gitignore', $gitignore);
    $this->climate->green()->out('  ✅ .gitignore');
    
    // README.md
    $readme = '# ' . ucfirst($analysis['project_name']) . '

' . $analysis['description'] . '

## Installation

```bash
composer install
```

## Démarrage

```bash
php -S localhost:8000 -t public
```

## Créé avec

- Framework: ' . ucfirst($analysis['framework']) . '
- Type: ' . ucfirst($analysis['project_type']) . '
- Assistant PHP AI 🤖

## Bibliothèques

' . implode("\n", array_map(fn($lib) => "- $lib", $analysis['libraries']));

    file_put_contents('README.md', $readme);
    $this->climate->green()->out('  ✅ README.md');
}

/**
 * Finaliser le projet
 */
private function finalizeProject(array $analysis): void
{
    $this->climate->out('');
    $this->climate->backgroundGreen()->black()->bold()->out(' 🎉 PROJET CRÉÉ AVEC SUCCÈS ! ');
    $this->climate->out('');
    
    $this->climate->green()->out('📁 Projet: ' . $analysis['project_name']);
    $this->climate->green()->out('🚀 Framework: ' . ucfirst($analysis['framework']));
    $this->climate->green()->out('📦 Type: ' . ucfirst($analysis['project_type']));
    
    $this->climate->out('');
    $this->climate->yellow()->out('💡 Prochaines étapes:');
    $this->climate->white()->out('   cd ' . $analysis['project_name']);
    $this->climate->white()->out('   php -S localhost:8000 -t public');
    $this->climate->out('');
    
    $this->climate->cyan()->out('🌐 Votre ' . $analysis['project_type'] . ' sera disponible sur: http://localhost:8000');
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

/**
 * Ajouter une bibliothèque à un projet existant
 */
private function addLibrary(string $instruction): void
{
    if (empty($instruction)) {
        $this->climate->error('❌ Usage: add "description de la fonctionnalité"');
        $this->climate->dim()->out('Exemples:');
        $this->climate->dim()->out('  add "authentification JWT"');
        $this->climate->dim()->out('  add "envoi d\'emails avec SwiftMailer"');
        return;
    }
    
    $this->climate->yellow()->out('🔍 Analyse de la demande...');
    
    // Analyser avec l'IA quoi ajouter
    $prompt = "Pour cette fonctionnalité PHP: \"$instruction\"
    
Retourne UNIQUEMENT un JSON avec:
{
    \"packages\": [\"composer packages à installer\"],
    \"description\": \"description courte\",
    \"files_to_create\": [\"fichiers à créer\"],
    \"instructions\": \"instructions courtes d'utilisation\"
}";
    
    try {
        $response = $this->ollama->ask($prompt);
        $analysis = json_decode($this->extractJsonFromResponse($response), true);
        
        if ($analysis && !empty($analysis['packages'])) {
            $this->climate->green()->out('📦 Installation: ' . implode(', ', $analysis['packages']));
            
            foreach ($analysis['packages'] as $package) {
                $this->executeCommand('composer require ' . $package);
            }
            
            $this->climate->green()->out('✅ Bibliothèques ajoutées !');
            
            if (!empty($analysis['instructions'])) {
                $this->climate->yellow()->out('💡 Instructions:');
                $this->climate->white()->out($analysis['instructions']);
            }
        }
        
    } catch (\Exception $e) {
        $this->climate->error('❌ Erreur: ' . $e->getMessage());
    }
}

/**
 * Générer des composants (scaffold)
 */
private function scaffoldComponent(string $instruction): void
{
    if (empty($instruction)) {
        $this->climate->error('❌ Usage: scaffold "type de composant"');
        $this->climate->dim()->out('Exemples:');
        $this->climate->dim()->out('  scaffold "UserController avec CRUD"');
        $this->climate->dim()->out('  scaffold "middleware d\'authentification"');
        return;
    }
    
    // Utiliser le système existant de génération de code
    $this->handleCreate($instruction);
}

/**
 * Suggérer des améliorations
 */
private function suggestImprovements(): void
{
    $this->climate->yellow()->out('🔍 Analyse du projet actuel...');
    
    $context = $this->detectProjectContext();
    
    $prompt = "Analyse ce projet PHP:
- Framework: {$context['framework']}
- Type: {$context['project_type']}
- Bibliothèques: " . implode(', ', $context['libraries']) . "

Suggère 5 améliorations concrètes et pratiques.";

    try {
        $suggestions = $this->ollama->ask($prompt);
        
        $this->climate->out('');
        $this->climate->backgroundYellow()->black()->bold()->out(' 💡 SUGGESTIONS D\'AMÉLIORATION ');
        $this->climate->out('');
        $this->climate->white()->out($suggestions);
        
    } catch (\Exception $e) {
        $this->climate->error('❌ Erreur: ' . $e->getMessage());
    }
}

/**
 * Détecter le contexte du projet actuel
 */
private function detectProjectContext(): array
{
    $context = [
        'framework' => 'unknown',
        'project_type' => 'unknown',
        'libraries' => []
    ];
    
    // Analyser composer.json
    if (file_exists('composer.json')) {
        $composer = json_decode(file_get_contents('composer.json'), true);
        
        foreach ($composer['require'] ?? [] as $package => $version) {
            $context['libraries'][] = $package;
            
            if (strpos($package, 'slim/') === 0) $context['framework'] = 'slim';
            if (strpos($package, 'laravel/') === 0) $context['framework'] = 'laravel';
            if (strpos($package, 'symfony/') === 0) $context['framework'] = 'symfony';
        }
    }
    
    // Détecter le type
    if (file_exists('public/index.php')) {
        $content = file_get_contents('public/index.php');
        if (strpos($content, 'api') !== false || strpos($content, 'json') !== false) {
            $context['project_type'] = 'api';
        } else {
            $context['project_type'] = 'web';
        }
    }
    
    return $context;
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
- \"créer une API Slim\" → create_project
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
    $createKeywords = ['créer', 'faire', 'nouveau', 'projet', 'api', 'application', 'site'];
    // Mots-clés pour ajouter quelque chose
    $addKeywords = ['ajouter', 'installer', 'intégrer', 'mettre'];
    // Mots-clés pour générer du code
    $generateKeywords = ['générer', 'controller', 'middleware', 'service', 'model'];
    // Mots-clés pour des questions
    $questionKeywords = ['comment', 'pourquoi', 'qu\'est-ce', 'quelle', 'quel', 'explain'];
    
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
    
    // Utiliser la logique existante mais automatiquement
    $analysis = $this->analyzeProjectInstruction($input);
    
    $this->climate->green()->out('📋 Projet détecté: ' . $analysis['description']);
    $this->climate->yellow()->out('⚡ Création automatique en cours...');
    $this->climate->out('');
    
    // Créer automatiquement sans demander confirmation
    $this->executeProjectCreation($analysis);
}

/**
 * Ajout autonome de fonctionnalité
 */
private function autonomousAddFeature(string $input): void
{
    $this->climate->blue()->out('📦 Ajout de fonctionnalité détecté !');
    
    if (!file_exists('composer.json')) {
        $this->climate->yellow()->out('⚠️  Aucun projet détecté. Création d\'un projet de base...');
        $this->autonomousCreateProject('API simple avec ' . $input);
        return;
    }
    
    // Analyser quoi ajouter
    $prompt = "L'utilisateur veut ajouter: \"$input\"

Retourne un JSON avec les packages Composer à installer:
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
        // Fallback: créer directement avec l'IA
        $this->generateCodeDirectly($input);
    }
}

/**
 * Modification autonome de fichier
 */
private function autonomousModifyFile(string $input): void
{
    $this->climate->blue()->out('✏️  Modification de fichier détectée !');
    
    // Analyser quel fichier modifier
    $prompt = "L'utilisateur veut: \"$input\"

Quel fichier doit être modifié ? Retourne: {\"file\": \"chemin/fichier.php\", \"action\": \"description\"}";

    try {
        $response = $this->ollama->ask($prompt);
        $analysis = json_decode($this->extractJsonFromResponse($response), true);
        
        if (!empty($analysis['file']) && file_exists($analysis['file'])) {
            $analyzer = new \AssistantPhp\Services\ProjectAnalyzer($this->currentPath);
            $fileManager = new \AssistantPhp\Services\FileManager($this->currentPath);
            $codeGenerator = new \AssistantPhp\Services\CodeGenerator($analyzer, $fileManager, $this->ollama);
            
            $result = $codeGenerator->editFile($analysis['file'], $input);
            
            if ($result['success']) {
                $this->climate->green()->out('✅ Fichier modifié: ' . $result['file_modified']);
            } else {
                $this->climate->error('❌ Erreur: ' . $result['error']);
            }
        } else {
            $this->autonomousAnswerQuestion($input);
        }
        
    } catch (\Exception $e) {
        $this->autonomousAnswerQuestion($input);
    }
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
            $result = $codeGenerator->analyzeFile($file);
            
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
    
    if (strpos($input, 'serveur') !== false || strpos($input, 'server') !== false || strpos($input, 'démarrer') !== false) {
        $this->climate->yellow()->out('🚀 Démarrage du serveur de développement...');
        
        if (file_exists('public/index.php')) {
            $this->climate->green()->out('✅ Serveur démarré sur http://localhost:8000');
            $this->climate->yellow()->out('💡 Appuyez sur Ctrl+C pour arrêter');
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
 * Trouver les fichiers PHP dans le projet
 */
private function findPhpFiles(): array
{
    $files = [];
    $iterator = new \RecursiveIteratorIterator(
        new \RecursiveDirectoryIterator($this->currentPath)
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
 * Générer du code directement avec l'IA
 */
private function generateCodeDirectly(string $input): void
{
    $prompt = "Génère le code PHP pour: \"$input\"

Inclus le chemin du fichier et le contenu complet.
Format: FICHIER: chemin/fichier.php
CONTENU:
```php
// code ici
```";

    try {
        $response = $this->ollama->ask($prompt);
        
        // Extraire fichier et contenu
        if (preg_match('/FICHIER:\s*(.+)\s*CONTENU:\s*```php\s*(.*?)```/s', $response, $matches)) {
            $filePath = trim($matches[1]);
            $content = trim($matches[2]);
            
            // Créer les dossiers si nécessaire
            $dir = dirname($filePath);
            if (!is_dir($dir)) {
                mkdir($dir, 0755, true);
            }
            
            file_put_contents($filePath, "<?php\n" . $content);
            $this->climate->green()->out('✅ Fichier créé: ' . $filePath);
        } else {
            $this->climate->yellow()->out('💬 Réponse de l\'IA:');
            $this->climate->white()->out($response);
        }
        
    } catch (\Exception $e) {
        $this->climate->error('❌ Erreur: ' . $e->getMessage());
    }
}

}
