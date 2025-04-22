<?php
namespace Jibix\Replay\entity;


/**
 * Interface ReplayEntity
 * @author Jibix
 * @date 26.12.2024 - 00:58
 * @project Replay
 */
interface ReplayEntity{

    public function getActualId(): int;

    public function getCustomNetworkTypeId(): string;
}