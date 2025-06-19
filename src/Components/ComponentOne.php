<?php

namespace Impulse\Components;

use Impulse\Attributes\Action;
use Impulse\Core\Component;

/**
 * @property string $test
 */
final class ComponentOne extends Component
{
    public ?string $tagName = 'one';

    public function setup(): void
    {
        $this->state('test', 'Title H1');
    }

    #[Action]
    public function changeTitle(): void
    {
        $this->test = 'Composant 1';
    }

    public function template(): string
    {
        return <<<HTML
            <div>
                <h1 data-impulse-update="{$this->getId()}::title">{$this->test}</h1>
                <two>
                    <slot name="header">
                        <h1>Mon en-tête personnalisé</h1>
                    </slot>
                
                    <slot>
                        <p>Contenu principal</p>
                    </slot>
                
                    <slot name="footer">
                        <p>Footer personnalisé</p>
                    </slot>
                </two>
                <button impulse:click="changeTitle" impulse:update="{$this->getId()}::title">Changer le titre H1</button>
            </div>
        HTML;
    }
}
