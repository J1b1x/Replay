<?php
/**
 * Class ReplaySettings
 * @author Jibix
 * @date 20.04.2025 - 17:13
 * @project Replay
 */
namespace Jibix\Replay\replay\replayer;

use Jibix\Replay\provider\Provider;
use pocketmine\plugin\PluginBase;


class ReplaySettings{

    public function __construct(
        protected PluginBase $plugin,
        protected Provider $provider,
        protected array $unreversable_level_event_ids
    ){}

    public function getPlugin(): PluginBase{
        return $this->plugin;
    }

    public function getProvider(): Provider{
        return $this->provider;
    }

    public function getUnreversableLevelEventIds(): array{
        return $this->unreversable_level_event_ids;
    }
}