<?php
namespace Jibix\Replay\replay\action\type\world;
use Jibix\Replay\replay\action\Action;
use Jibix\Replay\replay\action\ActionIds;
use Jibix\Replay\replay\action\type\WorldAction;
use Jibix\Replay\replay\replayer\Replay;
use Jibix\Replay\util\BinaryStream;
use pocketmine\block\Block;
use pocketmine\block\tile\TileFactory;
use pocketmine\math\Vector3;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\network\mcpe\protocol\types\CacheableNbt;


/**
 * Class SetBlockAction
 * @author Jibix
 * @date 25.12.2024 - 23:11
 * @project Replay
 */
class SetBlockAction extends WorldAction{

    protected const ID = ActionIds::BLOCK_SET;

    private Block $block;
    private Vector3 $position;
    private ?CompoundTag $tile = null; //we can't store the tile object because we don't have the world object serializing/deserializing

    public static function create(Block $block): self{
        $action = new self();
        $action->block = clone $block;
        $action->position = $pos = $block->getPosition();
        $action->tile = $pos->getWorld()->getTile($pos)?->saveNBT();
        return $action;
    }

    public function serialize(BinaryStream $stream): void{
        $stream->putBlock($this->block);
        $stream->putVector3($this->position);
        $stream->putBool($value = $this->tile !== null);
        if ($value) $stream->put((new CacheableNbt($this->tile))->getEncodedNbt());
    }

    public function deserialize(BinaryStream $stream): void{
        $this->block = $stream->getBlock();
        $this->position = $stream->getVector3();
        if ($stream->getBool()) $this->tile = $stream->getNbtCompoundRoot();
    }

    public function handle(Replay $replay): void{
        $world = $replay->getWorld();
        $world->setBlock($this->position, $this->block);
        if ($this->tile !== null) {
            $world->removeTile($tile = TileFactory::getInstance()->createFromData($world, $this->tile));
            $world->addTile($tile);
        }
    }

    public function handleReversed(Replay $replay): ?Action{
        return self::create($replay->getWorld()->getBlock($this->position));
    }
}