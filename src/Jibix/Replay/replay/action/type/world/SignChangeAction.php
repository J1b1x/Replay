<?php
namespace Jibix\Replay\replay\action\type\world;
use Jibix\Replay\replay\action\Action;
use Jibix\Replay\replay\action\ActionIds;
use Jibix\Replay\replay\action\type\WorldAction;
use Jibix\Replay\replay\replayer\Replay;
use Jibix\Replay\util\BinaryStream;
use pocketmine\block\BaseSign;
use pocketmine\block\utils\SignText;
use pocketmine\color\Color;
use pocketmine\math\Vector3;


/**
 * Class SignChangeAction
 * @author Jibix
 * @date 25.12.2024 - 23:14
 * @project Replay
 */
class SignChangeAction extends WorldAction{

    protected const ID = ActionIds::SIGN_CHANGE;

    private Vector3 $position;
    private SignText $text;

    public static function create(BaseSign $sign): self{
        $action = new self();
        $action->position = $sign->getPosition();
        $action->text = $sign->getText();
        return $action;
    }

    public function serialize(BinaryStream $stream): void{
        $stream->putVector3($this->position);
        $stream->putString(implode("\n", $this->text->getLines()));
        $stream->putBool($this->text->isGlowing());
        $stream->putInt($this->text->getBaseColor()->toRGBA());
    }

    public function deserialize(BinaryStream $stream): void{
        $this->position = $stream->getVector3();
        $this->text = SignText::fromBlob($stream->getString(), Color::fromRGBA($stream->getInt()), $stream->getBool());
    }

    public function handle(Replay $replay): void{
        $block = $replay->getWorld()->getBlock($this->position);
        if ($block instanceof BaseSign) $replay->getWorld()->setBlock($this->position, $block->setText($this->text));
    }

    public function handleReversed(Replay $replay): ?Action{
        $block = $replay->getWorld()->getBlock($this->position);
        return $block instanceof BaseSign ? self::create($block) : parent::handleReversed($replay);
    }
}