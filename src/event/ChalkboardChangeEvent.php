<?php

declare(strict_types=1);

namespace GameParrot\Chalkboard\event;

use GameParrot\Chalkboard\block\Chalkboard;
use pocketmine\event\Cancellable;
use pocketmine\event\CancellableTrait;
use pocketmine\event\player\PlayerEvent;
use pocketmine\player\Player;

class ChalkboardChangeEvent extends PlayerEvent implements Cancellable {
	use CancellableTrait;
	public function __construct(private Chalkboard $chalkboard, Player $player, private string $text, private bool $wantLocked) {
		$this->player = $player;
	}

	public function getText() : string {
		return $this->text;
	}
	public function getWantLocked() : bool {
		return $this->wantLocked;
	}
	public function setText(string $text) : void {
		$this->text = $text;
	}
	public function setWantLocked(bool $wantLocked) : void {
		$this->wantLocked = $wantLocked;
	}

	public function getChalkboard() : Chalkboard {
		return $this->chalkboard;
	}
}
