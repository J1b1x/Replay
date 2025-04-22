<?php
namespace Jibix\Replay\replay\action\type;
use Jibix\Replay\replay\action\Action;
use Jibix\Replay\util\BinaryStream;


/**
 * Class EntityAction
 * @author Jibix
 * @date 25.12.2024 - 22:44
 * @project Replay
 */
abstract class EntityAction extends Action{

    protected int $entityId;

    public function getEntityId(): int{
        return $this->entityId;
    }

    public function serialize(BinaryStream $stream): void{
        $stream->putUnsignedVarInt($this->entityId);
    }

    public function deserialize(BinaryStream $stream): void{
        $this->entityId = $stream->getUnsignedVarInt();
    }
}