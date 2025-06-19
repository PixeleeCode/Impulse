<?php

namespace Impulse\Core;

use Impulse\Collections\Collection\MethodCollection;
use Impulse\Collections\Collection\StateCollection;
use Impulse\Collections\Collection\WatcherCollection;
use Impulse\ImpulseRenderer;
use Impulse\Interfaces\ComponentInterface;
use ScssPhp\ScssPhp\Compiler;
use ScssPhp\ScssPhp\Exception\SassException;

abstract class Component implements ComponentInterface
{
    private string $id;

    /**
     * @var array<int, mixed>
     */
    private array $namedSlots = [];

    public ?string $tagName = null;

    /**
     * @var array<int, mixed>
     */
    protected array $defaults = [];
    protected string $slot = '';

    protected WatcherCollection $watchers;
    protected MethodCollection $methods;
    protected StateCollection $stateCache;

    public function __construct(string $id, array $defaults = [])
    {
        $this->id = $id;
        $this->defaults = $defaults;
        $this->slot = $defaults['__slot'] ?? '';

        foreach ($defaults as $key => $value) {
            if (str_starts_with($key, '__slot:')) {
                $slotName = substr($key, 8);
                $this->namedSlots[$slotName] = $value;
            }
        }

        $this->methods = new MethodCollection();
        $this->stateCache = new StateCollection();
        $this->watchers = new WatcherCollection();

        $this->setup();
        $this->stateCache->setComponent($this);
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
     * @throws \ReflectionException|\DOMException
     * @throws SassException
     */
    public function render(?string $update = null): string
    {
        ComponentResolver::registerNamespaceFromInstance($this);

        $id = $this->getId();
        $dataStates = htmlspecialchars(json_encode($this->getStates(), JSON_THROW_ON_ERROR), ENT_QUOTES, 'UTF-8');

        $parser = new ComponentHtmlTransformer();
        $template = $parser->process($this->template());

        if ($css = $this->style()) {
            $compiled = $this->compileStyle();
            $scopedAttr = $this->isScopedStyle() ? "[data-impulse-id=\"{$this->id}\"]" : '';
            $css = preg_replace('/(^|\})\s*([^{]+)/', "$1 $scopedAttr $2", $compiled);
            $styleTag = "<style>$css</style>";
            $template = $styleTag . $template;
        }

        if (!empty($update) && !str_contains($update, '@')) {
            $dom = new \DOMDocument();
            libxml_use_internal_errors(true);
            $dom->loadHTML('<!DOCTYPE html><meta charset="utf-8">' . $template);
            libxml_clear_errors();

            $xpath = new \DOMXPath($dom);
            $group = $update;

            $fragments = [];
            foreach ($xpath->query("//*[@data-impulse-update]") as $node) {
                $attr = $node->getAttribute('data-impulse-update');
                if (str_starts_with($attr, $group . '@')) {
                    $parts = explode('@', $attr, 2);
                    if (isset($parts[1])) {
                        $key = $parts[1];
                        $fragments["{$group}@{$key}"] = $dom->saveHTML($node);
                    }
                }
            }

            if (!empty($fragments)) {
                header('Content-Type: application/json');
                echo json_encode([
                    'fragments' => $fragments,
                    'states' => $this->getStates(),
                ], JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE);
                exit;
            }
        }

        if (trim($template) !== '') {
            $content = $template;
        } elseif ($renderer = ImpulseRenderer::get()) {
            $content = $renderer->render($this->getTemplateName(), $this->getViewData());
        } else {
            throw new \RuntimeException("Aucun contenu HTML ni moteur de template n’est disponible pour ce composant.");
        }

        $slotEncoded = base64_encode($this->slot);
        $slotAttr = $slotEncoded ? ' data-impulse-slot="' . htmlspecialchars($slotEncoded, ENT_QUOTES) . '"' : '';

        return <<<HTML
            <div data-impulse-id="$id" data-states="$dataStates"$slotAttr>
                {$content}
            </div>
        HTML;
    }

    public function view(string $template, array $data = []): string
    {
        $renderer = ImpulseRenderer::get();
        if (!$renderer) {
            throw new \RuntimeException("Aucun moteur de rendu n'est défini. Impossible d'appeler view().");
        }

        return $renderer->render($template, $data ?: $this->getViewData());
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

    public function style(): ?string
    {
        return null;
    }

    public function isScopedStyle(): bool
    {
        return true;
    }

    /**
     * @throws SassException
     */
    protected function compileStyle(): string
    {
        return (new Compiler())->compileString($this->style())->getCss();
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
        // Non implémenté, retourner false par défaut.
        return false;
    }

    /**
     * Invocation magique des méthodes
     */
    public function __call(string $name, array $arguments): mixed
    {
        return $this->methods->call($name, $arguments);
    }

    public function getTemplateName(): string
    {
        return get_class($this);
    }

    public function getViewData(): array
    {
        return get_object_vars($this);
    }

    /**
     * Définit un slot nommé dans le composant.
     */
    public function setSlot(string $name, string $content): void
    {
        $this->namedSlots[$name] = $content;
    }

    /**
     * Récupère un slot nommé, ou le slot par défaut.
     */
    public function slot(string $name = '__slot'): string
    {
        if ($name === '__slot') {
            return $this->slot;
        }

        return $this->namedSlots[$name] ?? '';
    }

    public function watch(string $stateName, callable $callback): static
    {
        $this->watchers->set($stateName, $callback);

        return $this;
    }

    public function getWatchers(): WatcherCollection
    {
        return $this->watchers;
    }

    public function onBeforeAction(?string $method = null, array $args = []): void {}
    public function onAfterAction(): void {}
}
