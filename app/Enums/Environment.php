<?php

namespace App\Enums;

enum Environment: string
{
    case Production = 'production';
    case Staging = 'staging';
    case Development = 'development';

    public function label(): string
    {
        return match($this) {
            self::Production => 'Production',
            self::Staging => 'Staging',
            self::Development => 'Development',
        };
    }

    public function color(): string
    {
        return match($this) {
            self::Production => 'red',
            self::Staging => 'yellow',
            self::Development => 'green',
        };
    }
}
