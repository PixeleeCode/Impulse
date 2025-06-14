<?php

namespace Impulse\Components;

use Impulse\Core\Component;

class MultiSelect extends Component
{
    public function setup(): void
    {
        $options = $this->state('options', []);
        $selected = $this->state('selected', []);

        // Ajouter une option à la sélection
        $this->methods->register('add', function (int $idx) use ($options, $selected) {
            $current = $selected->get();
            $opts = $options->get();
            if (!isset($opts[$idx]) || in_array($opts[$idx], $current, true)) {
                return;
            }

            $current[] = $opts[$idx];
            $selected->set($current);
        });

        // Retirer une option
        $this->methods->register('remove', function (int $idx) use ($selected) {
            $current = $selected->get();
            array_splice($current, $idx, 1);
            $selected->set($current);
        });
    }

    public function template(): string
    {
        $id = $this->getId();
        $options = $this->options ?? [];
        $selected = $this->selected ?? [];

        // Générer la liste filtrée
        $already = array_map(static fn($opt) => $opt['label'], $selected);
        $filtered = array_filter($options, static fn($o) => !in_array($o['label'], $already, true));

        // Affichage des options sélectionnées
        $selectedHtml = implode('', array_map(
            static fn($opt, $idx) => '<span class="multi-chip">' .
                htmlspecialchars($opt['label']) . ' ' . $opt['emoji'] .
                '<button type="button" class="multi-remove" impulse:click="remove(' . $idx . ')">&times;</button></span>',
            $selected, array_keys($selected)
        ));

        // Dropdown des options restantes
        $dropdown = implode('', array_map(
            static fn($opt, $i) => '<div class="multi-option" impulse:click="add(' . $i . ')">' .
                htmlspecialchars($opt['label']) . ' ' . $opt['emoji'] . '</div>',
            array_values($filtered), array_keys($filtered)
        ));

        return <<<HTML
            <div data-impulse-id="{$id}" class="multi-select-wrap">
                <label for="msel">What sounds tasty?</label>
                <div class="multi-select-box" tabindex="0" id="msel">
                    <div class="multi-chips">{$selectedHtml}<input type="text" class="multi-input" readonly></div>
                    <div class="multi-dropdown">{$dropdown}</div>
                </div>
            </div>
        HTML;
    }
}
