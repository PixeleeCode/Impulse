# Gérer le cycle de vie d’un composant : onBeforeAction et onAfterAction

Impulse te permet d’exécuter du code **avant** et **après** l’exécution d’une action utilisateur (méthode appelée via AJAX), 
grâce à deux méthodes spéciales :
* `onBeforeAction(string $method, array $args): void`
* `onAfterAction(string $method, array $args): void`

---

## Table des matières

- [Principe général](#principe-général)
- [Exemples d’utilisation](#exemples-d-utilisation)
- [Comportement par défaut](#comportement-par-défaut)
- [Bonnes pratiques](#bonnes-pratiques)

---

## Principe général

**onBeforeAction**  
Exécute du code juste avant la méthode appelée par l’utilisateur (ex: vérifier des droits, valider un état, logger…).  

**onAfterAction**  
Exécute du code juste après la méthode appelée (ex: nettoyer une variable, synchroniser une donnée, déclencher un autre
événement, log, etc.).

Cela permet de centraliser des comportements communs sans alourdir chaque méthode du composant.

---

## Exemple d’utilisation

```php
use Impulse\Component;

class HelloUser extends Component
{
    // ...

    public function onBeforeAction(string $method, array $args): void
    {
        // Log ou vérification avant action
        error_log("About to execute $method with args: " . json_encode($args));
        // Tu peux faire des vérifications et throw une exception si besoin
    }

    public function onAfterAction(string $method, array $args): void
    {
        // Action après l’appel
        if ($method === 'setName') {
            // Logger, notifier, sauvegarder en BDD, etc.
            error_log("Name changed to " . $this->name);
        }
    }
    
    // ...
}
```

---

## Comportement par défaut

* Ces méthodes sont **optionnelles** : tu n’es pas obligé de les déclarer.
* Elles ne sont **jamais appelées côté client** : tout se passe côté PHP.
* Si tu surcharges ces méthodes, pense à appeler `parent::onBeforeAction()` ou `parent::onAfterAction()` si tu veux 
conserver leur comportement (utile si tu ajoutes une logique de base dans une classe mère personnalisée).

---

## Bonnes pratiques

* Utilise `onBeforeAction` pour : vérification de droits, validation, logs, pré-chargement de données, contrôle d’accès...
* Utilise `onAfterAction` pour : notification, log, reset d’état, synchronisation externe, etc.
* Reste **concis** : n’alourdis pas trop ces hooks, garde-les lisibles et réutilisables.
