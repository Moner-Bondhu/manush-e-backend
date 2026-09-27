<?php

namespace App\ExperienceStudio;

class ExperienceBlockType
{
    public const PLAY = 'play';
    public const PULSE = 'pulse';
    public const PRACTICE = 'practice';
    public const LEARN = 'learn';
    public const SCENARIO = 'scenario';
    public const CREATE = 'create';
    public const REFLECT = 'reflect';
    public const CONNECT = 'connect';
    public const MOVE = 'move';
    public const OBSERVE = 'observe';

    public static function values(): array
    {
        return [
            self::PLAY, self::PULSE, self::PRACTICE, self::LEARN, self::SCENARIO,
            self::CREATE, self::REFLECT, self::CONNECT, self::MOVE, self::OBSERVE,
        ];
    }

    public static function normalise(?string $type, string $fallback): string
    {
        $value = strtolower(trim((string) $type));
        $legacy = [
            'game' => self::PLAY,
            'interactive_material' => self::LEARN,
            'project' => self::CREATE,
            'reflection' => self::REFLECT,
            'teacher_moment' => self::CONNECT,
            'family_moment' => self::CONNECT,
            'offline_activity' => self::MOVE,
            'showcase' => self::CREATE,
        ];

        $value = $legacy[$value] ?? $value;

        return in_array($value, self::values(), true) ? $value : $fallback;
    }
}
