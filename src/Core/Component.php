<?php

namespace Impulse\Core;

use Impulse\Collections\Collection\MethodCollection;
use Impulse\Collections\Collection\StateCollection;
use Impulse\Interfaces\ComponentInterface;

abstract class Component implements ComponentInterface
{
    private string $id;

    /** @var array<int, mixed> */
    protected array $defaults = [];

    protected MethodCollection $methods;
    protected StateCollection $stateCache;

    public function __construct(string $id, array $defaults = [])
    {
        $this->id = $id;
        $this->defaults = $defaults;
        $this->methods = new MethodCollection();
        $this->stateCache = new StateCollection();

        $this->setup();
    }

    public function getId(): string
    {
        return $this->id;
    }

    /**
     * Récupère la collection des méthodes
     */
    public function getMethods(): MethodCollection
    {
        return $this->methods;
    }

    /**
     * Initialise le composant et définit ses méthodes et son état
     */
    abstract public function setup(): void;

    /**
     * Rend le HTML du composant
     */
    abstract public function template(): string;

    /**
     * Rend le composant avec conteneur data-impulse-id et data-states (automatique pour AJAX)
     * @throws \JsonException
     */
    public function render(): string
    {
        $id = $this->getId();
        $dataStates = htmlspecialchars(json_encode($this->getStates(), JSON_THROW_ON_ERROR), ENT_QUOTES, 'UTF-8');

        return <<<HTML
            <div data-impulse-id="$id" data-states="$dataStates">
                {$this->template()}
            </div>
        HTML;
    }

    /**
     * Crée ou récupère un état pour le composant, avec gestion des valeurs par défaut personnalisées.
     *
     * Si une valeur par défaut a été transmise lors de l'instanciation du composant (via $defaults),
     * elle prend le dessus sur la valeur codée ici.
     * Si le state existe déjà (persisté), il est simplement récupéré.
     *
     * @template T
     * @param string $name Nom du state (ex: 'name', 'count', etc.)
     * @param T $defaultValue Valeur par défaut à utiliser si aucune valeur n'est trouvée en session ni passée à l'instanciation.
     * @param bool $protected Protège la valeur en y appliquant un hash pour le côté DOM
     * @return State<T>
     */
    public function state(string $name, mixed $defaultValue, bool $protected = false): State
    {
        $defaultValue = array_key_exists($name, $this->defaults) ? $this->defaults[$name] : $defaultValue;
        return $this->stateCache->getOrCreate($name, $defaultValue, $protected);
    }

    /**
     * Retourne un tableau associatif [nomState => valeur] pour tous les states du composant
     *
     * @return array<int|string, mixed>
     */
    public function getStates(): array
    {
        $states = [];
        foreach ($this->stateCache as $name => $state) {
            /** @var State $state */
            $states[$name] = $state->getForDom();
        }

        return $states;
    }

    /**
     * Accès magique en lecture à une propriété d'état du composant.
     */
    public function __get(string $name): mixed
    {
        return $this->stateCache->getValue($name);
    }

    /**
     * Accès magique en écriture à une propriété d'état du composant.
     * Note : l'affectation directe n'est pas supportée, utiliser les objets State.
     */
    public function __set(string $name, mixed $value): void
    {
        if (property_exists($this, $name)) {
            trigger_error("Ne pas déclarer de propriété publique '$name' dans les composants : utilisez \$this->state('$name', ...)", E_USER_WARNING);
        }

        $this->stateCache->setValue($name, $value);
    }

    /**
     * Vérifie si une propriété d'état est définie dans le composant.
     */
    public function __isset(string $name): bool
    {
        // On pourrait vérifier dans $this->stateCache si le state existe.
        // Ici non implémenté, retourner false par défaut.
        return false;
    }

    /**
     * Invocation magique des méthodes
     */
    public function __call(string $name, array $arguments): mixed
    {
        return $this->methods->call($name, $arguments);
    }

    public function onBeforeAction(?string $method = null, array $args = []): void {}
    public function onAfterAction(): void {}
}
