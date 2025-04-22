<?php
namespace Jibix\Replay\util;


/**
 * Class GameDetails
 * @author Jibix
 * @date 25.12.2024 - 14:24
 * @project Replay
 */
final class GameDetails{

    private function __construct(
        private string $name,
        private array $xuids
    ){}

    public static function create(string $name, array $xuids): self{
        return new self($name, array_values($xuids));
    }

    public function getName(): string{
        return $this->name;
    }

    public function setName(string $name): void{
        $this->name = $name;
    }

    public function getXuids(): array{
        return $this->xuids;
    }

    //Note: This only works while recording, you CAN add another xuid, even though i don't recommend it...
    public function addXuid(string $xuid): void{
        if (in_array($xuid, $this->xuids)) return;
        $this->xuids[] = $xuid;
    }

    public function removeXuid(string $xuid): void{
        unset($this->xuids[array_search($xuid, $this->xuids)]);
    }

    public static function fromData(array $data): self{
        Utils::validateProperties(self::class, $data);
        return self::create($data['name'], $data['xuids']);
    }
}