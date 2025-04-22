<?php
namespace Jibix\Replay\replay\data;
use DateTime;
use Exception;
use Jibix\Replay\util\GameDetails;
use Jibix\Replay\util\Utils;


/**
 * Class ReplayInformation
 * @author Jibix
 * @date 25.12.2024 - 22:08
 * @project Replay
 */
class ReplayInformation{

    public function __construct(
        private string $identifier,
        private DateTime $timestamp,
        private int $duration,
        private string $gameDetails,
    ){}

    public static function generateIdentifier(int $identifier_length = 6): string{
        return substr(sha1(bin2hex(random_bytes(32))), 0, $identifier_length);
    }

    public static function create(int $identifier_length, DateTime $timestamp, int $duration, GameDetails $details): self{
        return new self(
            self::generateIdentifier($identifier_length),
            $timestamp,
            $duration,
            $details->getName()
        );
    }

    public function getIdentifier(): string{
        return $this->identifier;
    }

    public function getTimestamp(): DateTime{
        return $this->timestamp;
    }

    public function getDuration(): int{
        return $this->duration;
    }

    public function getGameDetails(): string{
        return $this->gameDetails;
    }

    public function serialize(): array{
        return [
            "identifier" => $this->identifier,
            "timestamp" => $this->timestamp->format("Y-m-d H:i:s"),
            "duration" => $this->duration,
            "gameDetails" => $this->gameDetails
        ];
    }

    public static function deserialize(array $data): ?static{
        try {
            Utils::validateProperties(static::class, $data);
        } catch (Exception) {
            return null;
        }
        return new static(
            $data['identifier'],
            new DateTime($data['timestamp']),
            (int)$data['duration'],
            $data['gameDetails']
        );
    }
}