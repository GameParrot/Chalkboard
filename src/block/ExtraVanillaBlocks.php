<?php

declare(strict_types=1);

namespace GameParrot\Chalkboard\block;

use GameParrot\Chalkboard\block\tile\ChalkboardBlock;
use pocketmine\block\Block;
use pocketmine\block\BlockBreakInfo;
use pocketmine\block\BlockIdentifier;
use pocketmine\block\BlockToolType;
use pocketmine\block\BlockTypeIds;
use pocketmine\block\BlockTypeInfo;
use pocketmine\utils\CloningRegistryTrait;

/**
 * This doc-block is generated automatically, do not modify it manually.
 * This must be regenerated whenever registry members are added, removed or changed.
 * @see build/generate-registry-annotations.php
 * @generate-registry-docblock
 *
 * @method static \GameParrot\Chalkboard\block\Chalkboard CHALKBOARD()
 */
final class ExtraVanillaBlocks {
	use CloningRegistryTrait;

	private function __construct() {
		//NOOP
	}

	protected static function register(string $name, Block $block) : void {
		self::_registryRegister($name, $block);
	}

	/**
	 * @return Block[]
	 * @phpstan-return array<string, Block>
	 */
	public static function getAll() : array {
		//phpstan doesn't support generic traits yet :(
		/** @var Block[] $result */
		$result = self::_registryGetAll();
		return $result;
	}

	protected static function setup() : void {
		$chalkboardTypeId = BlockTypeIds::newId();
		self::register("chalkboard", new Chalkboard(new BlockIdentifier($chalkboardTypeId, ChalkboardBlock::class), "Chalkboard", new BlockTypeInfo(new BlockBreakInfo(1, BlockToolType::NONE, 0, 5))));
	}
}
