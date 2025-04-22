<?php
namespace Jibix\Replay\entity\type;
use Jibix\Replay\entity\CustomNetworkIdTrait;
use Jibix\Replay\entity\ReplayEntity;
use Jibix\Replay\entity\ReplayEntityTrait;
use pocketmine\entity\Living;
use pocketmine\player\Player;


/**
 * Class ReplayLiving
 * @author Jibix
 * @date 26.12.2024 - 01:01
 * @project Replay
 */
class ReplayLiving extends Living implements ReplayEntity{
    use ReplayEntityTrait;
    use CustomNetworkIdTrait;

    public function getName(): string{
        return "Balls"; //should not be visible anyway
    }

    protected function sendSpawnPacket(Player $player): void{
        $this->sendInternalSpawnPacket($player);
        $networkSession = $player->getNetworkSession();
        $networkSession->getEntityEventBroadcaster()->onMobArmorChange([$networkSession], $this);
    }
}