# Gérer les événements entre composants (emit)

Impulse permet aux composants de communiquer entre eux via un système d’événements réactifs : un composant peut
“émettre” un événement (`emit`), et d’autres composants peuvent "écouter" et réagir à cet événement.  
C’est l’un des principes clés pour rendre une interface vraiment interactive… sans complexité côté frontend.

---

## Table des matières

- [Principe général](#principe-général)
- [Pourquoi utiliser des événements ?](#pourquoi-utiliser-des-événements-)
- [Émettre un événement (`emit`)](#émettre-un-événement-emit)
- [Écouter un événement dans un composant (`onEvent`)](#écouter-un-événement-dans-un-composant-onevent)
- [Mettre à jour un composant sur réception d’un événement](#mettre-à-jour-un-composant-sur-réception-dun-événement)
- [Exemple complet](#exemple-complet)
- [Gérer les emits côté JavaScript (interop PHP/JS)](#gérer-les-emits-côté-javascript-interop-phpjs)
- [Questions fréquentes](#questions-fréquentes)
- [Bonnes pratiques](#bonnes-pratiques)

---

## Principe général

* **Émettre un événement** : Un composant déclenche une action qui va “notifier” d’autres composants de l’application.
* **Écouter un événement** : Un ou plusieurs composants peuvent réagir à cette notification, par exemple mettre à jour leur état ou leur affichage.

Tout se fait sans JS personnalisé ni manipulation manuelle des IDs, grâce à l’attribut HTML `impulse:emit`.

---

## Pourquoi utiliser des événements ?

- Mettre à jour plusieurs composants à la fois lors d’une action utilisateur.
- Synchroniser des valeurs entre des composants indépendants (ex : recherche ↔ résultats).
- Déclencher une modale, une alerte, ou toute action transversale.
- Gérer des workflows complexes sans créer de dépendances directes entre composants.

---

## Émettre un événement (`emit`)

Dans ton template, tu utilises simplement l’attribut `impulse:emit` :

### Exemple basique
```html
<button impulse:emit="userCreated">Créer un utilisateur</button>
```
Ici, un clic sur le bouton émet l’événement userCreated pour tous les composants à l’écoute.

### Exemple avec données en argument de la fonction
```html
<button impulse:emit="userCreated('Jean', 25)">Créer Jean</button>
```
Ici, on transmet en plus deux valeurs ('Jean', 25) aux composants qui écoutent l’événement.

### Exemple avec payload complexe (impulse:payload)

Si tu veux transmettre un objet complet ou plusieurs données sans utiliser la syntaxe d’appel de fonction(`emit("event(a, b)")`), 
tu peux utiliser l’attribut `impulse:payload`.  
Cet attribut attend une chaîne JSON valide.

```html
<button impulse:emit="userCreated" impulse:payload='{"name":"Jean", "age":30}'>
    Créer Jean
</button>
```

Dans ce cas, côté PHP, tu récupéreras tes données dans $payload['payload']:

---

## Écouter un événement dans un composant (`onEvent`)

Côté PHP, il te suffit d’implémenter la méthode `onEvent` dans le composant qui doit réagir :
```php
public function onEvent(string $event, array $payload): ?bool
{
    if ($event === 'userCreated') {
        $name = $payload['args'][0] ?? '';
        $age  = $payload['args'][1] ?? null;
        
        // Traitement...
        
        $this->state('lastUser', '')->set($name);
        
        return true; // Pour demander le refresh du composant
    }
    
    return null;
}
```

* `$event` contient le nom de l’événement (ex : `userCreated`)
* `$payload['args']` contient les arguments transmis (ex : ['Jean', 25]) passé en arguments de la fonction
* Retourner `true` force le rafraîchissement du composant après traitement.

Si tu veux récupérer les données passées par `impulse:payload` comme ceci : `impulse:payload='{"name":"Jean", "age":30}'` :
```php
public function onEvent(string $event, array $payload): ?bool
{
    if ($event === 'userCreated') {
        $name = $payload['payload']['name'] ?? '';
        $age  = $payload['payload']['age'] ?? null;
        
        // Traitement...
        
        return true;
    }
    
    return null;
}
```

---

## Mettre à jour un composant sur réception d’un événement

L’attribut `impulse:emit permet de passer un ou plusieurs arguments :
```html
<button impulse:emit="messageSent('Coucou', 42)">Envoyer</button>
```

**Côté PHP**, tu récupères via `$payload['args']` :
* `$payload['args'][0]` → 'Coucou'
* `$payload['args'][1]` → 42

---

## Exemple complet

### 1. Composant Sender (qui émet l’événement)
```php
class Sender extends Component
{
    public function setup(): void
    {
        // ...
    }

    public function template(): string
    {
        return <<<HTML
            <button impulse:emit="messageSent('Salut à tous !')">
                Envoyer un message
            </button>
        HTML;
    }
}
```

### 2. Composant Receiver (qui écoute et réagit)
```php
class Receiver extends Component
{
    public function setup(): void
    {
        $this->state('lastMessage', '');
    }

    public function onEvent(string $event, array $payload): ?bool
    {
        if ($event === 'messageSent') {
            $message = $payload['args'][0] ?? '';
            $this->state('lastMessage')->set($message);
            
            return true; // Rafraîchir le composant
        }
        
        return null;
    }

    public function template(): string
    {
        return "<p>Dernier message : {$this->lastMessage}</p>";
    }
}
```

---

## Gérer les emits côté JavaScript (interop PHP/JS)

Impulse permet d'écouter tous les événements émis (`emit`) non seulement côté PHP (avec la méthode `onEvent`), mais 
aussi côté JavaScript, pour des intégrations avancées : notifications, animations, traitements asynchrones, etc.

### 1. Écouter tous les emits via un event global

Impulse déclenche automatiquement un événement natif `impulse:emit` sur `document` à chaque fois qu’un événement est 
émis, qu'il soit déclenché depuis le HTML (`impulse:emit="..."`) ou depuis JS (`Impulse.emit`).

**Exemple :**
```js
document.addEventListener('impulse:emit', function(e) {
    // e.detail contient { event, payload, ... }
    const { event, payload } = e.detail;
    if (event === 'userCreated') {
        alert("Un utilisateur a été créé ! " + JSON.stringify(payload));
        // Tu peux ici déclencher une animation, une notification, etc.
    }
});
```

### 2. Émettre un événement depuis JavaScript

Tu peux aussi déclencher un `emit` manuellement depuis JS pour informer tous tes composants (et/ou JS) d’un changement.

**Exemple :**
```js
Impulse.emit('searchUpdated', { search: 'café' });
// → Tous les composants avec un onEvent('searchUpdated') seront notifiés
```

**Avec callback de résultat :**
```js
Impulse.emit('userSaved', { id: 123 }, {
  callback: (result) => {
    // result = retour du serveur après l’emit (optionnel)
    console.log('Serveur a répondu:', result);
  }
});
```

### 3. Gestion avancée : cibler certains composants

Tu peux demander à ce que l’événement ne cible qu’un ou plusieurs composants particuliers (par leurs IDs) :
```js
Impulse.emit('focusField', { field: 'search' }, { components: 'searchbar_1' });
```

### Récapitulatif JS
* **Emission** : `Impulse.emit('eventName', payload[, options])`
* **Écoute** : `document.addEventListener('impulse:emit', callback)`
* **Callback** : `options.callback` appelé après retour du serveur
* **Ciblage** : `options.components` pour n’émettre que sur certains composants

_N’hésite pas à mixer PHP et JS selon les besoins de ton projet. Cette interopérabilité est l’un des atouts majeurs
d’Impulse._

---

## Questions fréquentes

### Est-ce que plusieurs composants peuvent écouter le même événement ?
> Oui, tous les composants de la page qui implémentent onEvent pour ce nom d’événement réagiront.

### Est-ce qu’on peut émettre un événement depuis un input, un select, etc. ?
>Oui, impulse:emit fonctionne sur n’importe quel élément HTML (input, select, button…).

### Est-ce qu’on peut émettre plusieurs fois de suite ?
> Oui, chaque action déclenche l’événement sans limites.

---

## Bonnes pratiques

* Privilégie des noms d’événements clairs (userCreated, searchUpdated, messageSent…)
* Utilise `impulse:emit` pour toutes les interactions transversales (et non pour du simple binding local)
* Toujours **retourner true dans onEvent** si tu veux rafraîchir le composant automatiquement après traitement.
