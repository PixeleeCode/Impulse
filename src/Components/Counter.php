<?php

namespace Impulse\Components;

use Impulse\Attributes\Action;
use Impulse\Core\Component;

/**
 * @property int $count
 */
final class Counter extends Component
{
    public ?string $tagName = 'my-counter';

    public function setup(): void
    {
        $count = $this->state('count', 0);

        //$this->methods->register('increment', fn() => $count->set($count->get() + 1));
        //$this->methods->register('reset', fn() => $count->set(0));
    }

    #[Action]
    public function increment(): void
    {
        ++$this->count;
    }

    #[Action]
    public function reset(): void
    {
        $this->count = 0;
    }

    public function template(): string
    {
        $id = $this->getId();
        $count = $this->count;

        return <<<HTML
            <div>
                <h2 data-impulse-update="$id">Compteur : $count</h2>
                <button impulse:click="increment" impulse:update="$id">+1</button>
                <button impulse:click="reset">Réinitialiser</button>
            </div>
        HTML;
    }
}
