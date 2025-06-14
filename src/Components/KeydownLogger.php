<?php

namespace Impulse\Components;

use Impulse\Core\Component;

final class KeydownLogger extends Component
{
    public function setup(): void
    {
        $log = $this->state('log', []);

        $this->methods->register('logKey', function(string $value) use ($log) {
            $entries = $log->get();
            $entries[] = $value;
            $log->set(array_slice($entries, -5));
        });
    }

    public function template(): string
    {
        $log = $this->log;

        $logHtml = implode('<br>', array_map('htmlspecialchars', $log));

        return <<<HTML
            <div class="keydown-logger">
                <label for="key-input">Tapez quelque chose :</label>
                <input id="key-input" type="text" impulse:keydown="logKey">
                <div class="log">
                    <strong>Dernières touches :</strong><br>
                    {$logHtml}
                </div>
            </div>
        HTML;
    }
}
