<?php
/**
 * Class JsonProvider
 * @author Jibix
 * @date 22.04.2025 - 16:28
 * @project Replay
 */
namespace Jibix\Replay\provider\type;
use Closure;
use Jibix\Replay\provider\Provider;
use Jibix\Replay\replay\data\ReplayInformation;
use pocketmine\player\Player;
use pocketmine\scheduler\AsyncTask;
use pocketmine\utils\Config;
use function Jibix\AsyncMedoo\util\async;


class JsonProvider implements Provider{

    //YEAH, this is actually just trash. But idgaf about those 13yo server owners that don't know how to set up a MySQL db, so this is all they get.

    /** @var ReplayInformation[] */
    private array $replays = [];

    public function __construct(private string $replayFilePath){}

    private static function getReplayInformation(Config $config, string $identifier): ?ReplayInformation{
        return ReplayInformation::deserialize($config->get($identifier, []));
    }

    public function initializeReplays(Player $player, Closure $onComplete): void{
        $xuid = $player->getXuid();
        $replays = $this->replays;
        async(new AccessFileAsyncTask(
                $this->replayFilePath,
                static function (Config $config) use ($xuid, $replays): array{
                    $count = count($identifiers = $config->getNested("players.$xuid", []));
                    foreach ($identifiers as $i => $identifier) {
                        if (isset($replays[$identifier])) continue;
                        if (!$information = self::getReplayInformation($config, $identifier)) {
                            unset($identifiers[$i]);
                            continue;
                        }
                        $replays[$information->getIdentifier()] = $information;
                    }
                    if (count($identifiers) < $count) {
                        $config->setNested("players.$xuid", $identifiers);
                        $config->save();
                    }
                    return [$replays, $identifiers];
                }, function (array $data) use ($onComplete): void{
                [$replays, $identifiers] = $data;
                $this->replays = array_merge($this->replays, $replays);
                ($onComplete)($identifiers);
            })
        );
    }

    public function searchReplay(string $identifier, Closure $onComplete): void{
        if (($information = $this->getReplay($identifier)) !== null) {
            ($onComplete)($information);
            return;
        }
        async(new AccessFileAsyncTask(
            $this->replayFilePath,
            static fn (Config $config): ?ReplayInformation => self::getReplayInformation($config, $identifier),
            $onComplete
        ));
    }

    public function getReplay(string $identifier): ?ReplayInformation{
        return $this->replays[$identifier] ?? null;
    }

    public function getReplays(): array{
        return $this->replays;
    }

    public function getReplayData(ReplayInformation $information, Closure $onComplete): void{
        $identifier = $information->getIdentifier();
        async(new AccessFileAsyncTask(
            $this->replayFilePath,
            static fn (Config $config): string => file_get_contents(self::getPath($config->getPath()) . "$identifier.bin"),
            $onComplete
        ));
    }

    public function saveReplayData(ReplayInformation $information, array $xuids, string $buffer, ?Closure $onComplete = null): void{
        $data = $information->serialize();
        async(new AccessFileAsyncTask(
            $this->replayFilePath,
            static function (Config $config) use ($data, $xuids, $buffer): void{
                $config->set($identifier = $data['identifier'], $data);
                foreach ($xuids as $xuid) {
                    $config->setNested("players.$xuid", array_merge([$identifier], $config->getNested("players.$xuid", [])));
                }
                $config->save();
                file_put_contents(self::getPath($config->getPath()) . "$identifier.bin", $buffer);
            },
            fn () => $onComplete($information)
        ));
    }

    public function deleteReplay(string $identifier, ?Closure $onComplete = null): void{
        async(new AccessFileAsyncTask($this->replayFilePath, static function (Config $config) use ($identifier): void{
            $config->remove($identifier);
            $config->save();
        }, $onComplete));
        unset($this->replays[$identifier]);
    }

    private static function getPath(string $path): string{
        return rtrim(dirname($path), DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;
    }
}

class AccessFileAsyncTask extends AsyncTask{

    public function __construct(private string $path, private Closure $task, Closure $onComplete){
        $this->storeLocal("onComplete", $onComplete);
    }

    public function onRun(): void{
        $this->setResult(($this->task)(new Config($this->path, Config::JSON)));
    }

    public function onCompletion(): void{
        ($this->fetchLocal("onComplete"))($this->getResult());
    }
}