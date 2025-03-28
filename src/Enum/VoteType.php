<?php

namespace App\Enum;

enum VoteType: string
{
    case UP = 'UP';
    case DOWN = 'DOWN';
    case NONE = 'NONE';
}