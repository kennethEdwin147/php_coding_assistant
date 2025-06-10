# 1. Créer le dossier global
# Copier TOUT vers le dossier global
robocopy . C:\Tools\AssistantPHP /E /XD .git node_modules

# Aller dans le dossier global et réinstaller
cd C:\Tools\AssistantPHP
composer install


composer install


C:\Tools\AssistantPHP\
├── assistant.php      # ← Votre script principal 

notepad $PROFILE

function ai { php "C:\Tools\AssistantPHP\assistant.php" @args }