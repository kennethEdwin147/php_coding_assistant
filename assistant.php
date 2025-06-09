#!/usr/bin/env php
<?php

// Vérifier que nous sommes en CLI
if (php_sapi_name() !== 'cli') {
    echo "Ce script doit être exécuté en ligne de commande.\n";
    exit(1);
}

// Charger l'autoloader
require_once __DIR__ . '/vendor/autoload.php';

use AssistantPhp\App;

try {
    $app = new App();
    $app->run($argv);
} catch (Exception $e) {
    echo "Erreur fatale: " . $e->getMessage() . "\n";
    exit(1);
}