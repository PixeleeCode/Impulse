<?php

namespace Impulse\Components;

use Impulse\Core\Component;

final class BlurCounter extends Component
{
    public function setup(): void
    {
        $counter = $this->state('count', 0);

        $this->methods->register('updateFromBlur', function(string $value) use ($counter) {
            $counter->set((int) $value);
        });
    }

    public function template(): string
    {
        $count = $this->count;

        return <<<HTML
            <div class="blur-counter">
                <label for="blur-count">Compteur (modif. au blur) :</label>
                <input id="blur-count" type="text" value="$count" impulse:blur="updateFromBlur">
                <p>Valeur actuelle : $count</p>
            </div>
        HTML;
    }
}
