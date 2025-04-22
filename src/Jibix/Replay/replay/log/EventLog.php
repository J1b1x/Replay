<?php
namespace Jibix\Replay\replay\log;
use Jibix\Replay\replay\replayer\Replay;
use Jibix\Replay\util\BinaryStream;


/**
 * Class EventLog
 * @author Jibix
 * @date 25.12.2024 - 23:26
 * @project Replay
 */
abstract class EventLog{

    protected const ID = EventLogIds::NONE;

    public static function id(): int{
        return static::ID;
    }

    public static function getTickOffset(): int{
        return 0;
    }

    abstract public static function getName(): string; //TODO: Make this translatable?

    abstract public function getDisplayData(): string; //TODO: Make this translatable?
    abstract public function serialize(BinaryStream $stream): void;
    abstract public function deserialize(BinaryStream $stream): void;
    abstract public function handle(Replay $replay): void;
}