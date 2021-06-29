<?php namespace pmhorse;

use pocketmine\plugin\PluginBase;

class PMHorse extends PluginBase{

	public static $instance = null;

	public function onEnable() : void{
		self::$instance = $this;
	}

	public function onDisable() : void{

	}

	public static function getInstance() : ?PMHorse{
		return self::$instance;
	}

}