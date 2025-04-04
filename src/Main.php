<?php

declare(strict_types=1);

namespace GameParrot\Chalkboard;

use GameParrot\Chalkboard\block\BlocksSetup;
use GameParrot\Chalkboard\item\ItemsSetup;
use GameParrot\Chalkboard\listener\ChalkboardListener;
use pocketmine\plugin\PluginBase;
use pocketmine\scheduler\AsyncTask;

class Main extends PluginBase {
	public function onEnable() : void {
		BlocksSetup::registerBlocks();
		ItemsSetup::registerItems();
		$this->getServer()->getAsyncPool()->addWorkerStartHook(function(int $worker) : void {
			$this->getServer()->getAsyncPool()->submitTaskToWorker(new class  extends AsyncTask {
				public function onRun() : void {
					BlocksSetup::registerBlocks();
				}
			}, $worker);
		});
		$this->getServer()->getPluginManager()->registerEvents(new ChalkboardListener(), $this);
	}
}
