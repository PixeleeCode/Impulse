<?php

namespace Impulse\Components;

use Impulse\Core\Component;

/**
 * @property string $name
 */
class HelloUser extends Component
{
    public function setup(): void
    {
        $name = $this->state('name', '', true);

        $this->methods->register('setName', function (string $value) use ($name) {
            $name->set($value);
        });

        $this->methods->register('clearName', function () use ($name) {
            $name->set('');
        });
    }

    /**
     * @throws \JsonException
     */
    public function onBeforeAction(?string $method = null, array $args = []): void
    {
        // Exemple d'audit/log
        error_log('[HelloUser] Avant action : ' . $method . ' avec args : ' . json_encode($args, JSON_THROW_ON_ERROR));

        // Exemple de validation : empêche d'effacer le nom si déjà vide
        if ($method === 'clearName' && empty($this->name)) {
            error_log('[HelloUser] clearName ignoré car le champ est déjà vide.');
            // Ici tu pourrais throw une exception, retourner une réponse custom, etc.
        }
    }

    public function onAfterAction(): void
    {
        // Exemple de log après action
        error_log('[HelloUser] Après action, valeur actuelle : ' . $this->name);

        // Exemple d'enregistrement en base, ou de synchronisation externe
        // UserRepository::save(['name' => $this->name]);
    }

    public function onEvent(string $event, array $payload): ?array
    {
        if ($event === 'saveUser') {
            // Met à jour le state 'name' si reçu dans le payload
            if (isset($payload['input'])) {
                $this->state('name', '')->set($payload['input']);
            }

            // Refresh le composant
            return [
                'refresh' => true
            ];
        }

        // Pas de refresh du composant
        return null;
    }

    public function template(): string
    {
        $name = htmlspecialchars($this->name ?: '');

        /*return <<< HTML
            <div>
                <input type="text"
                       name="name"
                       impulse:input="setName"
                       impulse:update="preview"
                       placeholder="Votre prénom..." />

                <p>Bonjour, <strong data-impulse-update="preview">$name</strong>, tu n'as pas de moteur de template !</p>

                <!-- Bouton pour vider le champ -->
                <button type="button" impulse:click="clearName" impulse:update="preview">
                    Effacer
                </button>
            </div>
        HTML;*/

        return $this->view('hello.index', [
            'name' => $name,
        ]);
    }
}
