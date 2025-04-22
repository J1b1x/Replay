<?php
namespace Jibix\Replay\replay\action;


/**
 * Interface ActionIds
 * @author Jibix
 * @date 25.12.2024 - 22:37
 * @project Replay
 */
interface ActionIds{

    public const NONE = 0;
    public const PLAYER_ANIMATION = 1;
    public const ENTITY_CHANGE_SKIN = 2;
    public const ENTITY_PLAY_EMOTE = 3;
    public const ACTOR_EVENT = 4;
    public const ENTITY_EQUIP = 5;
    public const SET_METADATA = 6;
    public const ENTITY_ARMOR_EQUIP = 7;
    public const ACTOR_DESPAWN = 8;
    public const ACTOR_DEATH = 9;
    public const BLOCK_SET = 10;
    public const GAMERULE_CHANGE = 11;
    public const PLAYER_CHAT = 12;
    public const LEVEL_EVENT = 13;
    public const ENTITY_MOVE = 14;
    public const SIGN_CHANGE = 15;
    public const ENTITY_SPAWN = 16;
    public const ITEM_SPAWN = 17;
    public const BLOCK_EVENT = 18;
    public const WORLD_CHANGE = 19;
    public const WORLD_CHANGE_TIME = 20;
}