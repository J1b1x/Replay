<?php
namespace Jibix\Replay\replay\recorder;
use Closure;
use DateTime;
use Jibix\Replay\listener\record\RecordInventoryListener;
use Jibix\Replay\listener\record\RecordListener;
use Jibix\Replay\replay\action\Action;
use Jibix\Replay\replay\action\type\actor\ActorDespawnAction;
use Jibix\Replay\replay\action\type\entity\EntitySpawnAction;
use Jibix\Replay\replay\action\type\entity\EntitySpawnItemAction;
use Jibix\Replay\replay\action\type\world\WorldChangeAction;
use Jibix\Replay\replay\action\type\world\WorldChangeTimeAction;
use Jibix\Replay\replay\data\ReplayData;
use Jibix\Replay\replay\data\ReplayInformation;
use Jibix\Replay\replay\data\WorldData;
use Jibix\Replay\replay\log\EventLog;
use Jibix\Replay\task\AsyncCompressTask;
use Jibix\Replay\util\GameDetails;
use LogicException;
use pocketmine\entity\Entity;
use pocketmine\entity\Living;
use pocketmine\entity\object\ItemEntity;
use pocketmine\event\HandlerListManager;
use pocketmine\player\Player;
use pocketmine\scheduler\ClosureTask;
use pocketmine\scheduler\TaskHandler;
use pocketmine\Server;
use pocketmine\world\format\Chunk;
use pocketmine\world\World;
use function Jibix\AsyncMedoo\util\async;


/**
 * Class Recorder
 * @author Jibix
 * @date 25.12.2024 - 14:12
 * @project Replay
 */
class Recorder{

    private RecordSettings $settings;
    private RecordListener $listener;
    private DateTime $timestamp;
    private World $world;
    private GameDetails $details;
    private TaskHandler $tickTask;
    /** @var WorldData[] */
    private array $worldDatum = [];
    /** @var Action[][] */
    private array $actions = [];
    /** @var EventLog[][] */
    private array $eventLogs = [];
    private int $tick = 0;
    private int $currentTime;

    public function __construct(RecordSettings $settings, World $world, GameDetails $details){
        $this->settings = $settings;
        $this->timestamp = new DateTime();
        $this->details = $details;
        $this->changeWorld($world);

        $this->tickTask = $settings->getPlugin()->getScheduler()->scheduleRepeatingTask(new ClosureTask(function (): void{
            $this->tick();
        }), 1);
        Server::getInstance()->getPluginManager()->registerEvents($this->listener = new RecordListener($this), $settings->getPlugin());
    }

    /**
     * @internal
     * Use RecordHandler::stopRecording(world) instead
     */
    public function end(?Closure $onComplete = null): void{
        $this->tickTask->cancel();
        foreach ($this->getWorld()->getEntities() as $entity) {
            if ($entity instanceof Living) RecordInventoryListener::removeListeners($entity);
        }
        HandlerListManager::global()->unregisterAll($this->listener);
        async(new AsyncCompressTask(
            $information = ReplayInformation::create($this->settings->getIdentifierLength(), $this->timestamp, $this->tick, $this->details),
            ReplayData::create($information, $this->actions, $this->eventLogs, $this->worldDatum)->encode(),
            fn (ReplayInformation $information, string $result) => $this->settings->getProvider()->saveReplayData(
                $information,
                $this->details->getXuids(),
                $result,
                $onComplete
            )
        ));
    }


    /**
     * @internal
     * Use RecordHandler::changeRecordingWorld(world) instead
     */
    public function changeWorld(World $world): void{
        if (isset($this->world)) {
            if ($this->world->getId() === $world->getId()) throw new LogicException("Cannot change to the same world");
            foreach ($this->getWorld()->getEntities() as $entity) {
                $this->removeEntity($entity);
                if ($entity instanceof Living) RecordInventoryListener::removeListeners($entity);
            }
            $this->addAction(WorldChangeAction::create($world->getFolderName()));
        }

        $this->world = $world;
        $this->currentTime = $world->getTime();
        foreach ($world->getLoadedChunks() as $chunkHash => $chunk) {
            $this->addChunk($world, $chunk, $chunkHash);
        }
        foreach ($world->getEntities() as $entity) {
            if ($this->canRecordEntity($entity)) $this->addEntity($entity);
        }
    }

    public function getWorld(): World{
        return $this->world;
    }

    public function getSettings(): RecordSettings{
        return $this->settings;
    }

    public function getGameDetails(): GameDetails{
        return $this->details;
    }

    private function tick(): void{
        $this->tick++;
        if ($this->currentTime !== $time = $this->world->getTime()) {
            $this->addAction(WorldChangeTimeAction::create($time));
            $this->currentTime = $time;
        }
    }



    public function addEventLog(EventLog $eventLog): void{
        $this->eventLogs[max(0, $this->tick)][] = $eventLog;
    }

    public function addAction(Action $action): void{
        $this->actions[$this->tick][] = $action;
    }

    public function addChunk(World $world, Chunk $chunk, int $chunkHash): void{
        if (!isset($this->worldDatum[$name = $world->getFolderName()])) $this->worldDatum[$name] = WorldData::create($world);
        $this->worldDatum[$name]->addChunk($this, $world, $chunk, $chunkHash);
    }

    public function addEntity(Entity $entity): void{
        $this->addAction($entity instanceof ItemEntity ? EntitySpawnItemAction::create($entity) : EntitySpawnAction::create($entity));
        if ($entity instanceof Living) RecordInventoryListener::addListener($this, $entity);
    }

    public function removeEntity(Entity $entity): void{
        $this->addAction(ActorDespawnAction::create($entity->getId()));
        if ($entity instanceof Living) RecordInventoryListener::removeListeners($entity);
    }

    public function canRecordEntity(Entity $entity): bool{
        return $entity->getWorld() === $this->world && (!$entity instanceof Player || !$entity->isSpectator());
    }
}