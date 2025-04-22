<?php
namespace Jibix\Replay\replay\action;
use Jibix\Replay\replay\action\type\actor\ActorDeathAction;
use Jibix\Replay\replay\action\type\actor\ActorDespawnAction;
use Jibix\Replay\replay\action\type\actor\ActorEventAction;
use Jibix\Replay\replay\action\type\actor\ActorSetMetadataAction;
use Jibix\Replay\replay\action\type\entity\EntityArmorEquipAction;
use Jibix\Replay\replay\action\type\entity\EntityEquipAction;
use Jibix\Replay\replay\action\type\entity\EntityMoveAction;
use Jibix\Replay\replay\action\type\entity\EntityPlayEmoteAction;
use Jibix\Replay\replay\action\type\entity\EntitySpawnAction;
use Jibix\Replay\replay\action\type\entity\EntitySpawnItemAction;
use Jibix\Replay\replay\action\type\player\PlayerAnimationAction;
use Jibix\Replay\replay\action\type\player\PlayerChatAction;
use Jibix\Replay\replay\action\type\world\BlockEventAction;
use Jibix\Replay\replay\action\type\world\LevelEventAction;
use Jibix\Replay\replay\action\type\world\SetBlockAction;
use Jibix\Replay\replay\action\type\world\SignChangeAction;
use Jibix\Replay\replay\action\type\world\WorldChangeAction;
use Jibix\Replay\replay\action\type\world\WorldChangeTimeAction;
use pocketmine\utils\SingletonTrait;


/**
 * Class ActionHandler
 * @author Jibix
 * @date 25.12.2024 - 22:33
 * @project Replay
 */
final class ActionHandler{
    use SingletonTrait{
        setInstance as private;
        reset as private;
    }

    /** @var Action[] */
    private array $actions = [];

    private function __construct(){
        $this->registerAction(
            new EntitySpawnAction(),
            new EntitySpawnItemAction(),
            new EntityMoveAction(),
            new EntityEquipAction(),
            new EntityArmorEquipAction(),
            new EntityPlayEmoteAction(),

            new PlayerAnimationAction(),
            new PlayerChatAction(),

            new ActorDeathAction(),
            new ActorDespawnAction(),
            new ActorEventAction(),
            new ActorSetMetadataAction(),

            new WorldChangeAction(),
            new WorldChangeTimeAction(),
            new BlockEventAction(),
            new SetBlockAction(),
            new SignChangeAction(),
            new LevelEventAction(),
        );
    }

    public function registerAction(Action ...$actions): void{
        foreach ($actions as $action) {
            $this->actions[$action::id()] = clone $action;
        }
    }

    public function getActions(): array{
        return $this->actions;
    }

    public function getAction(int $id): ?Action{
        return isset($this->actions[$id]) ? clone $this->actions[$id] : null;
    }
}