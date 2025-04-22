<?php
/**
 * Class RecordSettings
 * @author Jibix
 * @date 20.04.2025 - 17:13
 * @project Replay
 */
namespace Jibix\Replay\replay\recorder;

use Jibix\Replay\provider\Provider;
use pocketmine\plugin\PluginBase;


class RecordSettings{

    public function __construct(
        protected PluginBase $plugin,
        protected Provider $provider,
        protected int $identifier_length,
    ){}

    public function getPlugin(): PluginBase{
        return $this->plugin;
    }

    public function getProvider(): Provider{
        return $this->provider;
    }

    public function getIdentifierLength(): int{
        return $this->identifier_length;
    }
}