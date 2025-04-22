<?php
namespace Jibix\Replay\listener\record;
use Jibix\Replay\replay\action\type\entity\EntityArmorEquipAction;
use Jibix\Replay\replay\recorder\Recorder;
use pocketmine\entity\Living;
use pocketmine\inventory\CallbackInventoryListener;
use pocketmine\inventory\Inventory;
use pocketmine\inventory\InventoryListener;
use pocketmine\item\Item;


/**
 * Class RecordInventoryListener
 * @author Jibix
 * @date 26.12.2024 - 00:45
 * @project Replay
 */
class RecordInventoryListener implements InventoryListener{

    /** @var CallbackInventoryListener[] */
    private static array $listeners = [];

    public function __construct(private Recorder $recorder){}

    public function onSlotChange(Inventory $inventory, int $slot, Item $oldItem): void{
        $holder = $inventory->getHolder();
        if (!$holder->isAlive()) return;
        $contents = $inventory->getContents(true);
        $contents[$slot] = $inventory->getItem($slot);
        $this->recorder->addAction(EntityArmorEquipAction::create($holder->getId(), $contents));
    }

    public function onContentChange(Inventory $inventory, array $oldContents): void{
        $holder = $inventory->getHolder();
        if (!$holder->isAlive()) return;
        $this->recorder->addAction(EntityArmorEquipAction::create($holder->getId(), $inventory->getContents(true)));
    }

    public static function addListener(Recorder $recorder, Living $living): void{
        if (isset(self::$listeners[$id = $living->getId()])) return;
        $living->getArmorInventory()->getListeners()->add(self::$listeners[$id] = new self($recorder));
    }

    public static function removeListeners(Living $living): void{
        if (!isset(self::$listeners[$id = $living->getId()])) return;
        $living->getArmorInventory()->getListeners()->remove(self::$listeners[$id]);
    }
}