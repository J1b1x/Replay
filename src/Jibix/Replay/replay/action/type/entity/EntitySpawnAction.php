<?php
namespace Jibix\Replay\replay\action\type\entity;
use Jibix\Replay\entity\ReplayEntity;
use Jibix\Replay\entity\type\DefaultReplayEntity;
use Jibix\Replay\entity\type\ReplayHuman;
use Jibix\Replay\entity\type\ReplayLiving;
use Jibix\Replay\replay\action\Action;
use Jibix\Replay\replay\action\ActionIds;
use Jibix\Replay\replay\action\type\actor\ActorDespawnAction;
use Jibix\Replay\replay\action\type\EntityAction;
use Jibix\Replay\replay\replayer\Replay;
use Jibix\Replay\util\BinaryStream;
use pocketmine\entity\Entity;
use pocketmine\entity\EntitySizeInfo;
use pocketmine\entity\Human;
use pocketmine\entity\Living;
use pocketmine\entity\Location;
use pocketmine\entity\Skin;
use pocketmine\inventory\InventoryHolder;
use pocketmine\item\Item;
use pocketmine\item\VanillaItems;
use pocketmine\math\Vector3;
use pocketmine\network\mcpe\convert\TypeConverter;
use pocketmine\network\mcpe\protocol\types\entity\MetadataProperty;
use pocketmine\player\Player;


/**
 * Class EntitySpawnAction
 * @author Jibix
 * @date 25.12.2024 - 22:51
 * @project Replay
 */
class EntitySpawnAction extends EntityAction{

    protected const ID = ActionIds::ENTITY_SPAWN;

    private string $networkId;
    private bool $isPlayer;
    private string $nameTag;
    /** @var MetadataProperty[] */
    private array $metadata;
    private float $scale;
    private Location $location;
    private EntitySizeInfo $size;
    private Vector3 $offsetPosition;
    private Item $holdingItem;
    private Item $offHandItem;
    private array $armorContents = [];
    private ?Skin $skin = null;

    public static function create(Entity $entity): static{
        $action = new static();
        if ($entity instanceof ReplayEntity) {
            $action->entityId = $entity->getActualId(); //used for reversing
            $action->networkId = $entity->getCustomNetworkTypeId();
        } else {
            $action->entityId = $entity->getId();
            $action->networkId = $entity::getNetworkTypeId();
        }
        $action->isPlayer = $entity instanceof Player;
        $action->nameTag = $entity->getNameTag();
        $action->scale = $entity->getScale();
        $action->location = $entity->getLocation();
        $action->size = $entity->getSize();
        $action->offsetPosition = $entity->getOffsetPosition(Vector3::zero());
        $action->metadata = $entity->getNetworkProperties()->getAll();
        if ($entity instanceof Human) {
            $action->skin = $entity->getSkin();
            $action->holdingItem = $entity->getInventory()->getItemInHand();
            $action->offHandItem = $entity->getOffHandInventory()->getItem(0);
        } elseif ($entity instanceof InventoryHolder) {
            $action->holdingItem = $entity->getInventory()->getItem(0);
            $action->offHandItem = VanillaItems::AIR();
        } else {
            $action->holdingItem = $action->offHandItem = VanillaItems::AIR();
        }
        if ($entity instanceof Living) $action->armorContents = $entity->getArmorInventory()->getContents();
        return $action;
    }

    public function serialize(BinaryStream $stream): void{
        parent::serialize($stream);
        $stream->putString($this->networkId);
        $stream->putBool($this->isPlayer);
        $stream->putString($this->nameTag);
        $stream->putFloat($this->scale);
        $stream->putLocation($this->location);
        $stream->putFloat($this->size->getHeight());
        $stream->putFloat($this->size->getWidth());
        $stream->putFloat($this->size->getEyeHeight());
        $stream->putVector3($this->offsetPosition);
        $stream->putEntityMetadata($this->metadata);
        $stream->putBool($value = $this->skin !== null);
        if ($value) $stream->putSkin(TypeConverter::getInstance()->getSkinAdapter()->toSkinData($this->skin));
        $stream->putItem($this->holdingItem);
        $stream->putItem($this->offHandItem);
        $stream->putContents($this->armorContents);
    }

    public function deserialize(BinaryStream $stream): void{
        parent::deserialize($stream);
        $this->networkId = $stream->getString();
        $this->isPlayer = $stream->getBool();
        $this->nameTag = $stream->getString();
        $this->scale = $stream->getFloat();
        $this->location = $stream->getLocation();
        $this->size = new EntitySizeInfo($stream->getFloat(), $stream->getFloat(), $stream->getFloat());
        $this->offsetPosition = $stream->getVector3();
        $this->metadata = $stream->getEntityMetadata();
        if ($stream->getBool()) $this->skin = TypeConverter::getInstance()->getSkinAdapter()->fromSkinData($stream->getSkin());
        $this->holdingItem = $stream->getItem();
        $this->offHandItem = $stream->getItem();
        $this->armorContents = $stream->getContents();
    }

    public function handle(Replay $replay): void{
        $entity = $this->createEntity($replay);
        $entity->setNameTag($this->nameTag);
        $entity->setNameTagVisible(true);
        $entity->setNameTagAlwaysVisible(true);
        $entity->setScale($this->scale);
        if ($entity instanceof Living) $entity->getArmorInventory()->setContents($this->armorContents);
        if ($entity instanceof Human) {
            $entity->getInventory()->setItemInHand($this->holdingItem);
            $entity->getOffHandInventory()->setItem(0, $this->offHandItem);
        } elseif ($entity instanceof InventoryHolder) {
            $entity->getInventory()->setItem(0, $this->holdingItem);
        }
        foreach ($this->metadata as $key => $metadata) {
            $entity->getNetworkProperties()->set($key, $metadata);
        }
        $replay->spawnEntity($entity);
    }

    public function handleReversed(Replay $replay): ?Action{
        return ActorDespawnAction::create($this->entityId);
    }

    protected function createEntity(Replay $replay): Entity{
        $location = Location::fromObject($this->location, $replay->getWorld(), $this->location->yaw, $this->location->pitch);
        return match (true) {
            $this->skin !== null => new ReplayHuman($this->entityId, $this->isPlayer, $this->size, $this->offsetPosition, $location, $this->skin),
            count($this->armorContents) > 0 => new ReplayLiving($this->networkId, $this->entityId, $this->size, $this->offsetPosition, $location),
            default => new DefaultReplayEntity($this->networkId, $this->entityId, $this->size, $this->offsetPosition, $location)
        };
    }
}