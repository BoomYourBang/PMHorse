<?php namespace pmhorse\entities;

use pocketmine\entity\{
	Animal, Rideable,
	Attribute
};

use pmhorse\inventory\HorseInventory;
use pocketmine\inventory\{
	Inventory, InventoryHolder
};

use pocketmine\network\mcpe\protocol\{
	SetActorLinkPacket,
	LevelSoundEventPacket,
	MobArmorEquipmentPacket,

	types\EntityLink,
	types\inventory\ItemStackWrapper
};

use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\{
	item\Item, math\Vector3,
	Player, Server
};

use pmhorse\PMHorse;

class Horse extends Animal implements Rideable, InventoryHolder{

	const NETWORK_ID = self::HORSE;

	public $width = 1.4;
	public $height = 1.6;

	public $jump_power = 0.0;
	public $jump_ticks = 0;

	public $rearing_count = 0;

	public $inventory;
	public $rider = null;

	//Base behavior
	protected function addAttributes(): void{
		parent::addAttributes();
		$this->attributeMap->addAttribute(Attribute::getAttribute(14));
	}

	protected function initEntity(): void{
		parent::initEntity();
		$this->setSaddled((bool)$this->namedtag->getByte("Saddled", 0));
		$this->setChested((bool)$this->namedtag->getByte("Chested", 0));

		if($this->namedtag->hasTag("Variant") && $this->namedtag->hasTag("MarkVariant")){
			$this->getDataPropertyManager()->setInt(self::DATA_VARIANT, $this->namedtag->getInt("Variant"));
			$this->getDataPropertyManager()->setInt(self::DATA_MARK_VARIANT, $this->namedtag->getInt("MarkVariant"));
		}else{
			$this->getDataPropertyManager()->setInt(self::DATA_VARIANT, mt_rand(0, 7));
			$this->getDataPropertyManager()->setInt(self::DATA_MARK_VARIANT, mt_rand(0, 5));
		}
		$this->inventory = new HorseInventory($this);

		if($this->namedtag->hasTag("ArmorItem")){
			$this->inventory->armorUp(Item::nbtDeserialize($this->namedtag->getCompoundTag("ArmorItem")));
		}

		if($this->namedtag->hasTag("SaddleItem")){
			$this->inventory->saddleUp(Item::nbtDeserialize($this->namedtag->getCompoundTag("SaddleItem")));
		}
	}

	public function saveNBT(): void{
		parent::saveNBT();
		$this->namedtag->setByte("Saddled", (int)$this->isSaddled());
		$this->namedtag->setByte("Chested", (int)$this->isChested());

		$this->namedtag->setInt("Variant", $this->getDataPropertyManager()->getInt(self::DATA_VARIANT));
		$this->namedtag->setInt("MarkVariant", $this->getDataPropertyManager()->getInt(self::DATA_MARK_VARIANT));

		if($this->getInventory() !== null){
			$this->namedtag->setTag($this->getInventory()->getSaddle()->nbtSerialize(-1, "SaddleItem"));
			$this->namedtag->setTag($this->getInventory()->getArmor()->nbtSerialize(-1, "ArmorItem"));
		}
	}

	public function fall(float $fallDistance): void{
		$damage = ceil($fallDistance / 2 - 3);
		if($damage > 0){
			$this->attack(new EntityDamageEvent($this, EntityDamageEvent::CAUSE_FALL, $damage));
			if(($rider = $this->getRider()) !== null){
				$rider->attack(new EntityDamageEvent($rider, EntityDamageEvent::CAUSE_FALL, $damage));
			}
		}
	}

	public function entityBaseTick(int $tickDiff = 1): bool{
		if($this->getRider() !== null){
			$player = Server::getInstance()->getPlayer($this->getRider());
			$this->move($player->getMotion()->x, $player->getMotion()->y, $player->getMotion()->z);
			$this->setRotation($player->yaw, $player->pitch);
		}
		if($this->isRearing()){

		}
		$this->updateMovement();
		return parent::entityBaseTick($tickDiff);
	}

	public function rightClick(Player $player){
		if($player->isSneaking()){
			if($this->isTamed()){
				$player->addWindow($this->getInventory());
			}else{
				$this->rear();
			}
			return;
		}
		if(!$this->isBaby() && $this->getRider() === null && !isset(PMHorse::getInstance()->riding_data[$player->getName()])){
			$this->setRider($player);
		}
	}

	//Horse stuff
	public function setJumpPower(float $power){
		if($this->isSaddled()){
			if($power < 0){
				$power = 0;
			}else{
				if($power >= 90){
					$this->jump_power = 1.0;
				}else{
					$this->jump_power = 0.4 + 0.4 * $power / 90;
				}
				$this->rear(false);
			}
		}
	}

	public function getJumpStrength() : float{
		return $this->attributeMap->getAttribute(14)->getValue();
	}

	public function setJumpStrength(float $value){
		$this->attributeMap->getAttribute(14)->setValue($value);
	}

	public function isRearing(){
		return $this->getGenericFlag(self::DATA_FLAG_REARING);
	}

	public function setRearing(bool $value){
		$this->setGenericFlag(self::DATA_FLAG_REARING, $value);
	}

	public function rear(bool $playSound = true){
		$this->setRearing(true);
		$this->rearing_count = 1;

		if($playSound){
			$this->level->broadcastLevelSoundEvent($this, LevelSoundEventPacket::SOUND_MAD, -1, static::NETWORK_ID);
		}
	}

	public function isSaddled(){
		return $this->getGenericFlag(self::DATA_FLAG_SADDLED);
	}

	public function setSaddled(bool $value = true){
		$this->setGenericFlag(self::DATA_FLAG_SADDLED, $value);
		$this->setGenericFlag(self::DATA_FLAG_CAN_POWER_JUMP, $value);
	}

	public function isChested(){
		return $this->getGenericFlag(self::DATA_FLAG_CHESTED);
	}

	public function setChested(bool $value = true){
		$this->setGenericFlag(self::DATA_FLAG_CHESTED, $value);
	}

	//Riding stuff
	public function setRider(Player $player){
		PMHorse::getInstance()->riding_data[$player->getName()] = $this->getId();
		$this->rider = $player->getName();

		$player->setGenericFlag(self::DATA_FLAG_RIDING);
		$player->getDataPropertyManager()->setVector3(self::DATA_RIDER_SEAT_POSITION, new Vector3(0, 1.8, -0.2));
		$this->setGenericFlag(self::DATA_FLAG_WASD_CONTROLLED);
		$this->getDataPropertyManager()->setByte(self::DATA_CONTROLLING_RIDER_SEAT_NUMBER, 0);

		$this->setRotation($player->yaw, $player->pitch);
		$pk = new SetActorLinkPacket();
		$pk->link = new EntityLink($this->getId(), $player->getId(), EntityLink::TYPE_RIDER, true, true);
		Server::getInstance()->broadcastPacket($this->getViewers(), $pk);
	}

	public function dismountRider(){
		if($this->getRider() !== null && ($player = Server::getInstance()->getPlayer($this->getRider())) !== null){
			$this->setGenericFlag(self::DATA_FLAG_WASD_CONTROLLED, false);
			$player->setGenericFlag(self::DATA_FLAG_RIDING, false);
			$player->getDataPropertyManager()->removeProperty(self::DATA_RIDER_SEAT_POSITION);

			$pk = new SetActorLinkPacket();
			$pk->link = new EntityLink($this->getId(), $player->getId(), EntityLink::TYPE_REMOVE, true, true);
			Server::getInstance()->broadcastPacket($this->getViewers(), $pk);

			unset(PMHorse::getInstance()->riding_data[$this->getRider()]);
			$this->rider = null;

			$this->jump_power = 0.0;
			$this->rearing_count = 0;
		}
	}

	public function getRider(){
		return $this->rider;
	}

	//Other data
	public function canSaveWithChunk() : bool{
		return true;
	}

	public function getName(): string{
		return "Horse";
	}

	public function getInventory(){
		return $this->inventory;
	}

	public function getDrops(): array{
		return [
			Item::get(Item::LEATHER, 0, mt_rand(0, 2))
		];
	}

	protected function sendSpawnPacket(Player $player): void{
		parent::sendSpawnPacket($player);
		$this->updateArmor([$player]);
	}

	protected function doHitAnimation(): void{
		parent::doHitAnimation();
		$this->updateArmor(); //???????????????????????????????
	}

	public function updateArmor(array $peeps = []){
		if(empty($peeps)) $peeps = $this->getViewers();

		$air = ItemStackWrapper::legacy(Item::get(0));
		$pk = new MobArmorEquipmentPacket();
		$pk->entityRuntimeId = $this->getId();
		$pk->head = $pk->legs = $pk->feet = $air;
		$pk->chest = ItemStackWrapper::legacy($this->getInventory()->getArmor());

		foreach($peeps as $player) $player->dataPacket($pk);
	}
}