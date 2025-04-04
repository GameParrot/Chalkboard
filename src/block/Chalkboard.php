<?php

declare(strict_types=1);

namespace GameParrot\Chalkboard\block;

use GameParrot\Chalkboard\block\tile\ChalkboardBlock;
use GameParrot\Chalkboard\event\ChalkboardChangeEvent;
use GameParrot\Chalkboard\item\ExtraVanillaItems;
use pocketmine\block\Block;
use pocketmine\block\Transparent;
use pocketmine\data\runtime\RuntimeDataDescriber;
use pocketmine\item\Item;
use pocketmine\math\Facing;
use pocketmine\math\Vector3;
use pocketmine\player\Player;
use pocketmine\Server;
use pocketmine\world\BlockTransaction;
use function assert;
use function floor;
use function max;
use function min;
use function strlen;

class Chalkboard extends Transparent {
	private int $direction = 0;
	private string $text = "";
	private string $ownerId = "";
	private bool $locked = true;
	private bool $onGround;
	private int $size = 0;
	private Vector3 $basePos;
	public function readStateFromWorld() : Block {
		parent::readStateFromWorld();
		$tile = $this->getBaseTile();
		$this->text = $tile->getText();
		$this->ownerId = $tile->getOwnerUUID();
		$this->locked = $tile->getLocked();
		$tile = $this->getRealTile();
		$this->size = $tile->getSize();
		$this->basePos = $tile->getBasePos();
		$this->onGround = $tile->isOnGround();
		return $this;
	}
	public function writeStateToWorld() : void {
		parent::writeStateToWorld();
		$tile = $this->getBaseTile();
		$tile->setText($this->text);
		$tile->setOwnerUUID($this->ownerId);
		$tile->setLocked($this->locked);
		$tile = $this->getRealTile();
		$tile->setSize($this->size);
		$tile->setBasePos($this->basePos);
		$tile->setOnGround($this->onGround);
	}
	protected function describeBlockOnlyState(RuntimeDataDescriber $w) : void {
		$w->int(4, $this->direction);
	}
	public function setDirection(int $direction) : self {
		$this->direction = $direction;
		return $this;
	}

	protected function recalculateCollisionBoxes() : array {
		return [];
	}
	public function isSolid() : bool {
		return false;
	}

	public function getDirection() : int {
		return $this->direction;
	}

	private function getBaseTile() : ChalkboardBlock {
		$tile = $this->position->getWorld()->getTile($this->position);
		assert($tile instanceof ChalkboardBlock);
		if (!$tile->isBase()) {
			$tile = $this->position->getWorld()->getTile($tile->getBasePos());
			assert($tile instanceof ChalkboardBlock);
		}
		return $tile;
	}

	private function getRealTile() : ChalkboardBlock {
		$tile = $this->position->getWorld()->getTile($this->position);
		assert($tile instanceof ChalkboardBlock);
		return $tile;
	}

	public function updateText(Player $player, string $newText, bool $wantLocked) : bool {
		if (strlen($newText) > 2000) {
			throw new \UnexpectedValueException($player->getName() . " tried to write " . strlen($newText) . " bytes of text onto a chalkboard (bigger than max 2000)");
		}
		if (!$this->isOwner($player)) {
			$wantLocked = $this->locked;
		}
		$ev = new ChalkboardChangeEvent($this,$player, $newText, $wantLocked);
		if (!$this->isOwner($player) && $this->locked) {
			$ev->cancel();
		}
		$ev->call();
		if (!$ev->isCancelled()) {
			$this->setText($ev->getText());
			$this->setLocked($ev->getWantLocked());
			$this->position->getWorld()->setBlock($this->position, $this);
			return true;
		}
		return false;
	}

	public function getSize() : int {
		return $this->size;
	}
	public function setSize(int $size) : self {
		$this->size = $size;
		return $this;
	}
	public function getText() : string {
		return $this->text;
	}
	public function setText(string $newText) : void {
		$this->text = $newText;
	}
	public function setOwner(Player $owner) : void {
		$this->ownerId = $owner->getUniqueId()->getBytes();
	}
	public function getOwner() : ?Player {
		return Server::getInstance()->getPlayerByRawUUID($this->ownerId) ?? null;
	}
	public function getOwnerUUID() : string {
		return $this->ownerId;
	}
	public function isOwner(Player $player) : bool {
		return $player->getUniqueId()->getBytes() === $this->ownerId || (Server::getInstance()->isOp($player->getName()) && $player->isCreative(true));
	}
	public function getLocked() : bool {
		return $this->locked;
	}
	public function setLocked(bool $locked) : void {
		$this->locked = $locked;
	}

	public function onInteract(Item $item, int $face, Vector3 $clickVector, ?Player $player = null, array &$returnedItems = []) : bool {
		return true;
	}

	public function place(BlockTransaction $tx, Item $item, Block $blockReplace, Block $blockClicked, int $face, Vector3 $clickVector, ?Player $player = null) : bool {
		if ($face === Facing::DOWN) {
			return false;
		}

		$this->basePos = $blockReplace->getPosition();
		$this->onGround = $face === Facing::UP;

		if ($player !== null) {
			$this->ownerId = $player->getUniqueId()->getBytes();
		}

		$size = $this->size;
		if ($size === 0) {
			if ($face === Facing::UP) {
				if ($player !== null) {
					$this->setDirection($this->getRotationFromYaw($player->getLocation()->getYaw()));
				}
			} else {
				$this->setDirection(match ($face) {
					Facing::SOUTH => 0,
					Facing::WEST => 1,
					Facing::NORTH => 2,
					Facing::EAST => 3,
					default => 0,
				});
			}
			return parent::place($tx, $item, $blockReplace, $blockClicked, $face, $clickVector, $player);
		}

		$direction = $face;
		if ($direction === Facing::UP) {
			if ($player !== null) {
				$direction = Facing::opposite($player->getHorizontalFacing());
			} else {
				$direction = Facing::EAST;
			}
		}

		$this->setDirection(match ($direction) {
			Facing::SOUTH => 0,
			Facing::WEST => 1,
			Facing::NORTH => 2,
			Facing::EAST => 3,
			default => 0,
		});

		[$x, $y] = self::sizeToDimensions($size);
		$startPos = $blockReplace->getPosition();

		$direction = Facing::rotateY($direction, false);

		$start = (int) -(($x - 1) / 2);
		$end = (int) ($x / 2);
		$startX = min($startPos->getSide($direction, $start)->getX(), $startPos->getSide($direction, $end)->getX());
		$startZ = min($startPos->getSide($direction, $start)->getZ(), $startPos->getSide($direction, $end)->getZ());
		$endX = max($startPos->getSide($direction, $start)->getX(), $startPos->getSide($direction, $end)->getX());
		$endZ = max($startPos->getSide($direction, $start)->getZ(), $startPos->getSide($direction, $end)->getZ());

		$startY = $startPos->getY();
		$endY = $startPos->getY() + $y - 1;

		for ($cX = $startX; $cX <= $endX; $cX++) {
			for ($cY = $startY; $cY <= $endY; $cY++) {
				for ($cZ = $startZ; $cZ <= $endZ; $cZ++) {
					if (!$blockReplace->getPosition()->getWorld()->getBlockAt($cX, $cY, $cZ)->canBeReplaced()) {
						return false;
					}
					$tx->addBlockAt($cX, $cY, $cZ, clone $this);
				}
			}
		}
		return true;
	}

	public function onBreak(Item $item, ?Player $player = null, array &$returnedItems = []) : bool {
		if ($player !== null && !$this->isOwner($player)) {
			return false;
		}
		return parent::onBreak($item, $player, $returnedItems);
	}

	public function getDrops(Item $item) : array {
		if ($this->basePos->equals($this->getPosition())) {
			return parent::getDrops($item);
		}
		return [];
	}

	private static function getRotationFromYaw(float $yaw) : int {
		return ((int) floor((($yaw + 180) * 16 / 360) + 0.5)) & 0xf;
	}

	private static function sizeToDimensions(int $size) : array {
		return match ($size) {
			0 => [1,1],
			1 => [2,1],
			2 => [3,2],
			default => [0,0]
		};
	}

	public function getAffectedBlocks() : array {
		if ($this->getSize() === 0) {
			return parent::getAffectedBlocks();
		}
		$blocks = [];
		$startPos = $this->basePos;
		$baseBlock = $this->getPosition()->getWorld()->getBlock($startPos);
		if ($baseBlock instanceof Chalkboard) {
			$direction = Facing::rotateY(Facing::opposite(match ($baseBlock->getDirection()) {
				0 => Facing::SOUTH,
				1 => Facing::WEST,
				2 => Facing::NORTH,
				3 => Facing::EAST,
			}), true);
			[$x, $y] = self::sizeToDimensions($baseBlock->getSize());
			$start = (int) -(($x - 1) / 2);
			$end = (int) ($x / 2);
			$startX = min($startPos->getSide($direction, $start)->getX(), $startPos->getSide($direction, $end)->getX());
			$startZ = min($startPos->getSide($direction, $start)->getZ(), $startPos->getSide($direction, $end)->getZ());
			$endX = max($startPos->getSide($direction, $start)->getX(), $startPos->getSide($direction, $end)->getX());
			$endZ = max($startPos->getSide($direction, $start)->getZ(), $startPos->getSide($direction, $end)->getZ());
			$startY = $startPos->getY();
			$endY = $startPos->getY() + $y - 1;

			for ($cX = $startX; $cX <= $endX; $cX++) {
				for ($cY = $startY; $cY <= $endY; $cY++) {
					for ($cZ = $startZ; $cZ <= $endZ; $cZ++) {
						if (($block = $this->getPosition()->getWorld()->getBlockAt($cX, $cY, $cZ)) instanceof Chalkboard) {
							$blocks[] = $block;
						}
					}
				}
			}
		}

		return $blocks;
	}

	public function asItem() : Item {
		$b = ExtraVanillaItems::BOARD();
		$size = $this->size;
		if (($base = $this->getBaseBlock()) !== null) {
			$size = $base->getSize();
		}
		$b->setSize($size);
		return $b;
	}

	public function getBaseBlock() : ?self {
		if (!isset($this->basePos)) {
			return null;
		}
		$startPos = $this->basePos;
		$baseBlock = $this->getPosition()->getWorld()->getBlock($startPos);
		if (!$baseBlock instanceof Chalkboard) {
			return null;
		}
		return $baseBlock;
	}
}
