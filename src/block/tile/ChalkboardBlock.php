<?php

declare(strict_types=1);
namespace GameParrot\Chalkboard\block\tile;

use pocketmine\block\tile\Spawnable;
use pocketmine\math\Vector3;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\player\Player;
use pocketmine\Server;

class ChalkboardBlock extends Spawnable {
	private const TAG_BASE_X = "BaseX"; // Tag_Int
	private const TAG_BASE_Y = "BaseY"; // Tag_Int
	private const TAG_BASE_Z = "BaseZ"; // Tag_Int
	private const TAG_SIZE = "Size"; // Tag_Int
	public const TAG_TEXT = "Text"; // Tag_String
	private const TAG_ON_GROUND = "OnGround"; // Tag_Byte
	public const TAG_LOCKED = "Locked"; // Tag_Byte
	private const TAG_OWNER = "Owner"; // Tag_Long
	private const TAG_PM_OWNER = "PmOwner"; // Tag_String
	private Vector3 $basePos;
	private bool $isBase = true;
	private bool $onGround;
	private bool $locked;
	private string $text = "";
	private int $size = 0;

	private int $owner = -1;
	private string $pmOwner = "";

	protected function addAdditionalSpawnData(CompoundTag $nbt) : void {
		$this->writeCommonData($nbt);
		$this->updateOwnerId();
		if ($this->isBase()) {
			$nbt->setLong(self::TAG_OWNER, $this->owner);
		}
	}

	public function updateOwnerId() : void {
		if ($pl = Server::getInstance()->getPlayerByRawUUID($this->pmOwner)) {
			$this->owner = $pl->getId();
		} else {
			$this->owner = -1;
		}
	}

	public function readSaveData(CompoundTag $nbt) : void {
		$this->basePos = new Vector3($nbt->getInt(self::TAG_BASE_X, 0), $nbt->getInt(self::TAG_BASE_Y, 0), $nbt->getInt(self::TAG_BASE_Z, 0));
		$this->text = $nbt->getString(self::TAG_TEXT, "");
		$this->size = $nbt->getInt(self::TAG_SIZE, 0);
		$this->isBase = $this->basePos->equals($this->position);
		$this->onGround = $nbt->getByte(self::TAG_ON_GROUND, 0) === 1;
		$this->locked = $nbt->getByte(self::TAG_LOCKED, 0) === 1;
		$this->pmOwner = $nbt->getString(self::TAG_PM_OWNER, "");
	}

	protected function writeSaveData(CompoundTag $nbt) : void {
		$this->writeCommonData($nbt);
		$nbt->setString(self::TAG_PM_OWNER, $this->pmOwner);
	}

	private function writeCommonData(CompoundTag $nbt) : void {
		$nbt->setInt(self::TAG_BASE_X, $this->basePos->getX());
		$nbt->setInt(self::TAG_BASE_Y, $this->basePos->getY());
		$nbt->setInt(self::TAG_BASE_Z, $this->basePos->getZ());
		$nbt->setInt(self::TAG_SIZE, $this->size);
		if ($this->isBase()) {
			$nbt->setByte(self::TAG_ON_GROUND, $this->onGround ? 1 : 0);
			$nbt->setByte(self::TAG_LOCKED,  $this->locked ? 1 : 0);
			$nbt->setString(self::TAG_TEXT, $this->text);
		}
	}

	public function isOnGround() : bool {
		return $this->onGround;
	}
	public function setOnGround(bool $onGround) : void {
		$this->onGround = $onGround;
	}
	public function getBasePos() : Vector3 {
		return $this->basePos;
	}
	public function setBasePos(Vector3 $basePos) : void {
		$this->basePos = $basePos;
		$this->isBase = $this->basePos->equals($this->position);
	}
	public function isBase() : bool {
		return $this->isBase;
	}
	public function getSize() : int {
		return $this->size;
	}
	public function setSize(int $size) : void {
		if ($size < 0 || $size > 2) {
			throw new \InvalidArgumentException("Size must be between 0 and 2");
		}
		$this->size = $size;
	}
	public function getText() : string {
		return $this->text;
	}
	public function setText(string $text) : void {
		$this->text = $text;
	}
	public function getLocked() : bool {
		return $this->locked;
	}
	public function setLocked(bool $locked) : void {
		$this->locked = $locked;
	}
	public function getOwner() : ?Player {
		return Server::getInstance()->getPlayerByRawUUID($this->pmOwner) ?? null;
	}
	public function getOwnerRuntimeId() : int {
		return $this->owner;
	}
	public function getOwnerUUID() : string {
		return $this->pmOwner;
	}
	public function setOwner(Player $owner) : void {
		$this->pmOwner = $owner->getUniqueId()->getBytes();
		$this->owner = $owner->getId();
	}
	public function setOwnerUUID(string $id) : void {
		$this->pmOwner = $id;
		if ($pl = Server::getInstance()->getPlayerByRawUUID($this->pmOwner)) {
			$this->owner = $pl->getId();
		}
	}
}
