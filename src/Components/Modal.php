<?php

namespace Impulse\Components;

use Impulse\Core\Component;

class Modal extends Component
{
    public function setup(): void
    {
        $open = $this->state('open', false);

        // Ouvre la modale
        $this->methods->register('open', function() use ($open) {
            $open->set(true);
        });

        // Ferme la modale
        $this->methods->register('close', function() use ($open) {
            $open->set(false);
        });
    }

    public function template(): string
    {
        $open = (bool)$this->open;

        $isOpenClass = $open ? 'is-open' : '';

        return <<<HTML
            <button impulse:click="open">Ouvrir la modale</button>
            <div 
                data-impulse-part="modal"
                class="impulse-modal-backdrop $isOpenClass"
            >
                <div class="impulse-modal-content">
                    <h2>Ma super modale</h2>
                    <p>Ceci est une modale Impulse.</p>
                    <button impulse:click="close">Fermer</button>
                </div>
            </div>
        HTML;
    }
}
