# ⚡️ Impulse

Impulse est un micro-framework PHP/JS réactif pour créer des composants dynamiques et interactifs… sans sacrifier
la simplicité !
Il permet de manipuler l’interface utilisateur **en temps réel** côté navigateur, via des fragments de DOM retournés
en AJAX, tout en gardant la logique métier en PHP pur.

---

## 🚀 Fonctionnement

- **Composant PHP** : Vous codez la logique côté backend (état, méthodes, rendu HTML).
- **Composant JS** : Vous ajoutez des attributs impulse:\* (impulse:input, impulse:click…) pour déclencher les updates.
- **Rendu partiel** : Seule la partie du composant concernée (data-impulse-part) est remplacée dans le DOM, pour une expérience ultra-fluide.
- **Moteur de template flexible** : Utilisez du HTML inline ou configurez un moteur comme Twig, Blade ou le vôtre en quelques secondes.

---

## ✨ Exemple de composant

```php
namespace MyComponents;

use Impulse\Core\Component;

/**
 * @property string $name 
 */
final class HelloUser extends Component
{
    public function setup(): void
    {
        $this->state('name', '');
    }
    
    #[Action]
    public function setName(string $name): void
    {
        $this->name = $name;
    }

    public function template(): string
    {
        $name = htmlspecialchars($this->name);

        return <<<HTML
            <div>
                <input type="text" impulse:input="setName" data-partial="preview" placeholder="Votre prénom..." />
                <p>Bonjour <strong data-impulse-part="preview">$name</strong></p>
            </div>
        HTML;
    }
}
```

---

## ⚡️ Points forts

* 🔬 Ultra léger : Pas de Virtual DOM ni dépendance frontend lourde.
* ⚡️ Ultra réactif : Seuls les fragments nécessaires sont mis à jour.
* 🧑‍💻 Dév ultra simple : On code du PHP, et ça marche… tout simplement.
* 🎯 Moderne : Fonctionne avec PHP 8+, ES2020+.

---

## ⚡️ Installation

### 1. Installer la librairie :
```bash
composer require pixelee/impulse
```

### 2. Ajoute les fichiers publics JS/CSS dans ton dossier public
Créez des liens symboliques du JS et du endpoint `impulse.php` dans votre dossier public :
```bash
ln -s ../vendor/pixelee/impulse/public/impulse.js public/impulse.js
ln -s ../vendor/pixelee/impulse/public/impulse.php public/impulse.php
````

> Astuce : Ce lien symbolique vous permet de profiter automatiquement des mises à jour via composer update,
> sans avoir à recopier le fichier.

Ajoutez le script JS dans vos layouts :
```html
<script src="/public/impulse.js" defer></script>
```

### 3. (Optionnel) Configurer un moteur de template

Impulse permet d’utiliser un moteur externe comme Twig ou Blade. Pour cela :

```bash
php bin/impulse renderer:configure
```

Vous pourrez choisir le moteur souhaité et son installation sera automatisée.  
Vous pouvez aussi créer votre propre moteur avec :

```bash
php bin/impulse make:renderer
```

Voir [la documentation](docs/template.md) pour plus de détails.  
La structure recommandée du projet est décrite dans [docs/architecture.md](docs/architecture.md).

### 4. Déclare tes composants
Place les composants dans `src/Components/`, (ou autres, mais adaptez le namespace).

```php
use Impulse\ImpulseFactory;
use MyComponents\HelloUser;

$helloUser = ImpulseFactory::create(HelloUser::class, ['name' => 'John']);
echo $helloUser->render();
```

---

## 📚 Pour les développeurs

Voir [docs/SUMMARY.md](docs/SUMMARY.md) pour apprendre à créer vos propres composants, utiliser les events `impulse:*`, et faire du rendu partiel proprement.

<p align="center">
  <img src="https://img.shields.io/badge/Impulse-php--js%20reactif-06b6d4?style=for-the-badge&logo=thunder-cloud&logoColor=white" alt="Impulse badge"/>
</p>
