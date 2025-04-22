<?php
namespace Jibix\Replay\replay\replayer\util;
use pocketmine\world\ChunkManager;
use pocketmine\world\generator\Generator;


/**
 * Class ReplayGenerator
 * @author Jibix
 * @date 26.12.2024 - 00:38
 * @project Replay
 */
class ReplayGenerator extends Generator{

    public function generateChunk(ChunkManager $world, int $chunkX, int $chunkZ): void{}

    public function populateChunk(ChunkManager $world, int $chunkX, int $chunkZ): void{}
}