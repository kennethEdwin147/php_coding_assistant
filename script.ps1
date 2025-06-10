# 1. Créer le dossier global
mkdir C:\Tools\AssistantPHP
copy .\* C:\Tools\AssistantPHP\

# 2. Ajouter au PATH (interface graphique)
# Win+R → sysdm.cpl → Avancé → Variables → Path → Nouveau → C:\Tools\AssistantPHP

composer install


C:\Tools\AssistantPHP\
├── assistant.php      # ← Votre script principal 

notepad $PROFILE

function ai { php "C:\Tools\AssistantPHP\assistant.php" @args }