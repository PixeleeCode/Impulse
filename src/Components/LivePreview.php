<?php

namespace Impulse\Components;

use Impulse\Core\Component;

final class LivePreview extends Component
{
    public function setup(): void
    {
        $name = $this->state('name', '');

        $this->methods->register('updateName', fn(string $value) => $name->set($value));
    }

    public function template(): string
    {
        $name = htmlspecialchars($this->name);

        return <<<HTML
            <div class="live-preview">
                <label for="live-name">Votre prénom :</label>
                <input id="live-name" type="text" impulse:input="updateName" value="{$name}">
                <p>Bonjour <strong>{$name}</strong>, bienvenue dans cette démonstration !</p>
                <p><my-counter count="123456789" /></p>
            </div>
        HTML;
    }
}
