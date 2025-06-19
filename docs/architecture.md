# Architecture du projet

Cette section présente l’organisation recommandée pour les fichiers du framework Impulse.

## Structure proposée

```
src/
├── Config.php        # Gestion centralisée de la configuration
├── Attributes/       # Attributs PHP personnalisés
├── Collections/      # Collections et helpers internes
├── Commands/         # Commandes de la CLI
├── Core/             # Classes noyau (Component, State, ...)
├── Rendering/        # Moteurs de rendu
└── Components/       # Exemples de composants
```

Les fichiers frontaux TypeScript sont stockés dans le dossier `impulse/` puis
transpilés vers `public/`.

La configuration du framework est centralisée dans `.impulse/config.json` et
peut être manipulée via la classe `\Impulse\Config`.

## Utilisation de `Config`

```php
use Impulse\Config;

Config::load();
$engine = Config::get('template_engine');
```

La commande `renderer:configure` met à jour ce fichier automatiquement.
