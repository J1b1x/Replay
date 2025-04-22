<?php
namespace Jibix\Replay\replay\action\type\player;
use Jibix\Replay\replay\action\ActionIds;
use Jibix\Replay\replay\action\type\EntityAction;
use Jibix\Replay\replay\replayer\Replay;
use Jibix\Replay\util\BinaryStream;


/**
 * Class PlayerChatAction
 * @author Jibix
 * @date 25.12.2024 - 22:44
 * @project Replay
 */
class PlayerChatAction extends EntityAction{

    protected const ID = ActionIds::PLAYER_CHAT;
    private const MESSAGE_PREFIX = "§8[§dReplay§8]§r ";

    //Not sure if recording the chat is a good idea yet (also most servers have own logs anyway)...

    private string $message;

    public static function create(int $entityId, string $message): self{
        $action = new self();
        $action->entityId = $entityId;
        $action->message = $message;
        return $action;
    }

    public function serialize(BinaryStream $stream): void{
        parent::serialize($stream);
        $stream->putString($this->message);
    }

    public function deserialize(BinaryStream $stream): void{
        parent::deserialize($stream);
        $this->message = $stream->getString();
    }

    public function handle(Replay $replay): void{
        $replay->getWatcher()->sendMessage(self::MESSAGE_PREFIX . $this->message);
    }

    //Should we even reverse this?
}