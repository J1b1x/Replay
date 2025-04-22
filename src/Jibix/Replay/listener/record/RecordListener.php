<?php
namespace Jibix\Replay\listener\record;
use Jibix\Replay\replay\action\type\actor\ActorDeathAction;
use Jibix\Replay\replay\action\type\actor\ActorEventAction;
use Jibix\Replay\replay\action\type\actor\ActorSetMetadataAction;
use Jibix\Replay\replay\action\type\entity\EntityChangeSkinAction;
use Jibix\Replay\replay\action\type\entity\EntityEquipAction;
use Jibix\Replay\replay\action\type\entity\EntityMoveAction;
use Jibix\Replay\replay\action\type\entity\EntityPlayEmoteAction;
use Jibix\Replay\replay\action\type\player\PlayerAnimationAction;
use Jibix\Replay\replay\action\type\player\PlayerChatAction;
use Jibix\Replay\replay\action\type\world\BlockEventAction;
use Jibix\Replay\replay\action\type\world\LevelEventAction;
use Jibix\Replay\replay\action\type\world\SignChangeAction;
use Jibix\Replay\replay\log\type\PlayerDeathEventLog;
use Jibix\Replay\replay\recorder\Recorder;
use pocketmine\entity\Human;
use pocketmine\entity\Location;
use pocketmine\entity\object\ItemEntity;
use pocketmine\event\block\SignChangeEvent;
use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\event\entity\EntityDeathEvent;
use pocketmine\event\entity\EntityDespawnEvent;
use pocketmine\event\entity\EntitySpawnEvent;
use pocketmine\event\entity\EntityTeleportEvent;
use pocketmine\event\entity\ItemSpawnEvent;
use pocketmine\event\Listener;
use pocketmine\event\player\PlayerChangeSkinEvent;
use pocketmine\event\player\PlayerChatEvent;
use pocketmine\event\player\PlayerGameModeChangeEvent;
use pocketmine\event\player\PlayerItemHeldEvent;
use pocketmine\event\player\PlayerMoveEvent;
use pocketmine\event\player\PlayerRespawnEvent;
use pocketmine\event\server\DataPacketReceiveEvent;
use pocketmine\event\server\DataPacketSendEvent;
use pocketmine\event\world\ChunkLoadEvent;
use pocketmine\lang\Translatable;
use pocketmine\network\mcpe\protocol\ActorEventPacket;
use pocketmine\network\mcpe\protocol\AnimatePacket;
use pocketmine\network\mcpe\protocol\BlockEventPacket;
use pocketmine\network\mcpe\protocol\EmotePacket;
use pocketmine\network\mcpe\protocol\LevelEventPacket;
use pocketmine\network\mcpe\protocol\SetActorDataPacket;
use pocketmine\network\mcpe\protocol\types\ActorEvent;
use pocketmine\player\GameMode;
use pocketmine\player\Player;
use pocketmine\scheduler\ClosureTask;
use pocketmine\scheduler\TaskHandler;
use pocketmine\world\World;


/**
 * Class RecordListener
 * @author Jibix
 * @date 26.12.2024 - 00:47
 * @project Replay
 */
class RecordListener implements Listener{

    private TaskHandler $task;

    /** @var Location[] */
    private array $locations = [];
    private array $skins = []; //God how much i love pm... makes sense that there's no EntityMoveEvent due to performance issues, but EntityChangeSkinEvent??? cmn dylan

    public function __construct(private Recorder $recorder){
        $this->task = $this->recorder->getSettings()->getPlugin()->getScheduler()->scheduleRepeatingTask(new ClosureTask(function (): void{
            foreach ($this->recorder->getWorld()->getEntities() as $entity) {
                if ($entity instanceof Player || !$entity->isAlive() || $entity->isFlaggedForDespawn()) continue;
                $id = $entity->getId();
                if ($entity instanceof Human && isset($this->skins[$id]) && $this->skins[$id] !== spl_object_id($skin = $entity->getSkin())) {
                    $this->skins[$id] = $skin;
                    $this->recorder->addAction(EntityChangeSkinAction::create($id, $skin));
                }
                $location = $entity->getLocation();
                if (isset($this->locations[$id]) && !$location->equals($this->locations[$id])) $this->recorder->addAction(EntityMoveAction::create($id, $location, false));
                $this->locations[$id] = $location;
            }
        }), 1);
    }

    public function __destruct(){
        $this->task->cancel();
    }

    /** @priority MONITOR */
    public function onGamemodeChange(PlayerGameModeChangeEvent $event): void{
        $player = $event->getPlayer();
        if ($player->getWorld() !== $this->recorder->getWorld()) return;
        if ($event->getNewGamemode() === GameMode::SPECTATOR) {
            $this->recorder->removeEntity($player);
        } elseif ($player->isSpectator()) {
            $this->recorder->addEntity($player);
        }
    }

    /** @priority MONITOR */
    public function onChat(PlayerChatEvent $event): void{
        if (!$this->recorder->canRecordEntity($player = $event->getPlayer())) return;
        $text = $event->getFormatter()->format($player->getDisplayName(), $event->getMessage());
        if ($text instanceof Translatable) $text = $player->getLanguage()->translate($text);
        $this->recorder->addAction(PlayerChatAction::create($player->getId(), $text));
    }

    /** @priority MONITOR */
    public function onMove(PlayerMoveEvent $event): void{
        if (!$this->recorder->canRecordEntity($player = $event->getPlayer())) return;
        $this->recorder->addAction(EntityMoveAction::create($player->getId(), $event->getTo(), false));
    }

    /** @priority MONITOR */
    public function onSkinChange(PlayerChangeSkinEvent $event): void{
        if (!$this->recorder->canRecordEntity($player = $event->getPlayer())) return;
        $this->recorder->addAction(EntityChangeSkinAction::create($player->getId(), $event->getNewSkin()));
    }

    /** @priority MONITOR */
    public function onItemHeld(PlayerItemHeldEvent $event): void{
        if (!$this->recorder->canRecordEntity($player = $event->getPlayer()) || !$player->isAlive()) return;
        $this->recorder->addAction(EntityEquipAction::create($player->getId(), $event->getItem()));
    }

    /** @priority MONITOR */
    public function onPlayerRespawn(PlayerRespawnEvent $event): void{
        if (!$this->recorder->canRecordEntity($player = $event->getPlayer())) return;
        $this->recorder->addEntity($player);
    }

    /** @priority MONITOR */
    public function onItemSpawn(ItemSpawnEvent $event): void{
        if (!$this->recorder->canRecordEntity($entity = $event->getEntity())) return;
        $this->recorder->addEntity($entity);
    }

    /** @priority MONITOR */
    public function onEntitySpawn(EntitySpawnEvent $event): void{
        $entity = $event->getEntity();
        if ($entity instanceof ItemEntity || !$this->recorder->canRecordEntity($entity)) return;
        if ($entity instanceof Human) $this->skins[$entity->getId()] = spl_object_id($entity->getSkin());
        $this->recorder->addEntity($entity);
    }

    /** @priority MONITOR */
    public function onEntityTeleport(EntityTeleportEvent $event): void{
        $from = $event->getFrom();
        $to = $event->getTo();
        $entity = $event->getEntity();
        if ($entity->getWorld() !== $this->recorder->getWorld() && $to->getWorld() === $this->recorder->getWorld()) {
            $this->recorder->addEntity($entity);
        } elseif ($this->recorder->canRecordEntity($entity) || !$entity->isAlive()) {
            if ($from->getWorld() !== $event->getTo()->getWorld()) {
                $this->recorder->removeEntity($entity);
                return;
            }
            $location = $entity->getLocation();
            $this->recorder->addAction(EntityMoveAction::create($entity->getId(), Location::fromObject(
                $event->getTo(),
                $location->getWorld(),
                $location->getYaw(),
                $location->getPitch()
            ), true));
        }
    }

    /** @priority MONITOR */
    public function onEntityDamage(EntityDamageEvent $event): void{
        if (!$this->recorder->canRecordEntity($entity = $event->getEntity()) || !$entity->isAlive()) return;
        $this->recorder->addAction(ActorEventAction::create($entity->getId(), ActorEvent::HURT_ANIMATION, 0)); //why tf does pm not send this as ActorEventPacket???
    }

    /** @priority MONITOR */
    public function onEntityDeath(EntityDeathEvent $event): void{
        if (!$this->recorder->canRecordEntity($entity = $event->getEntity())) return;
        $this->recorder->addAction(ActorDeathAction::create($entity->getId()));
        if ($entity instanceof Player) {
            if (!($killer = $entity->getLastDamageCause()?->getEntity()) instanceof Player || $killer === $entity) $killer = null;
            $this->recorder->addEventLog(PlayerDeathEventLog::create($entity, $killer));
        }
    }

    /** @priority MONITOR */
    public function onDespawn(EntityDespawnEvent $event): void{
        if (!$this->recorder->canRecordEntity($entity = $event->getEntity())) return;
        $this->recorder->removeEntity($entity);
        if (!$entity instanceof Player) {
            unset($this->locations[$id = $entity->getId()]);
            unset($this->skins[$id]);
        }
    }

    /** @priority MONITOR */
    public function onPacketReceive(DataPacketReceiveEvent $event): void{
        $player = $event->getOrigin()->getPlayer();
        if ($player === null || $player->getWorld() !== $this->recorder->getWorld()) return; //Or should we use canRecordEntity here..?

        $packet = $event->getPacket();
        if ($packet instanceof AnimatePacket && ($entity = $player->getWorld()->getEntity($packet->actorRuntimeId))?->isAlive() && $this->recorder->canRecordEntity($entity)) {
            $this->recorder->addAction(PlayerAnimationAction::create($packet->actorRuntimeId, $packet->action));
        } elseif ($packet instanceof ActorEventPacket && ($entity = $player->getWorld()->getEntity($packet->actorRuntimeId))?->isAlive() && $this->recorder->canRecordEntity($entity)) {
            $this->recorder->addAction(ActorEventAction::create($packet->actorRuntimeId, $packet->eventId, $packet->eventData));
        } elseif ($packet instanceof EmotePacket && ($entity = $player->getWorld()->getEntity($id = $packet->getActorRuntimeId()))?->isAlive() && $this->recorder->canRecordEntity($entity)) {
            $this->recorder->addAction(EntityPlayEmoteAction::create($id, $packet->getEmoteId()));
        }
    }

    /** @priority MONITOR */
    public function onPacketSend(DataPacketSendEvent $event): void{
        foreach ($event->getTargets() as $target) {
            $player = $target->getPlayer();
            if ($player === null || $player->getWorld() !== $this->recorder->getWorld()) continue; //Or should we use canRecordEntity here..?
            foreach ($event->getPackets() as $packet) {
                if ($packet instanceof LevelEventPacket) {
                    $this->recorder->addAction(LevelEventAction::create($packet->eventId, $packet->eventData, $packet->position));
                } elseif ($packet instanceof BlockEventPacket) {
                    $this->recorder->addAction(BlockEventAction::create($packet->eventType, $packet->eventData, $packet->blockPosition));
                } elseif (
                    $packet instanceof SetActorDataPacket &&
                    ($entity = $player->getWorld()->getEntity($id = $packet->actorRuntimeId)) !== null &&
                    $entity->isAlive() &&
                    !$entity->isFlaggedForDespawn() &&
                    $this->recorder->canRecordEntity($entity)
                ) {
                    $this->recorder->addAction(ActorSetMetadataAction::create($id, $packet->metadata));
                }/* elseif ($packet instanceof GameRulesChangedPacket) { //What do we even need gamerules for?
                    GameruleHistory::store($packet->gameRules);
                    $this->recorder->addWorldData(GameruleChangeData::create());
                }*/
            }
            return;
        }
    }

    /** @priority MONITOR */
    public function onSignChange(SignChangeEvent $event): void{
        $this->recorder->addAction(SignChangeAction::create($event->getSign()));
    }

    /** @priority MONITOR */
    public function onChunkLoad(ChunkLoadEvent $event): void{
        if ($event->getWorld() !== $this->recorder->getWorld()) return;
        $this->recorder->addChunk($event->getWorld(), $event->getChunk(), World::chunkHash($event->getChunkX(), $event->getChunkZ()));
    }
}