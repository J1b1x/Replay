# Replay

![php](https://img.shields.io/badge/php-8.1-informational)
![api](https://img.shields.io/badge/pocketmine-5.0-informational)


A PocketMine-MP library to record and replay games.
You can find an example of how to use this library in a plugin [here](https://github.com/J1b1x/ReplayExample).
The library supports **reversed watching**, **world changes**, **multiple world recording** and has an included **event log** for stuff like deaths or significant game events.


## NOTE
Using a replay system like this can cause performance issues, especially if you record multiple worlds at once, which i would NOT recommend.

This is a Network based Replay API, means it's not supposed to be used as a recorder and replayer at once on the same server.

However, if you have any issues, ideas or feature requests, just [create an issue]().
## API

### Recorder

#### Recording
To handle the replay recording use the functions in the [RecordHandler](https://github.com/J1b1x/Replay/blob/master/src/Jibix/Replay/replay/recorder/RecordHandler.php) class.
```php
public function record(RecordSettings $settings, World $world, GameDetails $details): Recorder;

public function stopRecording(World $world, ?Closure $onComplete = null): void;

public function isRecording(World $world): bool;

public function getRecorder(World $world): ?Recorder;

public function getRecordings(): array;

public function changeRecordingWorld(Recorder $recorder, World $world): void;
```

#### Actions
To add your own action, use the [ActionHandler](https://github.com/J1b1x/Replay/blob/master/src/Jibix/Replay/replay/action/ActionHandler.php).
```php
ActionHandler::getInstance()->registerAction(
    new EntitySigmaAction()
);

class EntitySigmaAction extends EntityAction{
    protected const ID = CustomActionIds::ENTITY_SIGMA; //like 21 or something

    private string $sigma;

    public static function create(int $entityId, string $sigma): self{
        $action = new self();
        $action->entityId = $entityId;
        $action->sigma = $sigma;
        return $action;
    }

    public function serialize(BinaryStream $stream): void{
        parent::serialize($stream);
        $stream->putString($this->sigma);
    }

    public function deserialize(BinaryStream $stream): void{
        parent::deserialize($stream);
        $this->sigma = $stream->getString();
    }

    public function handle(Replay $replay): void{
        if (!$entity = $replay->getEntity($this->entityId)) return; //don't ask me how tf this is even possible
        $entity->setDisplayName($this->sigma);
    }
    
    public function handleReversed(Replay $replay): ?Action{
        return self::create($this->entityId, $replay->getEntity($this->entityId)?->getDisplayName());
    }
}

$recorder->addAction(EntitySigmaAction::create($entity->getId(), "Absolute SIGMA!!!!"));
```

#### Event Log
To add your own events to a replay, for example a "bed break" event for bed wars, use the [EventLogHandler](https://github.com/J1b1x/Replay/blob/master/src/Jibix/Replay/replay/log/EventLogHandler.php).
```php
EventLogHandler::getInstance()->registerEventLog(
    new BedBreakEvent()
);

class BedBreakEvent extends EventLog{
    protected const ID = CustomEventLogIds::BED_BREAK_EVENT; //like 3 for example

    private string $playerName;
    private string $team; //could be "red" or "blue" or whatever
    private int $playerId;

    public static function create(Player $player, string $team): self{
        $data = new self();
        $data->playerName = $player->getDisplayName();
        $data->playerName = $team;
        $data->playerId = $player->getId();
        return $data;
    }

    public static function getName(): string{
        return "beds";
    }

    public static function getTickOffset(): int{ //This is the time offset you get teleported to before the event happened (like 4 * 20 is 4 seconds before they broke the bed)
        return 4 * 20;
    }

    public function getDisplayData(): string{
        return "§c" . $this->playerName . "§8 broke a bed of team§6 " . $this->team);
    }

    public function serialize(BinaryStream $stream): void{
        $stream->putString($this->playerName);
        $stream->putString($this->team);
        $stream->putInt($this->playerId);
    }

    public function deserialize(BinaryStream $stream): void{
        $this->playerName = $stream->getString();
        $this->team = $stream->getString();
        $this->playerId = $stream->getInt();
    }

    public function handle(Replay $replay): void{
        $replay->getWatcher()->teleport($replay->getEntity($this->playerId)->getPosition());
    }
}

$recorder->addEventLog(new BedBreakEvent($player, $team));
```


### Replayer

#### Replaying
To handle the replay watching use the [Replay events]() and the functions in the [Replay](https://github.com/J1b1x/Replay/blob/master/src/Jibix/Replay/replay/replayer/Replay.php) class.
```php
    public static function play(ReplaySettings $settings, Player $player, ReplayInformation $information): void;

    public function end(): void;

    public function skip(ReplayPlayDirection $direction, int $ticks): void;

    public function skipToTick(int $tick): void;

    public function getPlayDirection(): ReplayPlayDirection;

    public function setPlayDirection(ReplayPlayDirection $playDirection): void;

    public function getSpeed(): float;

    public function setSpeed(float $speed): void;

    public function isPaused(): bool;

    public function togglePaused(): bool;

    public function getEventLogs(): array;

    public function getWatcher(): Player;

    public function getWorld(): ?World;

    public function getEntity(int $entityId): ?ReplayEntity;

    public function getEntities(): array;

    public function getSettings(): ReplaySettings;
```

#### Replay Events
- ReplayStartEvent
- ReplayEndEvent
- ReplayRestartEvent
- ReplayTogglePauseEvent
- ReplayChangeDirectionEvent
