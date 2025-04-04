<?php

declare(strict_types=1);

namespace GameParrot\Chalkboard\listener;

use GameParrot\Chalkboard\block\Chalkboard;
use GameParrot\Chalkboard\block\tile\ChalkboardBlock;
use pocketmine\event\Listener;
use pocketmine\event\player\PlayerPostChunkSendEvent;
use pocketmine\event\server\DataPacketReceiveEvent;
use pocketmine\math\Vector3;
use pocketmine\nbt\tag\ByteTag;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\nbt\tag\StringTag;
use pocketmine\network\mcpe\protocol\BlockActorDataPacket;
use pocketmine\network\PacketHandlingException;
use function get_debug_type;

class ChalkboardListener implements Listener {
	public function onDataPacket(DataPacketReceiveEvent $event) : void {
		$packet = $event->getPacket();
		if ($packet->pid() !== BlockActorDataPacket::NETWORK_ID) {
			return;
		}
		/** @var BlockActorDataPacket $packet */
		$player = $event->getOrigin()->getPlayer();
		if ($player === null) {
			return;
		}
		$pos = new Vector3($packet->blockPosition->getX(), $packet->blockPosition->getY(), $packet->blockPosition->getZ());
		if ($pos->distanceSquared($player->getLocation()) > 10000) {
			return;
		}

		$block = $player->getLocation()->getWorld()->getBlock($pos);
		$nbt = $packet->nbt->getRoot();
		if (!($nbt instanceof CompoundTag)) {
			return;
		}

		if ($block instanceof Chalkboard) {
			$text = $nbt->getTag(ChalkboardBlock::TAG_TEXT);
			if (!$text instanceof StringTag) {
				throw new PacketHandlingException("Invalid tag type " . get_debug_type($text) . " for tag \"" . ChalkboardBlock::TAG_TEXT . "\" in chalkboard update data");
			}
			$locked = $nbt->getTag(ChalkboardBlock::TAG_LOCKED);
			if (!$locked instanceof ByteTag) {
				throw new PacketHandlingException("Invalid tag type " . get_debug_type($locked) . " for tag \"" . ChalkboardBlock::TAG_LOCKED . "\" in chalkboard update data");
			}
			try {
				if (!$block->updateText($player, $text->getValue(), $locked->getValue() === 1)) {
					foreach ($player->getWorld()->createBlockUpdatePackets([$pos]) as $updatePacket) {
						$event->getOrigin()->sendDataPacket($updatePacket);
					}
				}
			} catch(\UnexpectedValueException $e) {
				throw PacketHandlingException::wrap($e);
			}
		}
	}

	public function onChunkLoad(PlayerPostChunkSendEvent $ev) : void {
		$chunk = $ev->getPlayer()->getWorld()->getChunk($ev->getChunkX(), $ev->getChunkZ());
		if ($chunk !== null) {
			foreach ($chunk->getTiles() as $tile) {
				if ($tile instanceof ChalkboardBlock && $tile->isBase()) {
					$oldOwner = $tile->getOwnerRuntimeId();
					$tile->updateOwnerId();
					if ($tile->getOwnerRuntimeId() !== $oldOwner) {
						$tile->getPosition()->getWorld()->setBlock($tile->getPosition(), $tile->getBlock());
					}
				}
			}
		}
	}
}
