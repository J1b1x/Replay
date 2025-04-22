<?php
namespace Jibix\Replay\replay\recorder;
use Closure;
use Jibix\Replay\util\GameDetails;
use LogicException;
use pocketmine\utils\SingletonTrait;
use pocketmine\world\World;


/**
 * Class RecordHandler
 * @author Jibix
 * @date 25.12.2024 - 14:15
 * @project Replay
 */
final class RecordHandler{
    use SingletonTrait{
        setInstance as private;
        reset as private;
    }

    private function __construct(){}

    /** @var Recorder[] */
    private array $recordings = [];

    public function record(RecordSettings $settings, World $world, GameDetails $details): Recorder{
        if ($this->isRecording($world)) throw new LogicException("The world {$world->getDisplayName()} is already being recorded");
        return $this->recordings[$world->getId()] = new Recorder($settings, $world, $details);
    }

    public function stopRecording(World $world, ?Closure $onComplete = null): void{
        if (!$recorder = $this->getRecorder($world)) return;
        $recorder->end($onComplete);
        unset($this->recordings[$world->getId()]);
    }

    public function isRecording(World $world): bool{
        return isset($this->recordings[$world->getId()]);
    }

    public function getRecorder(World $world): ?Recorder{
        return $this->recordings[$world->getId()] ?? null;
    }

    public function getRecordings(): array{
        return $this->recordings;
    }

    public function changeRecordingWorld(Recorder $recorder, World $world): void{
        if ($this->isRecording($world)) throw new LogicException("The world {$world->getDisplayName()} is already being recorded");
        unset($this->recordings[$recorder->getWorld()->getId()]);
        $recorder->changeWorld($world);
        $this->recordings[$world->getId()] = $recorder;
    }
}