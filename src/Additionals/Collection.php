<?php declare(strict_types=1);

namespace ApiPHP\Additionals;

use ArrayAccess;
use InvalidArgumentException;

class Collection implements ArrayAccess
{
	protected array $items = [];

	public function __construct(array $items = [])
	{
		$this->items = $items;
	}

	// Dinamikus tulajdonság hozzáférés
	public function __get($key)
	{
		return $this->items[$key] ?? throw new InvalidArgumentException("Array key [{$key}] does not exists!");
	}

	// Elem hozzáadása
	public function add($key, $value): void
	{
		$this->items[$key] = $value;
	}

	// Visszaadja az összes elemet
	public function all(): array
	{
		return $this->items;
	}

	// ArrayAccess metódusok megvalósítása
	public function offsetSet($offset, $value): void
	{
		if (is_null($offset)) {
			$this->items[] = $value;
		} else {
			$this->items[$offset] = $value;
		}
	}

	public function offsetExists($offset): bool
	{
		return isset($this->items[$offset]);
	}

	public function offsetUnset($offset): void
	{
		unset($this->items[$offset]);
	}

	public function offsetGet($offset): mixed
	{
		return $this->items[$offset] ?? null;
	}
}
