<?php

declare(strict_types=1);

namespace GameParrot\Chalkboard\block;

use GameParrot\Chalkboard\block\tile\ChalkboardBlock;
use pocketmine\block\Block;
use pocketmine\block\RuntimeBlockStateRegistry;
use pocketmine\block\tile\TileFactory;
use pocketmine\data\bedrock\block\BlockStateNames;
use pocketmine\data\bedrock\block\BlockTypeNames;
use pocketmine\data\bedrock\block\convert\BlockStateReader;
use pocketmine\data\bedrock\block\convert\BlockStateWriter;
use pocketmine\item\StringToItemParser;
use pocketmine\world\format\io\GlobalBlockStateHandlers;

class BlocksSetup {
	public static function registerBlocks() : void {
		$chalkboard = ExtraVanillaBlocks::CHALKBOARD();
		self::registerBlockComplex($chalkboard, BlockTypeNames::CHALKBOARD, function(BlockStateReader $reader) use ($chalkboard) : Block {
			$b = clone $chalkboard;
			$b->setDirection($reader->readInt(BlockStateNames::DIRECTION));
			return $b;
		}, function(Chalkboard $block) : BlockStateWriter {
			$w = new BlockStateWriter(BlockTypeNames::CHALKBOARD);
			$w->writeInt(BlockStateNames::DIRECTION, $block->getDirection());
			return $w;
		});
		TileFactory::getInstance()->register(ChalkboardBlock::class, ["ChalkboardBlock"]);
	}

	/**
	 * @phpstan-template TBlockType of Block
	 * @phpstan-param TBlockType $block
	 * @phpstan-param \Closure(BlockStateReader) : Block $deserialize
	 * @phpstan-param \Closure(TBlockType) : BlockStateWriter $serializer
	 */
	public static function registerBlockComplex(Block $block, string $name, \Closure $deserialize, \Closure $serializer) : void {
		GlobalBlockStateHandlers::getDeserializer()->map($name, $deserialize);
		GlobalBlockStateHandlers::getSerializer()->map($block, $serializer);
		StringToItemParser::getInstance()->registerBlock($name, fn() => clone $block);
		RuntimeBlockStateRegistry::getInstance()->register($block);
	}
}
