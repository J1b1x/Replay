<?php
namespace Jibix\Replay\util;
use DateTime;
use InvalidArgumentException;
use pocketmine\Server;
use ReflectionClass;


/**
 * Class Utils
 * @author Jibix
 * @date 25.12.2024 - 14:24
 * @project Replay
 */
final class Utils{

    /**
     * Get the formatted name out of a class
     * Example: getClassName("Jibix\Replay\ExampleClassName", [" Class Name", ""]) returns "Example"
     */
    public static function getClassName(string $class, array $replace = []): string{
        return str_replace(array_keys($replace), array_values($replace), implode(" ", preg_split(
            '#([A-Z][^A-Z]*)#',
            pathinfo(str_replace("\\", "/", $class), PATHINFO_FILENAME),
            -1,
            PREG_SPLIT_DELIM_CAPTURE | PREG_SPLIT_NO_EMPTY
        )));
    }

    /**
     * Check if all the required properties are provided in the $data array and throw an exception if not
     */
    public static function validateProperties(string $class, array $data, array $excluded = [], bool $includeBase = true): void{
        $reflection = new ReflectionClass($class);
        foreach ($reflection->getProperties() as $property) {
            if (
                !isset($excluded[$name = $property->getName()]) &&
                !isset($data[$name]) &&
                ($includeBase || $property->getDeclaringClass()->getName() === $reflection->getName())
            ) throw new InvalidArgumentException("$name could not be found in " . self::getClassName($class) . " data");
        }
    }

    /**
     * Completely removed a world after unloading it
     */
    public static function removeWorld(string $worldName): void{
        $manager = Server::getInstance()->getWorldManager();
        if ($manager->isWorldLoaded($worldName)) $manager->unloadWorld($manager->getWorldByName($worldName));
        popen("rm -r " . Server::getInstance()->getDataPath() . "worlds/$worldName", "r");
    }

    /**
     * Formats the replay duration in seconds to an actual time format
     */
    public static function formatReplayDuration(int $duration): string{
        $interval = (new DateTime())->diff((new DateTime())->modify("+" . $duration +1 . "second")); //gotta do +1 second since it round's down so 10s would be displayed as 9s
        $format = "";
        foreach (["y" => $interval->y, "mo" => $interval->m, "d" => $interval->d, "h" => $interval->h, "m" => $interval->i, "s" => $interval->s] as $key => $value) {
            if ($value <= 0) continue;
            $format .= $value . $key . " ";
        }
        return substr($format, 0, -1); //Remove space at the end of the string
    }
}