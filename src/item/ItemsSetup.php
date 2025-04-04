<?php

declare(strict_types=1);
namespace GameParrot\Chalkboard\item;

use pocketmine\data\bedrock\item\ItemSerializerDeserializerRegistrar;
use pocketmine\data\bedrock\item\ItemTypeNames;
use pocketmine\inventory\CreativeCategory;
use pocketmine\inventory\CreativeGroup;
use pocketmine\inventory\CreativeInventory;
use pocketmine\item\Item;
use pocketmine\item\StringToItemParser;
use pocketmine\world\format\io\GlobalItemDataHandlers;

class ItemsSetup {
	public static function registerItems() : void {
		self::registerItemWithMeta(ItemTypeNames::BOARD, ExtraVanillaItems::BOARD(), ["slate" => ExtraVanillaItems::BOARD()->setSize(0), "poster" => ExtraVanillaItems::BOARD()->setSize(1), "board" => ExtraVanillaItems::BOARD()->setSize(2)], function(Board $item, int $meta) : void {
			if ($meta >= 0 && $meta <= 2) {
				$item->setSize($meta);
			}
		}, function(Board $item) : int {
			return $item->getSize();
		});

		$group = new CreativeGroup("Chalkoards", ExtraVanillaItems::BOARD()->setSize(2));
		CreativeInventory::getInstance()->add(ExtraVanillaItems::BOARD()->setSize(0), CreativeCategory::ITEMS, $group);
		CreativeInventory::getInstance()->add(ExtraVanillaItems::BOARD()->setSize(1), CreativeCategory::ITEMS, $group);
		CreativeInventory::getInstance()->add(ExtraVanillaItems::BOARD()->setSize(2), CreativeCategory::ITEMS, $group);
	}

	/**
	 * @phpstan-template TItem of Item
	 * @phpstan-param \Closure(TItem, int) : void $deserializeMeta
	 * @phpstan-param \Closure(TItem) : int       $serializeMeta
	 */
	private static function registerItemWithMeta(string $id, Item $item, array $stringToItemParserNames, \Closure $deserializeMeta, \Closure $serializeMeta) : void {
		$refl = (new \ReflectionClass(ItemSerializerDeserializerRegistrar::class));
		$registrar = $refl->newInstanceWithoutConstructor();
		$refl->getProperty("serializer")->setValue($registrar, GlobalItemDataHandlers::getSerializer());
		$refl->getProperty("deserializer")->setValue($registrar, GlobalItemDataHandlers::getDeserializer());

		$registrar->map1to1ItemWithMeta($id, $item, $deserializeMeta, $serializeMeta);
		foreach ($stringToItemParserNames as $name => $item) {
			StringToItemParser::getInstance()->register($name, fn() => $item);
		}
	}
}
