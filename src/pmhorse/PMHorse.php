<?php namespace pmhorse;

use pocketmine\plugin\PluginBase;

use pocketmine\entity\Entity;
use pmhorse\entities\Horse;

class PMHorse extends PluginBase{

	public static $instance = null;

	public $riding_data = [];

	public function onEnable() : void{
		self::$instance = $this;

		Entity::registerEntity(Horse::class, true);

		$this->getServer()->getPluginManager()->registerEvents(new MainListener($this), $this);
	}

	public function onDisable() : void{

	}

	public static function getInstance() : ?PMHorse{
		return self::$instance;
	}

}