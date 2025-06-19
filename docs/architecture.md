# Architecture du projet

Cette section présente l’organisation recommandée pour les fichiers du framework Impulse.

## Structure proposée

```
src/
├── Attributes/       # Attributs PHP personnalisés
├── Collections/      # Collections et helpers internes
├── Commands/         # Commandes de la CLI
├── Components/       # Exemples de composants
├── Core/             # Classes noyau (Component, State, ...)
├── Helpers/          # Fonctions utilitaires et assistants
├── Interfaces/       # Interfaces et contrats du framework
├── Rendering/        # Moteurs de rendu
```

Les fichiers frontaux TypeScript sont stockés dans le dossier `impulse/` puis
transpilés vers `public/`.

La configuration du framework est centralisée dans `.impulse/config.json` et
peut être manipulée via la classe `\Impulse\Config`.

## Utilisation de `Config`

```php
use Impulse\Core\Config;

Config::load();
$engine = Config::get('template_engine');
```

La commande `renderer:configure` met à jour ce fichier automatiquement.
