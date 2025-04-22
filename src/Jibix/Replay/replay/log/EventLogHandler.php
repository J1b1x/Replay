<?php
namespace Jibix\Replay\replay\log;
use Jibix\Replay\replay\log\type\PlayerDeathEventLog;
use pocketmine\utils\SingletonTrait;


/**
 * Class EventLogHandler
 * @author Jibix
 * @date 25.12.2024 - 23:26
 * @project Replay
 */
final class EventLogHandler{
    use SingletonTrait{
        setInstance as private;
        reset as private;
    }

    /** @var EventLog[] */
    private array $eventLogs = [];

    private function __construct(){
        $this->registerEventLog(
            new PlayerDeathEventLog()
        );
    }

    public function registerEventLog(EventLog ...$eventLogs): void{
        foreach ($eventLogs as $event) {
            $this->eventLogs[$event::id()] = clone $event;
        }
    }

    public function getEventLogs(): array{
        return $this->eventLogs;
    }

    public function getEventLog(int $id): ?EventLog{
        return isset($this->eventLogs[$id]) ? clone $this->eventLogs[$id] : null;
    }
}