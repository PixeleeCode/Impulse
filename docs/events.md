# Gestion des événements dans les templates HTML (impulse:*)

Impulse permet d’ajouter facilement des interactions entre l’utilisateur et vos composants, simplement en ajoutant des
attributs HTML spéciaux :  
**impulse:click, impulse:input, impulse:change**, etc.

Ces attributs relient vos actions côté navigateur à vos méthodes PHP, sans écrire de JS à la main !

---

## Table des matières

- [Principe général](#principe-général)
- [Liste des événements supportés](#liste-des-événements-supportés)
- [Exemples d’utilisation](#exemples-d-utilisation)
- [Passer des arguments](#passer-des-arguments)
- [Personnalisation (emit)](#personnalisation-emit)
- [Bonnes pratiques](#bonnes-pratiques)

---

## Principe général

Chaque composant déclare ses méthodes côté PHP :
```php
$this->methods->register('increment', function () use ($count) {
    $count->set($count->get() + 1);
});
```

Dans le template HTML du composant, on associe ces méthodes à des événements utilisateur :
```html
<button impulse:click="increment">+1</button>
```

À chaque clic, le navigateur envoie une requête AJAX à Impulse, qui exécute la méthode PHP correspondante et met à jour
l’interface.

---

## Liste des événements supportés

Impulse propose :
* `impulse:click` → Clic sur un bouton ou un élément
* `impulse:input` → Modification d’un champ texte ou textarea
* `impulse:change` → Changement d’une valeur (select, checkbox, range…)
* `impulse:blur` → Perte du focus sur un champ
* `impulse:keydown` → Touche pressée sur un champ
* `impulse:submit` → Soumission d’un formulaire

---

## Exemples d’utilisation

**Click**
```html
<button impulse:click="increment">+1</button>
```

**Input (champ texte)**
```html
<input impulse:input="setName" type="text" />
```

**Change (select/range)**
```html
<select impulse:change="setOption">
    <option value="A">A</option>
    <option value="B">B</option>
</select>
```

**Blur**
```html
<input impulse:blur="validateEmail" type="email" />
```

**Submit (formulaire)**
```html
<form impulse:submit="send">
    <input type="text" impulse:input="setValue" />
    <button type="submit">Envoyer</button>
</form>
```

---

## Passer des arguments

Tu peux passer des arguments dans l’appel :
```html
<button impulse:click="setColor('blue')">Bleu</button>
<button impulse:click="setColor('red')">Rouge</button>
```
Côté PHP, la méthode reçoit la valeur :
```php
$this->methods->register('setColor', fn(string $color) => $state->set($color));
```

---

## Personnalisation (emit)

Voir la [documentation](emit.md) sur les emits.

---

## Bonnes pratiques

* Utilise les événements natifs (click, input, etc.) autant que possible.
* Pour des cas avancés (emit), isole la logique métier dans tes méthodes PHP.
* Privilégie un nom d’action clair et descriptif : évite les noms génériques (handle, doAction).
* Garde ton HTML simple et auto-documenté : un dev qui lit `<button impulse:click="addItem">` comprend l’intention tout de suite.
