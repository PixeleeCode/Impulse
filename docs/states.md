# Gérer l’état (state) dans les composants

L’état est le cœur d’un composant Impulse : il permet de mémoriser des valeurs, de réagir aux actions utilisateur,
et de synchroniser l’interface en temps réel entre PHP et le navigateur.

---

## Table des matières

- [Principe général](#principe-général)
- [Déclarer un state dans un composant](#déclarer-un-state-dans-un-composant)
- [Accéder et modifier la valeur du state](#accéder-et-modifier-la-valeur-du-state)
- [Protéger la valeur du state](#protéger-la-valeur-du-state)
- [Persistance et cycle de vie](#persistance-et-cycle-de-vie)
- [Passer des valeurs par défaut](#passer-des-valeurs-par-défaut)
- [Récupérer tous les states d’un composant](#récupérer-tous-les-states-d-un-composant)
- [Bonnes pratiques](#bonnes-pratiques)

---

## Principe général

Chaque composant possède un ou plusieurs **states** :  
Ce sont des objets “mémoire” qui stockent la valeur courante d’une donnée (ex : compteur, texte saisi, couleur sélectionnée…).
* **Le state se met à jour en PHP**, et le frontend se synchronise automatiquement (DOM mis à jour).
* Le state **vit aussi longtemps que le composant** (même après plusieurs actions AJAX).

---

## Déclarer un state dans un composant

Pour créer un state :  
Utilise `$this->state('nom', valeurParDéfaut)` dans la méthode `setup() de ton composant.

```php
public function setup(): void
{
    // Un compteur initialisé à 10
    $count = $this->state('count', 10);
}
```

* 'count' est le nom du state (clé unique dans le composant)
* 10 est la valeur par défaut (utilisée à la première création)

> **Astuce** : le state est automatiquement accessible en propriété `$this->count`.

---

## Accéder et modifier la valeur du state

**Lire la valeur**
```php
$count = $this->state('count', 0);
$valeur = $count->get(); // Récupère la valeur actuelle

// ou directement via la propriété magique :
$valeur = $this->count;
```

**Modifier la valeur**
```php
$count->set($count->get() + 1); // Incrémenter

// Ou (équivalent)
$this->count += 1;
```
> **Remarque** : Utilise toujours `set` pour déclencher la synchronisation.

---

## Protéger la valeur du state

Les valeurs des states sont stockées côté serveur, mais elles sont également affichées dans le DOM 
(attribut `data-states` sur le composant) pour permettre au JS de synchroniser l’interface avec le backend.  
**Ceci implique que toute donnée stockée dans un state "public" sera visible par n’importe quel utilisateur via 
l’inspecteur de son navigateur.**

### Pourquoi protéger un state ?

Dans certains cas, tu peux vouloir masquer la vraie valeur d’un state côté client (par exemple : pour empêcher 
qu’un utilisateur ne voie ou modifie une valeur sensible dans le DOM, comme un identifiant secret, un token, une 
information confidentielle, etc.).  
La "protection" consiste à **remplacer la valeur réelle par un hash** dans le DOM : ainsi, la donnée reste utilisable 
normalement côté PHP, mais elle n’est jamais visible dans la page côté navigateur.

### Attention : ce que la protection ne fait PAS

* **Un state protégé n’est JAMAIS synchronisé côté client** : le JS ne peut pas l’utiliser pour piloter un champ de formulaire, faire de l’autocomplétion, ou autre synchronisation côté client.
* **Ne pas utiliser la protection** pour des states qui doivent pouvoir être modifiés par l’utilisateur via le JS (ex : champs de formulaire, compteurs, champs synchronisés…).
* **La protection ne remplace pas une vraie sécurité** : c’est juste un masquage pour le DOM, cela n’empêche pas le serveur de devoir vérifier les droits ou l’intégrité côté backend.

### Exemples d’utilisation

**Cas où il FAUT protéger :**
* Un token d’accès temporaire affiché dans le DOM juste pour le backend ;
* Un identifiant d’utilisateur caché pour retrouver l’utilisateur courant côté PHP ;
* Un numéro sensible ou secret qui ne doit pas être manipulé côté JS, mais seulement servir au backend pour effectuer un traitement (ex : un ID bancaire, un code d’accès unique, etc.).

**Cas où il NE FAUT PAS protéger :**
* La valeur d’un champ de formulaire éditable par l’utilisateur (input, textarea…) ;
* Un compteur, une couleur sélectionnée, un texte, ou tout état manipulé via le JS et devant être synchronisé entre le frontend et le backend ;
* Toute donnée devant être affichée en clair ou modifiée dans l’interface utilisateur.

### Exemple de code

```php
// Déclare un state protégé
$token = $this->state('token', $valeurInitiale, protected: true);

// Déclare un state public (par défaut)
$count = $this->state('count', 0); // Pour un compteur, NE PAS protéger !
```

Dans le composant :
```php
// Utilisation côté backend (toujours valeur réelle)
$token->get(); // => "e5f7..."

// Dans le DOM, data-states contiendra le hash et non la vraie valeur
// data-states="{ "token": "5d41402abc4b2a76b9719d911017c592" }"
```

---

## Persistance et cycle de vie

Le state d’un composant est **persisté** tant que le composant existe (dans la collection interne d’Impulse).  
**Chaque interaction AJAX** remet à jour la valeur côté serveur, puis côté navigateur.  
**À la fermeture ou rechargement complet de la page**, l’état est réinitialisé (sauf si tu implémentes une persistance 
manuelle dans ta base de données).

---

## Passer des valeurs par défaut

Tu peux passer une valeur par défaut (venant d’une BDD, par exemple) lors de la création du composant :
```php
use Impulse\ImpulseFactory;
$counter = ImpulseFactory::create(MyComponents\Counter::class, [
    'count' => 42, // valeur initiale
]);
echo $counter->render();
```
> Cela préremplit le state à la première instanciation.

---

## Récupérer tous les states d’un composant

Impulse te permet de récupérer tous les states courants d’un composant :
```php
$states = $counter->getStates();
// $states = ['count' => 17, 'name' => 'Jean', ...]
```
Utile si tu veux sauvegarder des données côté développeur (en BDD, etc.).

---

## Bonnes pratiques
* **Déclare tes states dans `setup()`** : c’est le point d’entrée officiel.
* Utilise des **noms courts et clairs** pour chaque state.
* **N’utilise pas de variable globale ou de $_SESSION** : tout se fait par la collection d’états interne.
* Préfère **une méthode par type d’action** qui modifie l’état (ex : increment, reset, setName, etc.)
* Si tu as besoin de persister l’état **entre plusieurs sessions** ou utilisateurs, sauvegarde-le manuellement en BDD via `$this->getStates()`.

---

## Exemple récapitulatif

```php
class Counter extends Component
{
    public function setup(): void
    {
        $this->state('count', 0);
    }

    #[Action]
    public function increment(): void
    {
        $this->count += + 1;
    }

    public function template(): string
    {
        return <<<HTML
            <div>
                <button impulse:click="increment">+1</button>
                <span>{$this->count}</span>
            </div>
        HTML;
    }
}
```
