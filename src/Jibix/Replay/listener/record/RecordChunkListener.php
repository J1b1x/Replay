<?php
namespace Jibix\Replay\listener\record;
use Jibix\Replay\replay\action\type\world\SetBlockAction;
use Jibix\Replay\replay\recorder\Recorder;
use pocketmine\math\Vector3;
use pocketmine\world\ChunkListener;
use pocketmine\world\ChunkListenerNoOpTrait;


/**
 * Class RecordChunkListener
 * @author Jibix
 * @date 26.12.2024 - 00:43
 * @project Replay
 */
class RecordChunkListener implements ChunkListener{
    use ChunkListenerNoOpTrait;

    /** @var self[] */
    private static array $listeners = [];

    public function __construct(private Recorder $recorder){}

    public function onBlockChanged(Vector3 $block): void{
        $this->recorder->addAction(SetBlockAction::create($this->recorder->getWorld()->getBlock($block)));
    }

    public static function getListener(Recorder $recorder): self{
        if (isset(self::$listeners[$id = $recorder->getWorld()->getId()])) return self::$listeners[$id];
        return self::$listeners[$id] = new self($recorder);
    }
}