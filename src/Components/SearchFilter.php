<?php

namespace Impulse\Components;

use Impulse\Core\Component;

final class SearchFilter extends Component
{
    /**
     * Liste des éléments à filtrer
     */
    private array $items = [
        'PHP', 'JavaScript', 'TypeScript', 'Python',
        'Ruby', 'Java', 'C#', 'C++', 'Go', 'Rust',
        'Swift', 'Kotlin', 'Dart', 'Scala', 'Haskell'
    ];

    public function setup(): void
    {
        // Initialisation des états
        $query = $this->state('query', '');
        $filteredItems = $this->state('filteredItems', $this->items);

        $this->methods->register('search', function(string $value) use ($query, $filteredItems) {
            $query->set($value);

            if (empty($value)) {
                $filteredItems->set($this->items);
                return;
            }

            $filtered = array_filter($this->items, static function($item) use ($value) {
                return stripos($item, $value) !== false;
            });

            $filteredItems->set(array_values($filtered));
        });

        $this->methods->register('reset', function() use ($query, $filteredItems) {
            $query->set('');
            $filteredItems->set($this->items);
        });
    }

    public function template(): string
    {
        $id = $this->getId();
        $query = $this->query;
        $filteredItems = $this->filteredItems;
        $count = count($filteredItems);

        // Générer un ID unique pour l'input de recherche
        $searchInputId = "search-input-" . str_replace('_', '-', $id);

        $itemsList = '';
        foreach ($filteredItems as $item) {
            $itemsList .= "<li>{$item}</li>";
        }

        return <<<HTML
            <div class="search-filter">
                <h3>Filtre de recherche</h3>
                <div class="search-box">
                    <input 
                        id="$searchInputId"
                        type="text" 
                        impulse:input="search" 
                        value="$query"
                        placeholder="Rechercher un langage..."
                        autocomplete="off"
                    >
                    <button impulse:click="reset">Effacer</button>
                </div>
                
                <div class="results">
                    <p>Résultats ($count)</p>
                    <ul>
                        $itemsList
                    </ul>
                </div>
            </div>
        HTML;
    }
}
