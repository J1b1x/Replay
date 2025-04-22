<?php
namespace Jibix\Replay\replay\replayer;
use Jibix\Replay\entity\ReplayEntity;
use Jibix\Replay\replay\action\Action;
use Jibix\Replay\replay\data\ReplayData;
use Jibix\Replay\replay\data\ReplayInformation;
use Jibix\Replay\replay\log\EventLog;
use Jibix\Replay\replay\replayer\event\ReplayChangeDirectionEvent;
use Jibix\Replay\replay\replayer\event\ReplayEndEvent;
use Jibix\Replay\replay\replayer\event\ReplayRestartEvent;
use Jibix\Replay\replay\replayer\event\ReplayStartEvent;
use Jibix\Replay\replay\replayer\event\ReplayTogglePauseEvent;
use Jibix\Replay\replay\replayer\util\ReplayGenerator;
use Jibix\Replay\replay\replayer\util\ReplayPlayDirection;
use Jibix\Replay\task\AsyncDecompressTask;
use Jibix\Replay\util\Utils;
use pocketmine\block\tile\TileFactory;
use pocketmine\player\Player;
use pocketmine\scheduler\ClosureTask;
use pocketmine\scheduler\TaskHandler;
use pocketmine\Server;
use pocketmine\world\World;
use pocketmine\world\WorldCreationOptions;
use function Jibix\AsyncMedoo\util\async;


/**
 * Class Replay
 * @author Jibix
 * @date 26.12.2024 - 00:29
 * @project Replay
 */
class Replay{

    private TaskHandler $task;

    private bool $paused = true;
    private int $currentTick = 0;
    private float $speedTicks = 0; //used for slowed speed (so like speed that's slower than x1)
    private float $speed = 1.0;
    private ReplayPlayDirection $playDirection;
    private World $world;
    /** @var ReplayEntity[] */
    private array $entities = [];
    /** @var Action[][] */
    private array $reversed = [];

    public function __construct(
        private ReplaySettings $settings,
        private Player $player,
        private ReplayData $data
    ){
        $this->switchWorld(array_key_first($this->data->getWorldDatum()));
        $this->playDirection = ReplayPlayDirection::FORWARDS();
        $this->task = $this->settings->getPlugin()->getScheduler()->scheduleRepeatingTask(new ClosureTask(function (): void{
            //TODO: Find a better way than this, since it looks kinda weird when playing on slowed down speed (like x0.25) because it only plays the actions when a full tick is reached
            $this->speedTicks += $this->speed;
            if (fmod($this->speedTicks, 1) !== 0.0) return;
            for ($i = 0; $i < $this->speedTicks; $i++) {
                if (!$this->tick()) break;
            }
            $this->speedTicks = 0;
        }), 1);
        (new ReplayStartEvent($this))->call();
    }

    public function end(): void{
        $this->task->cancel();
        $this->removeCurrentWorld();
        (new ReplayEndEvent($this))->call();
    }

    private function tick(?ReplayPlayDirection $direction = null): bool{
        if ($this->paused && $direction === null) return false;
        $reversed = ($direction ?? $this->playDirection)->equals(ReplayPlayDirection::BACKWARDS());
        if ($reversed) {
            foreach (array_reverse($this->reversed[$this->currentTick] ?? []) as $action) {
                $action->handle($this);
            }
        } else {
            if (isset($this->reversed[$this->currentTick])) {
                foreach ($this->data->getActionsByTick($this->currentTick) as $action) {
                    $action->handle($this);
                }
            } else {
                foreach ($this->data->getActionsByTick($this->currentTick) as $action) {
                    if ($reversedAction = $action->handleReversed($this)) $this->reversed[$this->currentTick][] = $reversedAction;
                    $action->handle($this);
                }
            }
        }

        $this->currentTick += $reversed ? -1 : 1;
        if ($this->currentTick < 0) {
            $this->currentTick = 0;
            if ($this->playDirection->equals(ReplayPlayDirection::BACKWARDS())) {
                $this->paused = true;
                $this->playDirection = ReplayPlayDirection::FORWARDS();
                (new ReplayRestartEvent($this, true))->call();
            }
            return false;
        } elseif ($this->currentTick >= $duration = $this->data->getInformation()->getDuration()) {
            if ($this->playDirection->equals(ReplayPlayDirection::BACKWARDS())) {
                $this->currentTick = $duration;
            } else {
                //Restart from 0
                $this->skip(ReplayPlayDirection::BACKWARDS(), $this->currentTick +1);
                $this->paused = true;
                (new ReplayRestartEvent($this))->call();
            }
            return false;
        }
        if ($this->currentTick == 0) {
            $this->player->getXpManager()->setXpAndProgress(0, 0);
        } else {
            $this->player->getXpManager()->setXpAndProgress(
                (int)floor($this->currentTick / 20),
                $this->currentTick / $this->data->getInformation()->getDuration()
            );
        }
        return true;
    }

    public function skip(ReplayPlayDirection $direction, int $ticks): void{
        for ($i = 0; $i < $ticks; $i++) {
            if (!$this->tick($direction)) break;
        }
        $this->speedTicks = 0;
    }

    public function skipToTick(int $tick): void{
        if ($tick == $this->currentTick) return;
        $this->skip($tick > $this->currentTick ? ReplayPlayDirection::FORWARDS() : ReplayPlayDirection::BACKWARDS(), abs($tick - $this->currentTick));
    }

    public function getPlayDirection(): ReplayPlayDirection{
        return $this->playDirection;
    }

    public function setPlayDirection(ReplayPlayDirection $playDirection): void{
        if ($playDirection->equals($this->playDirection)) return;
        $this->playDirection = $playDirection;
        (new ReplayChangeDirectionEvent($this))->call();
    }

    public function getSpeed(): float{
        return $this->speed;
    }

    public function setSpeed(float $speed): void{
        $this->speedTicks = 0;
        $this->speed = $speed;
    }

    public function isPaused(): bool{
        return $this->paused;
    }

    public function togglePaused(): bool{
        $paused = $this->paused = !$this->paused;
        (new ReplayTogglePauseEvent($this))->call();
        return $paused;
    }

    /**
     * @return EventLog[][]
     */
    public function getEventLogs(): array{
        return $this->data->getEventLogs();
    }

    public function getWatcher(): Player{
        return $this->player;
    }

    public function getWorld(): ?World{
        return $this->world;
    }

    /** @internal */
    public function switchWorld(string $worldName): void{
        if (isset($this->world)) $this->removeCurrentWorld();
        $worldData = $this->data->getWorldData($worldName);
        Server::getInstance()->getWorldManager()->generateWorld($id = uniqid(), (new WorldCreationOptions())
            ->setGeneratorClass(ReplayGenerator::class)
            ->setSpawnPosition($worldData->getSpawn()), false);
        $this->world = Server::getInstance()->getWorldManager()->getWorldByName($id);
        $this->world->setDisplayName($worldName);
        $this->world->setTime($worldData->getInitialTime());
        $this->world->stopTime();
        $factory = TileFactory::getInstance();
        foreach ($worldData->getChunks() as $chunkHash => $chunk) {
            World::getXZ($chunkHash, $x, $z);
            foreach ($worldData->getTiles() as $tag) {
                $tile = $factory->createFromData($this->world, $tag);
                $chunk->removeTile($tile); //removing current tile with empty data
                $chunk->addTile($tile);
            }
            $this->world->setChunk($x, $z, $chunk);
        }
        $this->player->teleport($this->world->getSafeSpawn());
        $this->speedTicks = 0;
    }

    private function removeCurrentWorld(): void{
        Utils::removeWorld($this->world->getFolderName());
    }

    /** @internal */
    public function spawnEntity(ReplayEntity $entity): void{
        $this->entities[$entity->getActualId()] = $entity;
        $entity->spawnToAll();
    }

    /** @internal */
    public function despawnEntity(int $entityId): void{
        if (!isset($this->entities[$entityId])) return;
        if (!$this->entities[$entityId]->isFlaggedForDespawn()) $this->entities[$entityId]?->flagForDespawn();
        unset($this->entities[$entityId]);
    }

    public function getEntity(int $entityId): ?ReplayEntity{
        return $this->entities[$entityId] ?? null;
    }

    public function getEntities(): array{
        return $this->entities;
    }

    public function getSettings(): ReplaySettings{
        return $this->settings;
    }

    public static function play(ReplaySettings $settings, Player $player, ReplayInformation $information): void{
        $name = $player->getName();
        $settings->getProvider()->getReplayData($information, fn (string $data) => async(new AsyncDecompressTask(
            $data,
            function (string $buffer) use ($settings, $name, $information): void{
                if (!$player = Server::getInstance()->getPlayerExact($name)) return;
                new self($settings, $player, ReplayData::decode($information, $buffer));
            }
        )));
    }
}