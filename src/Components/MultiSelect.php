<?php

namespace Impulse\Components;

use Impulse\Attributes\Action;
use Impulse\Core\Component;

/**
 * @property array $options
 * @property array $selected
 */
class MultiSelect extends Component
{
    public function setup(): void
    {
        $this->state('options', [
            [ 'label' => 'Banana',   'emoji' => '🍌' ],
            [ 'label' => 'Apple',    'emoji' => '🍎' ],
            [ 'label' => 'Cheese',   'emoji' => '🧀' ],
            [ 'label' => 'Pizza',    'emoji' => '🍕' ],
            [ 'label' => 'Pretzel',  'emoji' => '🥨' ],
            [ 'label' => 'Donut',    'emoji' => '🍩' ],
            [ 'label' => 'Pineapple','emoji' => '🍍' ],
            [ 'label' => 'Hamburger','emoji' => '🍔' ],
            [ 'label' => 'Watermelon','emoji' => '🍉' ],
        ]);

        $this->state('selected', []);
    }

    #[Action]
    public function addOption(int $idx): void
    {
        $options = $this->options;
        $selected = $this->selected;

        if (!isset($options[$idx]) || in_array($options[$idx], $selected, true)) {
            return;
        }

        $selected[] = $options[$idx];
        $this->selected = $selected;
    }

    #[Action]
    public function removeOption(int $idx): void
    {
        $selected = $this->selected;
        array_splice($selected, $idx, 1);
        $this->selected = $selected;
    }

    public function template(): string
    {
        return $this->view('components.multi-select', [
            'options' => $this->options,
            'selected' => $this->selected,
        ]);
    }
}
