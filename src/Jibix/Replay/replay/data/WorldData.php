<?php
namespace Jibix\Replay\replay\data;
use Jibix\Replay\listener\record\RecordChunkListener;
use Jibix\Replay\replay\recorder\Recorder;
use Jibix\Replay\util\BinaryStream;
use pocketmine\math\Vector3;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\network\mcpe\protocol\types\CacheableNbt;
use pocketmine\world\format\Chunk;
use pocketmine\world\format\io\FastChunkSerializer;
use pocketmine\world\World;


/**
 * Class WorldData
 * @author Jibix
 * @date 25.12.2024 - 22:26
 * @project Replay
 */
class WorldData{

    /** @var Chunk[] */
    private array $chunks = [];

    /** @var CompoundTag[] */
    private array $tiles = [];
    private Vector3 $spawn;
    private int $initialTime;

    public static function create(World $world): self{
        $worldData = new self();
        $worldData->spawn = $world->getSafeSpawn();
        $worldData->initialTime = $world->getTimeOfDay();
        return $worldData;
    }

    public static function fromData(array $chunks, array $tiles, Vector3 $spawn, int $initialTime): self{
        $worldData = new self();
        $worldData->chunks = $chunks;
        $worldData->tiles = $tiles;
        $worldData->spawn = $spawn;
        $worldData->initialTime = $initialTime;
        return $worldData;
    }

    public function addChunk(Recorder $recorder, World $world, Chunk $chunk, int $chunkHash): void{
        World::getXZ($chunkHash, $x, $z);
        foreach ($chunk->getSubChunks() as $subChunk) {
            //TODO: find a better way than isset[chunkHash] cause if there's a new sub chunk that hasn't been added yet it would not add that sub chunk
            if ($subChunk->isEmptyFast() || isset($this->chunks[$chunkHash])) continue; //Storing empty chunks is unnecessary
            $this->chunks[$chunkHash] = clone $chunk;
            break;
        }
        $world->registerChunkListener(RecordChunkListener::getListener($recorder), $x, $z);
    }

    public function getChunks(): array{
        return $this->chunks;
    }

    public function getTiles(): array{
        return $this->tiles;
    }

    public function getSpawn(): Vector3{
        return $this->spawn;
    }

    public function getInitialTime(): int{
        return $this->initialTime;
    }


    public function encode(BinaryStream $stream): void{
        $stream->putInt(count($chunks = $this->chunks));
        foreach ($chunks as $hash => $chunk) {
            $stream->putLong($hash);
            $stream->putString(zlib_encode(FastChunkSerializer::serializeTerrain($chunk), ZLIB_ENCODING_RAW, 9));
            $stream->putInt(count($tiles = $chunk->getTiles()));
            foreach ($tiles as $tile) {
                $stream->put((new CacheableNbt($tile->saveNBT()))->getEncodedNbt());
            }
        }
        $stream->putVector3($this->spawn);
        $stream->putInt($this->initialTime);
    }

    public static function decode(BinaryStream $stream): self{
        $chunks = $tiles = [];
        $chunkCount = $stream->getInt();
        for ($i = 0; $i < $chunkCount; $i++) {
            $chunks[$stream->getLong()] = FastChunkSerializer::deserializeTerrain(zlib_decode($stream->getString()));
            $tileCount = $stream->getInt();
            for ($j = 0; $j < $tileCount; $j++) {
                $tiles[] = $stream->getNbtCompoundRoot();
            }
        }
        return WorldData::fromData($chunks, $tiles, $stream->getVector3(), $stream->getInt());
    }
}