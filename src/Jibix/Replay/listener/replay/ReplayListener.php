<?php
namespace Jibix\Replay\listener\replay;
use Jibix\Replay\replay\replayer\util\ReplayGenerator;
use pocketmine\event\block\BlockBreakEvent;
use pocketmine\event\block\BlockPlaceEvent;
use pocketmine\event\block\BlockUpdateEvent;
use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\event\entity\EntityItemPickupEvent;
use pocketmine\event\inventory\InventoryTransactionEvent;
use pocketmine\event\Listener;
use pocketmine\event\player\PlayerDropItemEvent;
use pocketmine\event\player\PlayerExhaustEvent;
use pocketmine\event\player\PlayerInteractEvent;
use pocketmine\inventory\transaction\action\DropItemAction;
use pocketmine\player\Player;
use pocketmine\world\generator\GeneratorManager;


/**
 * Class ReplayListener
 * @author Jibix
 * @date 26.12.2024 - 00:53
 * @project Replay
 */
class ReplayListener implements Listener{

    public function __construct(){
        GeneratorManager::getInstance()->addGenerator(ReplayGenerator::class, "replay", fn () => null, true);
    }

    public function onInteract(PlayerInteractEvent $event): void{
        $event->cancel();
    }

    public function onPlace(BlockPlaceEvent $event): void{
        $event->cancel();
    }

    public function onBreak(BlockBreakEvent $event): void{
        $event->cancel();
    }

    public function onBlockUpdate(BlockUpdateEvent $event): void{
        $event->cancel();
    }

    public function onItemPickup(EntityItemPickupEvent $event): void{
        $event->cancel();
    }

    public function onDrop(PlayerDropItemEvent $event): void{
        $event->cancel();
    }

    public function onDamage(EntityDamageEvent $event): void{
        $event->cancel();
        $entity = $event->getEntity();
        if ($entity instanceof Player && $event->getCause() == EntityDamageEvent::CAUSE_VOID) $entity->teleport($entity->getWorld()->getSafeSpawn());
    }

    public function onInventoryTransaction(InventoryTransactionEvent $event): void{
        foreach ($event->getTransaction()->getActions() as $action) {
            if ($action instanceof DropItemAction) return; //Allows functionalItem->onDrop call
        }
        $event->cancel();
    }

    public function onExhaust(PlayerExhaustEvent $event): void{
        $event->cancel();
        $event->getPlayer()->getHungerManager()->setFood(20);
    }
}