<?php
namespace Jibix\Replay\provider\type;
use Closure;
use Jibix\AsyncMedoo\AsyncMedoo;
use Jibix\Replay\provider\Provider;
use Jibix\Replay\replay\data\ReplayInformation;
use Medoo\Medoo;
use pocketmine\player\Player;
use function Jibix\AsyncMedoo\util\async;


/**
 * Class MySQLProvider
 * @author Jibix
 * @date 25.12.2024 - 13:27
 * @project Replay
 */
class MySQLProvider implements Provider{

    /** @var ReplayInformation[] */
    private array $replays = [];

    public const MAX_IDENTIFIER_LENGTH = 20;

    private const REPLAY_TABLE = "replays";
    private const PLAYER_REPLAY_DATA_TABLE = "player_replay_data";

    public function __construct(){
        $medoo = AsyncMedoo::getCredentials()->createConnection();
        $medoo->create(self::REPLAY_TABLE, [
            "identifier" => ["VARCHAR(" . self::MAX_IDENTIFIER_LENGTH . ")", "NOT NULL", "UNIQUE", "PRIMARY KEY"],
            "information" => ["VARCHAR(500)", "NOT NULL"],
            "data" => ["LONGBLOB"]
        ]);
        $medoo->create(self::PLAYER_REPLAY_DATA_TABLE, [
            "xuid" => ["VARCHAR(255)", "NOT NULL", "UNIQUE", "PRIMARY KEY"],
            "identifiers" => ["LONGBLOB", "NOT NULL"],
        ]);
        $medoo->pdo = null;
    }

    private static function getReplayInformation(Medoo $medoo, string $identifier): ?ReplayInformation{
        return ReplayInformation::deserialize(json_decode($medoo->get(
            self::REPLAY_TABLE,
            ["information"],
            ["identifier" => $identifier]
        )["information"] ?? '{}', true) ?? []);
    }

    public function initializeReplays(Player $player, Closure $onComplete): void{
        $xuid = $player->getXuid();
        $replays = $this->replays;
        async(static function (Medoo $medoo) use ($xuid, $replays): array{
            $identifiers = json_decode($medoo->get(self::PLAYER_REPLAY_DATA_TABLE, ["identifiers"], ["xuid" => $xuid])['identifiers'] ?? "{}", true);
            $count = count($identifiers);
            foreach ($identifiers as $i => $identifier) {
                if (isset($replays[$identifier])) continue;
                if (!$information = self::getReplayInformation($medoo, $identifier)) {
                    unset($identifiers[$i]);
                    continue;
                }
                $replays[$information->getIdentifier()] = $information;
            }
            if (count($identifiers) < $count) $medoo->update(self::PLAYER_REPLAY_DATA_TABLE, ["identifiers" => json_encode($identifiers)], ["xuid" => $xuid]);
            return [$replays, $identifiers];
        }, function (array $data) use ($onComplete): void{
            [$replays, $identifiers] = $data;
            $this->replays = array_merge($this->replays, $replays);
            ($onComplete)($identifiers);
        });
    }

    public function searchReplay(string $identifier, Closure $onComplete): void{
        if (($information = $this->getReplay($identifier)) !== null) {
            ($onComplete)($information);
            return;
        }
        async(static fn (Medoo $medoo): ?ReplayInformation => self::getReplayInformation($medoo, $identifier), $onComplete);
    }

    public function getReplay(string $identifier): ?ReplayInformation{
        return $this->replays[$identifier] ?? null;
    }

    public function getReplays(): array{
        return $this->replays;
    }

    public function getReplayData(ReplayInformation $information, Closure $onComplete): void{
        $identifier = $information->getIdentifier();
        async(static fn (Medoo $medoo): string => $medoo->get(self::REPLAY_TABLE, ["data"], ["identifier" => $identifier])['data'], $onComplete);
    }

    public function saveReplayData(ReplayInformation $information, array $xuids, string $buffer, ?Closure $onComplete = null): void{
        $data = $information->serialize();
        async(static function (Medoo $medoo) use ($data, $xuids, $buffer): void{
            $medoo->insert(self::REPLAY_TABLE, [
                "identifier" => $identifier = $data['identifier'],
                "information" => json_encode($data),
                "data" => $buffer
            ]);
            foreach ($xuids as $xuid) {
                if ($medoo->has(self::PLAYER_REPLAY_DATA_TABLE, ["xuid" => $xuid])) {
                    $medoo->update(self::PLAYER_REPLAY_DATA_TABLE, ["identifiers" => json_encode(array_merge(
                        [$identifier],
                        json_decode($medoo->get(self::PLAYER_REPLAY_DATA_TABLE, ["identifiers"], ["xuid" => $xuid])['identifiers'] ?? [], true)
                    ))], ["xuid" => $xuid]);
                } else {
                    $medoo->insert(self::PLAYER_REPLAY_DATA_TABLE, ["xuid" => $xuid, "identifiers" => json_encode([$identifier])]);
                }
            }
        }, fn () => $onComplete($information));
    }

    public function deleteReplay(string $identifier, ?Closure $onComplete = null): void{
        //TODO: find a solution to efficiently delete the replay id of the players table
        //we could just store the xuid's in the replay data but i'll try to find a better way
        async(static fn (Medoo $medoo) => $medoo->delete(self::REPLAY_TABLE, ['identifier' => $identifier]), $onComplete);
        unset($this->replays[$identifier]);
    }
}