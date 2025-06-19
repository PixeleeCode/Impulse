<?php

namespace Impulse\Components;

use Impulse\Attributes\Action;
use Impulse\Core\Component;

/**
 * @property string test
 */
final class ComponentThree extends Component
{
    public ?string $tagName = 'three';

    public function setup(): void
    {
        $this->state('test', 'Title H3');
    }

    #[Action]
    public function changeTitle(): void
    {
        $this->test = 'Composant 3';
    }

    public function template(): string
    {
        return <<<HTML
            <div>
                <h3 data-impulse-update="{$this->getId()}::title">{$this->test}</h3>
                {$this->slot}
                <button impulse:click="changeTitle" impulse:update="{$this->getId()}::title">Changer le titre H3</button>
            </div>
        HTML;
    }
}
