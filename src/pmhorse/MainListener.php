<?php namespace pmhorse;

use pocketmine\event\Listener;
use pocketmine\event\player\{
	PlayerJoinEvent, PlayerQuitEvent
};

use pocketmine\network\mcpe\protocol\{
	RiderJumpPacket,
	InteractPacket,
	InventoryTransactionPacket
};

use pocketmine\Server;

class MainListener implements Listener{

	public $plugin;

	public function __construct(PMHorse $plugin){
		$this->plugin = $plugin;
	}

	public function onJoin(PlayerJoinEvent $e){
		$player = $e->getPlayer();
	}

	public function onQuit(PlayerQuitEvent $e){
		$player = $e->getPlayer();

		if(isset($this->plugin->riding_data[$player->getName()])){
			Server::getInstance()->findEntity($this->plugin->riding_data[$player->getName()])->dismountRider();
			unset($this->plugin->riding_data[$player->getName()]);
		}
	}
}