<?php
namespace Jibix\Replay\provider;
use Closure;
use Jibix\Replay\replay\data\ReplayInformation;
use pocketmine\player\Player;


/**
 * Interface Provider
 * @author Jibix
 * @date 25.12.2024 - 13:26
 * @project Replay
 */
interface Provider{

    public function initializeReplays(Player $player, Closure $onComplete): void;
    public function searchReplay(string $identifier, Closure $onComplete): void;
    public function getReplay(string $identifier): ?ReplayInformation;
    /** @return ReplayInformation[] */
    public function getReplays(): array;
    public function getReplayData(ReplayInformation $information, Closure $onComplete): void;
    public function saveReplayData(ReplayInformation $information, array $xuids, string $buffer, ?Closure $onComplete = null): void;
    public function deleteReplay(string $identifier, ?Closure $onComplete = null): void;
}