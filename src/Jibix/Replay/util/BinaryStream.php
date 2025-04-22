<?php
namespace Jibix\Replay\util;
use pocketmine\block\Block;
use pocketmine\data\bedrock\block\BlockStateData;
use pocketmine\data\bedrock\item\SavedItemData;
use pocketmine\entity\Location;
use pocketmine\item\enchantment\EnchantmentInstance;
use pocketmine\item\enchantment\VanillaEnchantments;
use pocketmine\item\Item;
use pocketmine\item\VanillaItems;
use pocketmine\nbt\LittleEndianNbtSerializer;
use pocketmine\nbt\TreeRoot;
use pocketmine\network\mcpe\protocol\serializer\PacketSerializer;
use pocketmine\world\format\io\GlobalBlockStateHandlers;
use pocketmine\world\format\io\GlobalItemDataHandlers;


/**
 * Class BinaryStream
 * @author Jibix
 * @date 25.12.2024 - 13:18
 * @project Replay
 */
class BinaryStream extends PacketSerializer{

    public static function encoder(): self{
        return new self();
    }

    public static function decoder(string $buffer, int $offset): self{
        return new self($buffer, $offset);
    }

    public function putLocation(Location $location): void{
        $this->putVector3($location->asVector3());
        $this->putLFloat($location->yaw);
        $this->putLFloat($location->pitch);
    }

    public function getLocation(): Location{
        $vector = $this->getVector3();
        return new Location($vector->x, $vector->y, $vector->z, null, $this->getLFloat(), $this->getLFloat());
    }

    public function putBlock(Block $block): void{
        $this->putString((new LittleEndianNbtSerializer())->write(new TreeRoot(GlobalBlockStateHandlers::getSerializer()->serializeBlock($block)->toNbt())));
    }

    public function getBlock(): Block{
        return GlobalBlockStateHandlers::getDeserializer()->deserializeBlock(BlockStateData::fromNbt((new LittleEndianNbtSerializer())->read($this->getString())->mustGetCompoundTag()));
    }

    public function putItem(Item $item): void{
        $this->putBool($null = $item->isNull());
        if ($null) return;
        $serialized = GlobalItemDataHandlers::getSerializer()->serializeType($item);
        $this->putString($serialized->getName());
        $this->putInt($serialized->getMeta());
        $this->putBool(($state = $serialized->getBlock()) !== null);
        if ($state !== null) $this->putString((new LittleEndianNbtSerializer())->write(new TreeRoot($state->toNbt())));
        $this->putBool($item->hasEnchantments());
    }

    public function getItem(): Item{
        if ($this->getBool()) return VanillaItems::AIR();
        $item = GlobalItemDataHandlers::getDeserializer()->deserializeType(new SavedItemData(
            $this->getString(),
            $this->getInt(),
            $this->getBool() ? BlockStateData::fromNbt((new LittleEndianNbtSerializer())->read($this->getString())->mustGetCompoundTag()) : null
        ));
        if ($this->getBool()) $item->addEnchantment(new EnchantmentInstance(VanillaEnchantments::INFINITY())); //just for the visual enchantment effect
        return $item;
    }

    public function putContents(array $contents): void{
        $this->putVarInt(count($contents = array_filter($contents, fn (Item $item): bool => !$item->isNull())));
        foreach ($contents as $slot => $item) {
            $this->putInt($slot);
            $this->putItem($item);
        }
    }

    public function getContents(): array{
        $contents = [];
        $contentCount = $this->getVarInt();
        for ($i = 0; $i < $contentCount; $i++) {
            $contents[$this->getInt()] = $this->getItem();
        }
        return $contents;
    }
}