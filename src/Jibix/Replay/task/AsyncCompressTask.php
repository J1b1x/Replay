<?php
namespace Jibix\Replay\task;
use Closure;
use Jibix\Replay\replay\data\ReplayInformation;
use pocketmine\scheduler\AsyncTask;


/**
 * Class AsyncCompressTask
 * @author Jibix
 * @date 26.12.2024 - 00:41
 * @project Replay
 */
class AsyncCompressTask extends AsyncTask{

    public function __construct(ReplayInformation $information, private string $buffer, Closure $onComplete){
        $this->storeLocal("information", $information);
        $this->storeLocal("callback", $onComplete);
    }

    public function onRun(): void{
        $this->setResult(zlib_encode($this->buffer, ZLIB_ENCODING_RAW, 9));
    }

    public function onCompletion(): void{
        ($this->fetchLocal("callback"))($this->fetchLocal("information"), $this->getResult());
    }
}