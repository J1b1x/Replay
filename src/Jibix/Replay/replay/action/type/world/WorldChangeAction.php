<?php
namespace Jibix\Replay\replay\action\type\world;
use Jibix\Replay\replay\action\Action;
use Jibix\Replay\replay\action\ActionIds;
use Jibix\Replay\replay\action\type\WorldAction;
use Jibix\Replay\replay\replayer\Replay;
use Jibix\Replay\util\BinaryStream;


/**
 * Class WorldChangeAction
 * @author Jibix
 * @date 25.12.2024 - 23:14
 * @project Replay
 */
class WorldChangeAction extends WorldAction{

    protected const ID = ActionIds::WORLD_CHANGE;

    private string $worldName;

    public static function create(string $worldName): self{
        $action = new self();
        $action->worldName = $worldName;
        return $action;
    }

    public function serialize(BinaryStream $stream): void{
        $stream->putString($this->worldName);
    }

    public function deserialize(BinaryStream $stream): void{
        $this->worldName = $stream->getString();
    }

    public function handle(Replay $replay): void{
        $replay->switchWorld($this->worldName);
    }

    public function handleReversed(Replay $replay): ?Action{
        return self::create($replay->getWorld()->getDisplayName());
    }
}