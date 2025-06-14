<?php

namespace Impulse\Components;

use Impulse\Core\Component;

final class ColorPicker extends Component
{
    /**
     * Liste des couleurs disponibles
     */
    private array $availableColors = [
        'red' => 'Rouge',
        'blue' => 'Bleu',
        'green' => 'Vert',
        'yellow' => 'Jaune',
        'purple' => 'Violet',
        'orange' => 'Orange',
        'pink' => 'Rose',
        'gray' => 'Gris',
        'black' => 'Noir'
    ];

    /**
     * Codes hexadécimaux des couleurs
     */
    private array $colorCodes = [
        'red' => '#FF0000',
        'blue' => '#0000FF',
        'green' => '#00FF00',
        'yellow' => '#FFFF00',
        'purple' => '#800080',
        'orange' => '#FFA500',
        'pink' => '#FFC0CB',
        'gray' => '#808080',
        'black' => '#000000'
    ];

    public function setup(): void
    {
        // Initialisation des états
        $selectedColor = $this->state('selectedColor', 'blue');
        $intensity = $this->state('intensity', 50);
        $history = $this->state('history', []);

        $this->methods->register('changeColor', function(string $color) use ($selectedColor, $history) {
            // Ajouter la couleur précédente à l'historique
            $previousColors = $history->get();
            $previousColor = $selectedColor->get();

            if ($previousColor !== $color) {
                $previousColors[] = $previousColor;
                // Garder seulement les 5 dernières couleurs
                if (count($previousColors) > 5) {
                    array_shift($previousColors);
                }
                $history->set($previousColors);
            }

            $selectedColor->set($color);
        });

        $this->methods->register('changeIntensity', function(int $value) use ($intensity) {
            $intensity->set(max(0, min(100, (int)$value)));
        });

        $this->methods->register('resetHistory', function() use ($history) {
            $history->set([]);
        });

        $this->methods->register('selectFromHistory', function(int $index) use ($selectedColor, $history) {
            $previousColors = $history->get();
            if (isset($previousColors[$index])) {
                $selectedColor->set($previousColors[$index]);
            }
        });
    }

    public function onBeforeAction(?string $method = null, array $args = []): void
    {
        // Exemple d'audit/log
        error_log('[ColorPicker] Avant action : ' . $method . ' avec args : ' . json_encode($args));
    }

    public function onAfterAction(): void
    {
        // Exemple de log après action
        error_log('[ColorPicker] Après action, valeur actuelle : ' . $this->name);

        // Exemple d'enregistrement en base, ou de synchronisation externe
        // UserRepository::save(['name' => $this->name]);
    }

    public function template(): string
    {
        $id = $this->getId();
        $selectedColor = $this->selectedColor;
        $intensity = $this->intensity;
        $history = $this->history;

        // ID unique pour le select
        $selectId = "color-select-" . str_replace('_', '-', $id);
        $rangeId = "intensity-range-" . str_replace('_', '-', $id);

        // Créer les options du sélecteur
        $options = '';
        foreach ($this->availableColors as $value => $label) {
            $selected = $value === $selectedColor ? 'selected' : '';
            $options .= "<option value=\"{$value}\" {$selected}>{$label}</option>";
        }

        // Calculer la couleur avec l'intensité
        $colorCode = $this->colorCodes[$selectedColor] ?? '#000000';
        $rgbColor = $this->hexToRgb($colorCode);

        // Ajuster l'intensité (on utilise l'intensité pour ajuster la luminosité)
        $factor = $intensity / 100;
        $adjustedColor = $this->adjustBrightness($rgbColor, $factor);
        $finalColorHex = $this->rgbToHex($adjustedColor);

        // Générer l'historique
        $historyHtml = '';
        if (!empty($history)) {
            $historyHtml .= '<div class="color-history"><h4>Historique</h4><div class="history-chips">';
            foreach ($history as $index => $historyColor) {
                $historyColorCode = $this->colorCodes[$historyColor] ?? '#000000';
                $title = $this->availableColors[$historyColor] ?? $historyColor;
                $historyHtml .= "<div class=\"color-chip\" style=\"background-color: {$historyColorCode};\" impulse:click=\"selectFromHistory({$index})\" title=\"{$title}\"></div>";
            }
            $historyHtml .= '</div></div>';
        }

        return <<<HTML
            <div class="color-picker">
                <h3>Sélecteur de Couleur</h3>
                
                <div class="controls">
                    <label for="$selectId">Choisir une couleur:</label>
                    <select 
                        id="$selectId" 
                        impulse:change="changeColor" 
                        class="color-select"
                    >
                        $options
                    </select>
                    
                    <div class="intensity-control">
                        <label for="$rangeId">Intensité: $intensity%</label>
                        <input 
                            id="$rangeId"
                            type="range" 
                            min="0" 
                            max="100" 
                            value="$intensity" 
                            impulse:change="changeIntensity"
                            class="intensity-slider"
                        >
                    </div>
                </div>
                
                <div class="preview" style="background-color: $finalColorHex;">
                    <span class="color-name">{$this->availableColors[$selectedColor]}</span>
                    <span class="color-code">$finalColorHex</span>
                </div>
                
                $historyHtml
                
                <button impulse:click="resetHistory" class="reset-btn">Effacer l'historique</button>
            </div>
        HTML;
    }

    /**
     * Convertit une couleur hexadécimale en RGB
     */
    private function hexToRgb(string $hex): array
    {
        $hex = ltrim($hex, '#');

        if (strlen($hex) === 3) {
            $r = hexdec($hex[0] . $hex[0]);
            $g = hexdec($hex[1] . $hex[1]);
            $b = hexdec($hex[2] . $hex[2]);
        } else {
            $r = hexdec(substr($hex, 0, 2));
            $g = hexdec(substr($hex, 2, 2));
            $b = hexdec(substr($hex, 4, 2));
        }

        return ['r' => $r, 'g' => $g, 'b' => $b];
    }

    /**
     * Convertit une couleur RGB en hexadécimal
     */
    private function rgbToHex(array $rgb): string
    {
        return sprintf("#%02x%02x%02x", $rgb['r'], $rgb['g'], $rgb['b']);
    }

    /**
     * Ajuste la luminosité d'une couleur RGB
     */
    private function adjustBrightness(array $rgb, float $factor): array
    {
        return [
            'r' => min(255, max(0, round($rgb['r'] * $factor))),
            'g' => min(255, max(0, round($rgb['g'] * $factor))),
            'b' => min(255, max(0, round($rgb['b'] * $factor)))
        ];
    }
}
