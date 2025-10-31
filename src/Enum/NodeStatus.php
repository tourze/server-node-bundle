<?php

declare(strict_types=1);

namespace ServerNodeBundle\Enum;

use Tourze\EnumExtra\Itemable;
use Tourze\EnumExtra\ItemTrait;
use Tourze\EnumExtra\Labelable;
use Tourze\EnumExtra\Selectable;
use Tourze\EnumExtra\SelectTrait;

enum NodeStatus: string implements Labelable, Itemable, Selectable
{
    use ItemTrait;
    use SelectTrait;

    case INIT = 'INIT';
    case ONLINE = 'ON-LINE';
    case OFFLINE = 'OFF-LINE';
    case BANDWIDTH_OVER = 'BANDWIDTH-OVER';
    case MAINTAIN = 'MAINTAIN';

    public function getLabel(): string
    {
        return match ($this) {
            self::INIT => '初始化',
            self::ONLINE => '正常',
            self::OFFLINE => '离线',
            self::BANDWIDTH_OVER => '流量用完',
            self::MAINTAIN => '维护中',
        };
    }
}
