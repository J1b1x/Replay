<?php
namespace Jibix\Replay\entity;
use pocketmine\math\Vector3;


/**
 * Trait ReplayEntityTrait
 * @author Jibix
 * @date 26.12.2024 - 00:58
 * @project Replay
 */
trait ReplayEntityTrait{

    public function getActualId(): int{
        return $this->actualId;
    }

    public function canBeMovedByCurrents(): bool{
        return false;
    }

    public function hasMovementUpdate(): bool{
        return false;
    }

    public function canBeCollidedWith(): bool{
        return false;
    }

    public function getDrops(): array{
        return [];
    }

    public function setPositionAndRotation(Vector3 $pos, float $yaw, float $pitch): bool{
        return parent::setPositionAndRotation($pos, $yaw, $pitch);
    }
}