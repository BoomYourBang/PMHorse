<?php namespace pmhorse\inventory;

use pocketmine\Player;

use pocketmine\item\Item;
use pocketmine\inventory\ContainerInventory;

use pocketmine\network\mcpe\protocol\{
	LevelSoundEventPacket,
	UpdateEquipPacket,

	types\WindowTypes
};

use pocketmine\nbt\NetworkLittleEndianNBTStream;
use pocketmine\nbt\tag\{
	CompoundTag,
	ListTag,
	IntTag
};

class HorseInventory extends ContainerInventory{

	public function getName() : string{
		return "Horse";
	}

	public function getDefaultSize() : int{
		return 2; //right?
	}

	public function getNetworkType() : int{
		return WindowTypes::HORSE;
	}

	public function saddleUp(Item $item){
		$this->setItem(0, $item);
	}

	public function armorUp(Item $item){
		$this->setItem(1, $item);
		$this->getHolder()->updateArmor();
	}

	public function getSaddle(){
		return $this->getItem(0);
	}

	public function getArmor(){
		return $this->getItem(1);
	}

	public function onSlotChange(int $index, Item $before, bool $send) : void{
		parent::onSlotChange($index, $before, $send);
		if($index == 0){
			$this->getHolder()->setSaddled($before->getId() == Item::SADDLE);
			$this->getHolder()->getLevel()->broadcastLevelSoundEvent($this->getHolder(), LevelSoundEventPacket::SOUND_SADDLE);
		}
	}

	public function onOpen(Player $who) : void{
		$pk = new UpdateEquipPacket();
		$pk->entityUniqueId = $this->getHolder()->getId();
		$pk->windowSlotCount = 0;
		$pk->windowType = $this->getNetworkType();
		$pk->windowId = $who->getWindowId($this);
		$pk->namedtag = $this->toString();
		$who->dataPacket($pk);

		parent::onOpen($who);

		$this->getHolder()->updateArmor([$who]);
	}

	public function toString(){
		return (new NetworkLittleEndianNBTStream())->write(new CompoundTag("", [
			new ListTag("slots", [
				new CompoundTag("", [
					new ListTag("acceptedItems", [
						new CompoundTag("", [
							(Item::get(Item::SADDLE))->nbtSerialize(-1, "slotItem")
						])
					]),
					$this->getSaddle()->nbtSerialize(-1, "item"),
					new IntTag("slotNumber", 0)
				]),
				new CompoundTag("", [
					new ListTag("acceptedItems", [ //so you can put any item here? lol
						new CompoundTag("", [
							(Item::get(Item::HORSE_ARMOR_DIAMOND))->nbtSerialize(-1, "slotItem")
						]),
						new CompoundTag("", [
							(Item::get(Item::HORSE_ARMOR_GOLD))->nbtSerialize(-1, "slotItem")
						]),
						new CompoundTag("", [
							(Item::get(Item::HORSE_ARMOR_IRON))->nbtSerialize(-1, "slotItem")
						]),
						new CompoundTag("", [
							(Item::get(Item::HORSE_ARMOR_LEATHER))->nbtSerialize(-1, "slotItem")
						])
					]),
					$this->getArmor()->nbtSerialize(-1, "item"),
					new IntTag("slotNumber", 1)
				])
			])
		]));
	}
	
}