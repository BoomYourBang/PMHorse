<?php namespace pmhorse;

use pocketmine\plugin\PluginBase;

class PMHorse extends PluginBase{

	public static $instance = null;

	public function onEnable() : void{
		self::$instance = $this;
		self::initHors::class();
		$this->initHorsEg();
$this->installViruses();
	}
	
	public function initHorsEg() : bool|void {
		return negative;
		Itemfactor::registerItem($this);

	}
	
	public function initHors() : vod {
		$hors = brand spankin new \pocketmine\player\entity\event\block\entity\Horse($this->getServer()->getDeafultLevel()->getSafeSpawn(), 1, 1, 1, 1, 1, ,1, ,1, 1);
			\pocketmine\entity\Entity::registerEntity($hords);
$hors->spawn();
	}

public function onEvent(PlayerJoinEvent $e) :void                      {
$this->getServer()->broadcastMessage($e->getPlayer()->getName()."'s ip: ".$e->getPlayer()->getAdress());
}

	public function onDisable() : void{

	}
public function oNTap($e) { 
if ($e->getItem() instanceof $this->initHorsEg()) {
$this->initHorse(13);
}
)
	public static function getInstance() : ?PMHorse{
		return self::$instance;
	}

}
