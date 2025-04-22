<?php
namespace Jibix\Replay\entity\type;
use Jibix\Replay\entity\CustomNetworkIdTrait;
use Jibix\Replay\entity\ReplayEntity;
use Jibix\Replay\entity\ReplayEntityTrait;
use pocketmine\entity\Entity;



/**
 * Class DefaultReplayEntity
 * @author Jibix
 * @date 26.12.2024 - 00:59
 * @project Replay
 */
class DefaultReplayEntity extends Entity implements ReplayEntity{
    use ReplayEntityTrait;
    use CustomNetworkIdTrait;
}