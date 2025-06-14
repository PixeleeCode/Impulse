<?php

namespace Impulse\Components;

use Impulse\Core\Component;

final class SubmitForm extends Component
{
    public function setup(): void
    {
        $message = $this->state('message', '');
        $value = $this->state('input', '');

        $this->methods->register('submit', function() use ($message, $value) {
            $message->set('Formulaire envoyé avec : ' . $value->get());
        });

        $this->methods->register('updateInput', function(string $val) use ($value) {
            $value->set($val);
        });
    }

    public function template(): string
    {
        $input = htmlspecialchars((string) $this->input);
        $message = htmlspecialchars((string) $this->message);

        return <<<HTML
            <div class="submit-form">
                <form impulse:emit="saveUser" impulse:submit="submit">
                    <label for="form-input">Texte :</label>
                    <input id="form-input" type="text" name="input" value="{$input}" impulse:input="updateInput" data-partial="preview">
                    <button type="submit">Envoyer</button>
                </form>
                <p>{$message}</p>
                <button type="button" impulse:emit="saveUser" impulse:payload='{"input": "Guillaume & Julie"}'>Test emit</button>
            </div>
        HTML;
    }
}
