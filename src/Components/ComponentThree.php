<?php

namespace Impulse\Components;

use Impulse\Attributes\Action;
use Impulse\Core\Component;

/**
 * @property string $message
 * @property string $test
 */
final class ComponentThree extends Component
{
    public ?string $tagName = 'three';

    public function setup(): void
    {
        $message = $this->state('message', '');
        $this->state('test', 'Title H3');

        $this->watch('test', function ($new, $old) use ($message) {
            $message->set('Voici la nouvelle valeur : ' . $new . ' et l\'ancienne : ' . $old);
        });
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
                <h3 data-impulse-update="group@{$this->getId()}::title">{$this->test}</h3>
                {$this->slot}
                <p data-impulse-update="group@{$this->getId()}::message">$this->message</p>
                <button impulse:click="changeTitle" impulse:update="group">Changer le titre H3</button>
            </div>
        HTML;
    }
}
