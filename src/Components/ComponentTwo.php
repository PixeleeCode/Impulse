<?php

namespace Impulse\Components;

use Impulse\Attributes\Action;
use Impulse\Core\Component;

/**
 * @property string $test
 */
final class ComponentTwo extends Component
{
    public ?string $tagName = 'two';

    public function setup(): void
    {
        $this->state('test', 'Title H2');
    }

    #[Action]
    public function changeTitle(): void
    {
        $this->test = 'Composant 2';
    }

    public function template(): string
    {
        return <<<HTML
            <div>
                <h2 data-impulse-update="{$this->getId()}::title">{$this->test}</h2>
                <header>{$this->slot('header')}</header>
                <main>{$this->slot()}</main>
                <footer>{$this->slot('footer')}</footer>
                <three>
                    <p>Slot Composant 3 depuis le Composant 2</p>
                </three>
                <button impulse:click="changeTitle" impulse:update="{$this->getId()}::title">Changer le titre H2</button>
            </div>
        HTML;
    }
}
