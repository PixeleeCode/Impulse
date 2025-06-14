<?php

namespace Impulse\Collections;

/**
 * Collection générique de base
 * @template T
 */
class Collection implements \Iterator, \Countable, \ArrayAccess
{
    /** @var array<int|string, T> */
    private array $items;
    private int $position = 0;

    /**
     * @param array<int|string, T> $items
     */
    public function __construct(array $items = [])
    {
        $this->items = $items;
    }

    /**
     * Ajoute un élément à la collection
     *
     * @param T $item
     */
    public function add(mixed $item): self
    {
        $this->items[] = $item;
        return $this;
    }

    /**
     * Définit un élément avec une clé
     *
     * @param T $item
     */
    public function set(int|string $key, mixed $item): self
    {
        $this->items[$key] = $item;
        return $this;
    }

    /**
     * Récupère un élément par sa clé
     *
     * @return T|null
     */
    public function get(int|string $key): mixed
    {
        return $this->items[$key] ?? null;
    }

    /**
     * Vérifie si une clé existe
     */
    public function has(int|string $key): bool
    {
        return isset($this->items[$key]);
    }

    /**
     * Supprime un élément
     */
    public function remove(int|string $key): self
    {
        unset($this->items[$key]);
        return $this;
    }

    /**
     * Filtre la collection
     *
     * @param callable(T, int|string): bool $callback
     */
    public function filter(callable $callback): self
    {
        return new Collection(array_filter($this->items, $callback, ARRAY_FILTER_USE_BOTH));
    }

    /**
     * Transforme la collection
     *
     * @template U
     * @param callable(T, int|string): U $callback
     * @return Collection<U>
     */
    public function map(callable $callback): Collection
    {
        return new Collection(array_map($callback, $this->items, array_keys($this->items)));
    }

    /**
     * Trouve le premier élément qui correspond au critère
     *
     * @return T|null
     */
    public function first(?callable $callback = null): mixed
    {
        if ($callback === null) {
            return reset($this->items) ?: null;
        }

        foreach ($this->items as $item) {
            if ($callback($item)) {
                return $item;
            }
        }

        return null;
    }

    /**
     * Vide la collection
     */
    public function clear(): self
    {
        $this->items = [];
        return $this;
    }

    /**
     * Convertit en array
     *
     * @return array<int|string, T>
     */
    public function toArray(): array
    {
        return $this->items;
    }

    /**
     * Vérifie si la collection est vide
     */
    public function isEmpty(): bool
    {
        return empty($this->items);
    }

    // Iterator interface
    public function rewind(): void
    {
        $this->position = 0;
    }

    #[\ReturnTypeWillChange]
    public function current()
    {
        $keys = array_keys($this->items);
        return $this->items[$keys[$this->position]] ?? null;
    }

    #[\ReturnTypeWillChange]
    public function key()
    {
        $keys = array_keys($this->items);
        return $keys[$this->position] ?? null;
    }

    public function next(): void
    {
        $this->position++;
    }

    public function valid(): bool
    {
        $keys = array_keys($this->items);
        return isset($keys[$this->position]);
    }

    // Countable interface
    public function count(): int
    {
        return count($this->items);
    }

    // ArrayAccess interface
    public function offsetExists(mixed $offset): bool
    {
        return isset($this->items[$offset]);
    }

    #[\ReturnTypeWillChange]
    public function offsetGet(mixed $offset): mixed
    {
        return $this->items[$offset] ?? null;
    }

    public function offsetSet(mixed $offset, mixed $value): void
    {
        if ($offset === null) {
            $this->items[] = $value;
        } else {
            $this->items[$offset] = $value;
        }
    }

    public function offsetUnset(mixed $offset): void
    {
        unset($this->items[$offset]);
    }
}
