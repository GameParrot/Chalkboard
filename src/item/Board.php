<?php

declare(strict_types=1);
namespace GameParrot\Chalkboard\item;

use GameParrot\Chalkboard\block\ExtraVanillaBlocks;
use pocketmine\block\Block;
use pocketmine\data\runtime\RuntimeDataDescriber;
use pocketmine\item\Item;

class Board extends Item {
	private int $size = 0;

	public function getSize() : int {
		return $this->size;
	}

	public function setSize(int $size) : self {
		$this->size = $size;
		return $this;
	}

	protected function describeState(RuntimeDataDescriber $w) : void {
		$w->boundedIntAuto(0, 2, $this->size);
	}

	public function getBlock(?int $clickedFace = null) : Block {
		return ExtraVanillaBlocks::CHALKBOARD()->setSize($this->size);
	}

	public function getMaxStackSize() : int {
		return 16;
	}
}
