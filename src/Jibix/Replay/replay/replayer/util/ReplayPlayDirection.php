<?php
namespace Jibix\Replay\replay\replayer\util;
use pocketmine\utils\EnumTrait;


/**
 * Class ReplayPlayDirection
 * @author Jibix
 * @date 26.12.2024 - 00:38
 * @project Replay
 *
 * @method static ReplayPlayDirection FORWARDS()
 * @method static ReplayPlayDirection BACKWARDS()
 */
final class ReplayPlayDirection{
    use EnumTrait;

    protected static function setup(): void{
        self::registerAll(
            new self("forwards"),
            new self("backwards"),
        );
    }

    public static function opposite(self $direction): self{
        return $direction->equals(ReplayPlayDirection::FORWARDS()) ? ReplayPlayDirection::BACKWARDS() : ReplayPlayDirection::FORWARDS();
    }

    public static function fromString(string $direction): ?self{
        self::checkInit();
        return self::$members[mb_strtoupper($direction)] ?? null;
    }
}