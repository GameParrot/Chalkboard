<?php

declare(strict_types=1);
namespace GameParrot\Chalkboard\item;

use pocketmine\item\Item;
use pocketmine\item\ItemIdentifier;
use pocketmine\item\ItemTypeIds;
use pocketmine\utils\CloningRegistryTrait;

/**
 * This doc-block is generated automatically, do not modify it manually.
 * This must be regenerated whenever registry members are added, removed or changed.
 * @see build/generate-registry-annotations.php
 * @generate-registry-docblock
 *
 * @method static \GameParrot\Chalkboard\item\Board BOARD()
 */
final class ExtraVanillaItems {
	use CloningRegistryTrait;

	private function __construct() {
		//NOOP
	}

	protected static function register(string $name, Item $item) : void {
		self::_registryRegister($name, $item);
	}

	/**
	 * @return Item[]
	 * @phpstan-return array<string, Item>
	 */
	public static function getAll() : array {
		//phpstan doesn't support generic traits yet :(
		/** @var Item[] $result */
		$result = self::_registryGetAll();
		return $result;
	}

	protected static function setup() : void {
		$chalkboardTypeId = ItemTypeIds::newId();
		self::register("board", new Board(new ItemIdentifier($chalkboardTypeId), "Board"));
	}
}
