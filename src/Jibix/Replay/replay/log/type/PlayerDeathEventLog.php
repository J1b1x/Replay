<?php
namespace Jibix\Replay\replay\log\type;
use Jibix\Replay\replay\log\EventLog;
use Jibix\Replay\replay\log\EventLogIds;
use Jibix\Replay\replay\replayer\Replay;
use Jibix\Replay\util\BinaryStream;
use pocketmine\player\Player;


/**
 * Class PlayerDeathEventLog
 * @author Jibix
 * @date 25.12.2024 - 23:30
 * @project Replay
 */
class PlayerDeathEventLog extends EventLog{

    protected const ID = EventLogIds::DEATH_EVENT;

    private string $playerName;
    private int $playerId;
    private ?string $killerName;

    public static function create(Player $player, ?Player $killer): self{
        $data = new self();
        $data->playerName = $player->getDisplayName();
        $data->playerId = $player->getId();
        $data->killerName = $killer?->getDisplayName();
        return $data;
    }

    public static function getName(): string{
        return "deaths";
    }

    public static function getTickOffset(): int{
        return 4 * 20;
    }

    public function getDisplayData(): string{
        return "§c" . $this->playerName . ($this->killerName === null ? "§8 died" : "§8 got killed by §b" . $this->killerName);
    }

    public function serialize(BinaryStream $stream): void{
        $stream->putString($this->playerName);
        $stream->putInt($this->playerId);
        $stream->putString($this->killerName ?? "");
    }

    public function deserialize(BinaryStream $stream): void{
        $this->playerName = $stream->getString();
        $this->playerId = $stream->getInt();
        $this->killerName = empty($name = $stream->getString()) ? null : $name;
    }

    public function handle(Replay $replay): void{
        $replay->getWatcher()->teleport($replay->getEntity($this->playerId)->getPosition());
    }
}