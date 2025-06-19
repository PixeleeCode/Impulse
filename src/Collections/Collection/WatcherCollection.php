<?php

namespace Impulse\Collections\Collection;

use Impulse\Collections\Collection;

class WatcherCollection extends Collection
{
    public function set(int|string $key, mixed $item): self
    {
        $this->items[$key][] = $item;

        return $this;
    }
}
