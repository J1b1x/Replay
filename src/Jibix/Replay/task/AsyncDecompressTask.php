<?php
namespace Jibix\Replay\task;
use Closure;
use pocketmine\scheduler\AsyncTask;


/**
 * Class AsyncDecompressTask
 * @author Jibix
 * @date 26.12.2024 - 00:41
 * @project Replay
 */
class AsyncDecompressTask extends AsyncTask{

    public function __construct(private string $buffer, Closure $onComplete){
        $this->storeLocal("callback", $onComplete);
    }

    public function onRun(): void{
        $this->setResult(zlib_decode($this->buffer));
    }

    public function onCompletion(): void{
        ($this->fetchLocal("callback"))($this->getResult());
    }
}