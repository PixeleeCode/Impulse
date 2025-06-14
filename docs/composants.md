# Créer un composant

Impulse permet de créer des composants réactifs en PHP, faciles à intégrer dans vos applications. Un composant encapsule **état, logique et rendu** : tout se passe côté serveur, avec une synchronisation automatique côté navigateur.

---

## Table des matières

- [Principe général](#principe-général)
- [Structure d’un composant](#structure-dun-composant)
- [Créer un composant pas à pas](#créer-un-composant-pas-à-pas)
- [Déclarer les méthodes/actions](#déclarer-les-méthodesactions)
- [Instancier et afficher un composant](#instancier-et-afficher-un-composant)
- [Lier le composant à l’interface](#lier-le-composant-à-linterface)
- [Fragments et rendu partiel](#fragments-et-rendu-partiel)
- [Bonnes pratiques](#bonnes-pratiques)
- [Aller plus loin](#aller-plus-loin)

---

## Principe général

Un composant Impulse est une **classe PHP** qui gère :
- Un **état** (`state`) : données persistantes (ex. : compteur, saisi utilisateur…) → persistant côté serveur
- Des **méthodes** associées à des actions utilisateur (`click`, `input`, etc.) → appelées par le navigateur à chaque interaction
- Un **template** qui définit le rendu HTML → automatiquement synchronisé dans le navigateur

La logique de votre composant est **centralisée en PHP** :  
l’utilisateur interagit (clique, tape du texte…), Impulse envoie l’action au serveur, la méthode PHP est appelée, et la vue est mise à jour instantanément côté navigateur.

---

## Structure d’un composant

Un composant **hérite toujours de `Impulse\Core\Component`**.

### Exemple minimal :

```php
<?php

namespace MyComponents;

use Impulse\Core\Component;

class Counter extends Component
{
    public function setup(): void
    {
        // Déclaration de l'état "count" avec valeur par défaut 0
        $this->state('count', 0);
    }

    #[ImpulseAction]
    public function increment(): void
    {
        $this->count += + 1;
    }

    #[ImpulseAction]
    public function reset(): void
    {
        $this->count = 0;
    }

    public function template(): string
    {
        return <<<HTML
            <div>
                <button impulse:click="increment">+1</button>
                <span>{$this->count}</span>
                <button impulse:click="reset">Reset</button>
            </div>
        HTML;
    }
}
```

---

## Créer un composant pas à pas

### 1. Créer la classe
   * Place le fichier dans src/Components/ (par convention)
   * Nomme la classe selon le composant (Counter, TodoList, ColorPicker…)

### 2. Déclarer l’état
Utilise `$this->state('nom', valeurParDéfaut)` dans la méthode `setup()`.

Exemple :
```php
$this->state('name', '');
```
L’état est automatiquement persistant : la valeur est retenue entre deux actions.

### 3. Enregistrer les méthodes/actions

Créer autant de méthodes publiques que tu as besoin toutes surmontées de l'attribut PHP `#[ImpulseAction]`.  
**Sans cet attribut, elles ne seront pas exposées et donc non utilisable par le composant.**

```php
#[ImpulseAction]
public function increment(): void
{
    $this->count += 1;
}

#[ImpulseAction]
public function reset(): void
{
    $this->count = 0;
}
```

### Autres possibilités

Dans `setup()`, lie tes actions (méthodes) :
```php
public function setup(): void
{
    $count = $this->state('count', 0);

    $this->methods->register('increment', fn() => $count->set($count->get() + 1));
    $this->methods->register('reset', fn() => $count->set(0));
}
```
Chaque méthode sera déclenchée par un événement utilisateur.

### 4. Rendre le composant (template())

Crée le HTML de ton composant :
* Utilise des attributs `impulse:*` sur les boutons, inputs, etc.
* Injecte l’état avec `$this->compteur` ou similaire.

---

## Déclarer les méthodes/actions

### Enregistrer une action
```php
#[ImpulseAction]
public function setName(string $name): void
{
    $this->name = $name;
}
```
Ou dans `setup()`
```php
$this->methods->register('setName', fn(string $name) => $state->set($name));
```

### Utiliser dans le template
```html
<input impulse:input="setName">
<button impulse:click="increment">+1</button>
```

### Passer des arguments

Tu peux envoyer des valeurs :
```html
<button impulse:click="increment(10)">+10</button>
```
Et dans le PHP du composant :
```php
#[ImpulseAction]
public function increment(int $number): void
{
    $this->count += $number;
}
```
Ou dans le `setup()`
```php
public function setup(): void
{
    $count = $this->state('count', 0);
    $this->methods->register('increment', fn(int $number) => $count->set($count->get() + $number));
}
```

---

## Instancier et afficher un composant

En PHP, dans ton contrôleur ou ta page :
```php
use Impulse\ImpulseFactory;
use MyComponents\Counter;

$counter = ImpulseFactory::create(Counter::class, ['count' => 10]); // valeur initiale 10
echo $counter->render(); // ⚠️ Toujours utiliser render(), pas template()
```
> Le tableau passé en second paramètre permet de fixer des valeurs par défaut (état initial).
---

## Lier le composant à l’interface

Impulse détecte automatiquement les attributs :

* `impulse:click="increment"`
* `impulse:input="setName"`
* `impulse:change="selectOption"`
* etc.

Chaque événement du DOM envoie une requête AJAX au serveur, la méthode PHP correspondante est appelée et l’état
(et le HTML) sont mis à jour automatiquement côté navigateur.

---

## Fragments et rendu partiel

Pour **mettre à jour seulement une partie du composant** (optimisation), utilise l’attribut `data-impulse-update` :
```html
<span data-impulse-part="preview">{$count}</span>
```
Puis sur l’input ou le bouton :
```html
<input impulse:input="setCount" impulse:update="preview">
```

Ainsi, seul le fragment nommé `preview` sera remplacé lors de la mise à jour, pas tout le composant.
Idéal pour les formulaires, les listes, les compteurs, etc.

> **Pas de rendu partiel sur l'événement `impulse:emit`**

| Attribut                   |                           Rôle                           |
|:---------------------------|:--------------------------------------------------------:|
| impulse:update="name"      |     DEMANDE au backend de rafraîchir la zone “name”      |
| data-impulse-update="name" | INDIQUE dans le DOM que cette balise correspond à “name” |

---

## Bonnes pratiques

* **Une méthode = une action utilisateur** (évite les méthodes trop générales)
* Utilise `data-impulse-data` pour éviter de recharger tout le DOM
* Ne code **aucune logique métier** côté JS : tout doit rester en PHP pour rester maintenable
* **Nomme clairement tes méthodes** (ex : increment, reset, setName…)
* Si besoin, factorise les méthodes utilitaires dans des traits ou classes communes

---

## Aller plus loin

- [Gérer plusieurs états](states.md)
- [Écouter/émettre des événements entre composants (emit)](emit.md)
