<?php
namespace Jibix\Replay\replay\data;
use Jibix\Replay\replay\action\Action;
use Jibix\Replay\replay\action\ActionHandler;
use Jibix\Replay\replay\log\EventLog;
use Jibix\Replay\replay\log\EventLogHandler;
use Jibix\Replay\util\BinaryStream;


/**
 * Class ReplayData
 * @author Jibix
 * @date 25.12.2024 - 22:17
 * @project Replay
 */
class ReplayData{

    private function __construct(){}

    private ReplayInformation $information;
    /** @var Action[][] */
    private array $actions = [];
    /** @var EventLog[][] */
    private array $eventLogs = [];
    /** @var WorldData[] */
    private array $worldDatum;

    public static function create(ReplayInformation $information, array $actions, array $eventLogs, array $worldDatum): self{
        $data = new self();
        $data->information = $information;
        $data->actions = $actions;
        $data->eventLogs = $eventLogs;
        $data->worldDatum = $worldDatum;
        return $data;
    }

    public function getInformation(): ReplayInformation{
        return $this->information;
    }

    public function getActions(): array{
        return $this->actions;
    }

    public function getActionsByTick(int $tick): array{
        return $this->actions[$tick] ?? [];
    }

    public function getEventLogs(): array{
        return $this->eventLogs;
    }

    public function getWorldDatum(): array{
        return $this->worldDatum;
    }

    public function getWorldData(string $worldName): ?WorldData{
        return $this->worldDatum[$worldName] ?? null;
    }

    //TODO: Make the de-/encoding async... not really sure how tho, as most stuff (such as item or block objects) ain't thread safe so we can't really deserialize it in other threads
    public function encode(): string{
        $stream = BinaryStream::encoder();
        $stream->putInt(count($this->actions));
        foreach ($this->actions as $tick => $actions) {
            $stream->putInt($tick);
            $stream->putInt(count($actions));
            foreach ($actions as $action) {
                $stream->putInt($action::id());
                $action->serialize($stream);
            }
        }
        $stream->putInt(count($this->eventLogs));
        foreach ($this->eventLogs as $tick => $eventLogs) {
            $stream->putInt($tick);
            $stream->putInt(count($eventLogs));
            foreach ($eventLogs as $eventLog) {
                $stream->putInt($eventLog::id());
                $eventLog->serialize($stream);
            }
        }
        $stream->putInt(count($this->worldDatum));
        foreach ($this->worldDatum as $worldName => $data) {
            $stream->putString($worldName);
            $data->encode($stream);
        }
        return $stream->getBuffer();
    }

    public static function decode(ReplayInformation $information, string $buffer): self{
        $stream = BinaryStream::decoder($buffer, 0);
        $actionHandler = ActionHandler::getInstance();
        $actions = [];
        $tickCount = $stream->getInt();
        for ($i = 0; $i < $tickCount; $i++) {
            $tick = $stream->getInt();
            $actionCount = $stream->getInt();
            for ($j = 0; $j < $actionCount; $j++) {
                $action = $actionHandler->getAction($stream->getInt());
                $action->deserialize($stream);
                $actions[$tick][] = $action;
            }
        }
        $logHandler = EventLogHandler::getInstance();
        $eventLogs = [];
        $tickCount = $stream->getInt();
        for ($i = 0; $i < $tickCount; $i++) {
            $tick = $stream->getInt();
            $eventLogCount = $stream->getInt();
            for ($j = 0; $j < $eventLogCount; $j++) {
                $event = $logHandler->getEventLog($stream->getInt());
                $event->deserialize($stream);
                $eventLogs[$tick][] = $event;
            }
        }

        $worldDatum = [];
        $worldDataCount = $stream->getInt();
        for ($i = 0; $i < $worldDataCount; $i++) {
            $worldDatum[$stream->getString()] = WorldData::decode($stream);
        }
        return self::create($information, $actions, $eventLogs, $worldDatum);
    }
}