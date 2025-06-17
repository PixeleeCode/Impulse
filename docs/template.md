# Moteurs de template dans Impulse

Impulse offre une gestion flexible de la présentation grâce à un système modulaire de moteurs de rendu. Que tu 
utilises Twig, Blade, ou aucun moteur du tout, tu peux adapter l'affichage de tes composants à tes besoins, ou même 
développer ton propre moteur personnalisé.

Impulse prend actuellement en charge :
* Twig
* Blade
* Aucun moteur (retour de HTML direct dans la méthode `template()`)
* Moteurs personnalisés via l’attribut `#[Renderer]`

---

## Table des matières

- [Principe général](#principe-général)
- [Installer un moteur de template](#installer-un-moteur-de-template)
- [Structure de configuration](#structure-de-configuration)
- [Utiliser les vues dans un composant](#utiliser-les-vues-dans-un-composant)
- [Ajouter un moteur personnalisé](#ajouter-un-moteur-personnalisé)
- [Erreurs fréquentes](#erreurs-fréquentes)
- [Bonnes pratiques](#bonnes-pratiques)

---

## Principe général

Impulse permet de séparer le code métier et la présentation grâce à un système flexible de moteurs de template.

Par défaut, les composants peuvent retourner du HTML directement via la méthode `template()`, mais il est également 
possible d’utiliser un moteur de rendu comme **Twig**, **Blade**, ou un moteur personnalisé grâce au système d’attributs `#[Renderer]`.

Le moteur sélectionné permet de rendre un fichier `.blade.php`, `.twig`, ou autre, situé dans un dossier 
(par défaut `resources/views`) avec les données fournies.

Voici les trois approches disponibles :

1. **Aucun moteur** – Code HTML écrit manuellement dans `template()`
2. **Moteur intégré** – Twig ou Blade avec rendu automatique
3. **Moteur personnalisé** – Via une classe annotée et enregistrée par l’utilisateur

Le moteur actif est déterminé lors de l’exécution de la commande `renderer:configure`, et stocké dans `.impulse/config.json`.


---

## Installer un moteur de template

Dans ta console, lance la commande :
```shell
php bin/impule renderer:configure
```

Tu obtiendras une liste interactive des moteurs disponibles automatiquement, ex. :
```shell
[Impulse] Quel moteur de template souhaitez-vous utiliser ?
[0] Aucun
[1] Twig
[2] Blade

[Impulse] Où se trouvent vos templates ? [resources/views]
```

👉 Si tu choisis un moteur, il sera :
* **enregistré** dans `.impulse/config.json`
* **installé** via `composer require` s’il n’est pas déjà présent

---

## Structure de configuration

Un fichier `.impulse/config.json` sera généré :

```json
{
  "template_engine": "blade",
  "template_path": "resources/views"
}
```

* "**twig**" ⇒ Utilisation de `TwigRenderer`
* "**blade**" ⇒ Utilisation de `BladeRenderer`
* "" ou "**none**" ⇒ Aucun moteur (HTML inline via `template()`)

---

## Utiliser les vues dans un composant

### Méthode 1 – HTML inline (sans moteur)

```php
public function template(): string
{
    return <<<HTML
    <p>Hello inline</p>
    HTML;
}
```

### Méthode 2 – Vue moteur (Blade ou Twig)

Vous pouvez appeler un template dans un dossier comme le permet Twig ou Blade. Il te suffit de suivre
la directive de **la documentation officielle** à ce sujet.

```php
public function template(): string
{
    return $this->view('hello', [
        'user' => 'Guillaume'
    ]);
}
```

Le fichier attendu :
* `resources/views/hello.blade.php` (Blade)
* `resources/views/hello.twig` (Twig)

---

## Ajouter un moteur personnalisé

### Créer le template

Dans ta console, tapes :
```shell
php bin/impulse make:renderer
```

Donne le nom de ton fichier en répondant à la question posé dans la console.

**Exemple :**
```shell
[Impulse] Nom du renderer (ex: MustacheRenderer) : Mustache
[Impulse] 🎉 Renderer MustacheRenderer créé avec succès : src/Rendering/MustacheRenderer.php
```

Cela aura pour but de créer ton fichier directement dans le dossier `src/Renderer`.
```php
<?php

namespace Impulse\Rendering;

use Impulse\Attributes\Renderer;
use Impulse\Interfaces\TemplateRendererInterface;

#[Renderer(
    name: 'MustacheRenderer',
    bundle: 'MustacheRenderer/MustacheRenderer'
)]
class MustacheRenderer implements TemplateRendererInterface
{
    public function __construct(string $viewsPath = '')
    {
        // "$viewPath" contient le chemin vers le dossier des templates
    }
    
    public function render(string $template, array $data = []): string
    {
        // Implémenter le rendu ici
        return '';
    }
}
```

### Installe le bundle

Il sera automatiquement installé via la commande `php bin/impulse renderer:configure`, grâce à l'attribut :

```php
#[Renderer(
    name: 'mustache', 
    bundle: 'mustache/mustache:^2.14'
)]
```

> L'argument `bundle` n'est pas obligatoire si jamais tu veux gérer tout le moteur dans le renderer ou ailleurs.

### Re-lancer `php bin/impulse renderer:configure`

Ton moteur **apparaîtra automatiquement dans la liste** des moteurs détectés.

### Détail technique : comment cela fonctionne ?

* Chaque `*Renderer.php` dans `src/Rendering/` est inspecté.
* Si la classe contient l’attribut `#[Renderer(name: ..., bundle: ...)]`, elle est ajoutée à la liste.
* La classe doit implémenter `TemplateRendererInterface` :

```php
interface TemplateRendererInterface
{
    public function render(string $template, array $data = []): string;
}
```

---

## Erreurs fréquentes
| Erreur                                                        | Cause                                                    | Solution                                                                                    |
|---------------------------------------------------------------|----------------------------------------------------------|---------------------------------------------------------------------------------------------|
| `RuntimeException: Aucun moteur défini`                       | Tu appelles `$this->view()` sans moteur actif            | Soit configure un moteur avec `renderer:configure`, soit retourne du HTML dans `template()` |
| `InvalidArgumentException: View [hello] not found.`           | Vue introuvable                                          | Vérifie le nom et l’emplacement du fichier dans `resources/views`                           |
| Ton moteur personnalisé n’apparaît pas dans la liste proposée | Pas d’attribut `#[Renderer(...)]` ou namespace incorrect | Vérifie que le fichier est dans `Impulse\Rendering` et que l'attribut est bien défini       |

---

## Bonnes pratiques

* Toujours utiliser `str_replace(['.php', '.blade.php'], '', $template)` pour nettoyer les noms de fichiers
* Normaliser les chemins si besoin (`str_replace('\\', '.', $template)`)
* Stocker les vues dans `resources/views/` ou dans le dossier choisi au moment de la configuration
* Ajouter un cache (ex: `var/storage/cache/tonmoteur/`) si le moteur le permet
