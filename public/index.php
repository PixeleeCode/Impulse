<?php

require_once __DIR__ . '/../vendor/autoload.php';

use Impulse\Components\BlurCounter;
use Impulse\Components\Counter;
use Impulse\Components\HelloUser;
use Impulse\Components\KeydownLogger;
use Impulse\Components\LivePreview;
use Impulse\Components\Modal;
use Impulse\Components\MultiSelect;
use Impulse\Components\SearchFilter;
use Impulse\Components\ColorPicker;
use Impulse\Components\SubmitForm;
use Impulse\ImpulseFactory;

// Créer les instances de composants
$modal = ImpulseFactory::create(Modal::class);
$multiSelect = ImpulseFactory::create(MultiSelect::class, [
    'selected' => [
        [ 'label' => 'Hamburger', 'emoji' => '🍔' ],
        [ 'label' => 'Watermelon','emoji' => '🍉' ]
    ]
]);

$helloUser = ImpulseFactory::create(HelloUser::class, [
    'name' => 'John',
]);
$counter1 = ImpulseFactory::create(Counter::class);
$counter2 = ImpulseFactory::create(Counter::class, [
    'count' => 100,
]);
$searchFilter = ImpulseFactory::create(SearchFilter::class);
$colorPicker = ImpulseFactory::create(ColorPicker::class);
$blurCounter = ImpulseFactory::create(BlurCounter::class);
$keydownLogger = ImpulseFactory::create(KeydownLogger::class);
$submitForm = ImpulseFactory::create(SubmitForm::class);
$livePreview = ImpulseFactory::create(LivePreview::class);

?>
<!DOCTYPE html>
<html lang="fr">
    <head>
        <meta charset="UTF-8">
        <title>Impulse - Démo</title>
        <script src="impulse.js"></script>
        <script>
            // Emmetre un emit en JS
            /*Impulse.emit('saveUser', { input: 123 }, {
                components: ['hellouser_1'],
                callback: r => alert(JSON.stringify(r))
            });*/

            // Ecouter un emit en JS
            document.addEventListener('impulse:emit', function(event) {
                // event.detail contient l'objet { event, payload }
                const detail = event.detail;
                console.log('Un emit Impulse a été reçu :', detail);

                // Exemple : filtrer selon le nom de l'événement emit
                if (detail.event === 'saveUser') {
                    // Utilise la payload comme tu veux !
                    alert('Payload reçu côté JS : ' + JSON.stringify(detail.payload));
                }
            });
        </script>
        <style>
            body {
                font-family: sans-serif;
                padding: 2rem;
                max-width: 800px;
                margin: 0 auto;
            }
            button {
                margin: 0.5rem;
                padding: 0.5rem 1rem;
                background: #4a7bff;
                color: white;
                border: none;
                border-radius: 4px;
                cursor: pointer;
            }
            button:hover {
                background: #3a6eff;
            }
            .component {
                border: 1px solid #eee;
                padding: 1rem;
                margin-bottom: 1.5rem;
                border-radius: 8px;
                box-shadow: 0 2px 4px rgba(0,0,0,0.05);
            }
            .search-filter input {
                padding: 0.5rem;
                width: 70%;
                border: 1px solid #ddd;
                border-radius: 4px;
                margin-right: 0.5rem;
            }
            .search-filter .results {
                margin-top: 1rem;
            }
            .search-filter ul {
                list-style-type: none;
                padding: 0;
            }
            .search-filter li {
                padding: 0.5rem;
                border-bottom: 1px solid #eee;
            }

            /* Styles pour le ColorPicker */
            .color-picker {
                font-family: sans-serif;
            }
            .color-picker .controls {
                margin-bottom: 1rem;
            }
            .color-picker select {
                padding: 0.5rem;
                margin: 0.5rem 0;
                width: 100%;
                max-width: 300px;
                border-radius: 4px;
                border: 1px solid #ddd;
            }
            .color-picker .intensity-control {
                margin-top: 1rem;
            }
            .color-picker .intensity-slider {
                width: 100%;
                max-width: 300px;
                margin-top: 0.5rem;
            }
            .color-picker .preview {
                width: 100%;
                height: 100px;
                border-radius: 8px;
                margin: 1rem 0;
                display: flex;
                flex-direction: column;
                justify-content: center;
                align-items: center;
                color: white;
                text-shadow: 0 0 3px rgba(0,0,0,0.5);
            }
            .color-picker .color-name {
                font-size: 1.5rem;
                font-weight: bold;
                margin-bottom: 0.5rem;
            }
            .color-picker .color-code {
                font-family: monospace;
            }
            .color-picker .reset-btn {
                background-color: #ff5252;
            }
            .color-picker .reset-btn:hover {
                background-color: #ff0000;
            }
            .color-picker .color-history {
                margin: 1rem 0;
            }
            .color-picker .history-chips {
                display: flex;
                flex-wrap: wrap;
                gap: 10px;
            }
            .color-picker .color-chip {
                width: 30px;
                height: 30px;
                border-radius: 50%;
                cursor: pointer;
                border: 2px solid #fff;
                box-shadow: 0 1px 3px rgba(0,0,0,0.2);
                transition: transform 0.2s;
            }
            .color-picker .color-chip:hover {
                transform: scale(1.1);
            }

            /* Styles pour la modale animée */
            .impulse-modal-backdrop {
                position: fixed;
                left: 0; top: 0; width: 100vw; height: 100vh;
                background: #0008;
                z-index: 900;
                display: flex;
                align-items: center;
                justify-content: center;
                opacity: 0;
                pointer-events: none;
                transition: opacity 0.4s cubic-bezier(.45,0,.55,1);
            }
            .impulse-modal-backdrop.is-open {
                opacity: 1;
                pointer-events: auto;
            }
            .impulse-modal-content {
                background: #fff;
                border-radius: 8px;
                padding: 2em;
                min-width: 300px;
                max-width: 90vw;
                margin: 8vh auto;
                box-shadow: 0 6px 40px #0003;
                position: relative;
                transform: scale(.98) translateY(20px);
                opacity: 0;
                transition: transform 0.35s cubic-bezier(.5,.1,.3,1), opacity 0.35s cubic-bezier(.5,.1,.3,1);
            }
            .impulse-modal-backdrop.is-open .impulse-modal-content {
                transform: scale(1) translateY(0);
                opacity: 1;
            }
        </style>
    </head>
    <body>
        <h1>Démo Impulse</h1>

        <div class="component">
            <?= $modal->render() ?>
        </div>

        <div class="component">
            <?= $multiSelect->render() ?>
        </div>

        <div class="component">
            <?= $helloUser->render() ?>
        </div>

        <div class="component">
            <h3>Premier compteur</h3>
            <?= $counter1->render() ?>
        </div>

        <div class="component">
            <h3>Deuxième compteur</h3>
            <?= $counter2->render() ?>
        </div>

        <div class="component">
            <?= $searchFilter->render() ?>
        </div>

        <div class="component">
            <?= $colorPicker->render() ?>
        </div>

        <div class="component">
            <?= $blurCounter->render() ?>
        </div>

        <div class="component">
            <?= $keydownLogger->render() ?>
        </div>

        <div class="component">
            <?= $submitForm->render() ?>
        </div>

        <div class="component">
            <?= $livePreview->render() ?>
        </div>
    </body>
</html>
