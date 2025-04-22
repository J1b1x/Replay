<?php
namespace Jibix\Replay\replay\action;
use Jibix\Replay\replay\replayer\Replay;
use Jibix\Replay\util\BinaryStream;


/**
 * Class Action
 * @author Jibix
 * @date 25.12.2024 - 22:36
 * @project Replay
 */
abstract class Action{

    protected const ID = ActionIds::NONE;

    public static function id(): int{
        return static::ID;
    }

    abstract public function serialize(BinaryStream $stream): void;
    abstract public function deserialize(BinaryStream $stream): void;

    abstract public function handle(Replay $replay): void;

    public function handleReversed(Replay $replay): ?Action{
        return $this;
    }

    //reverse
    //run the reversed function before we run handle and cache the result
    //self::create(world->getBlock($this->pos));
}