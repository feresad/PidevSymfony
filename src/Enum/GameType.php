<?php

namespace App\Enum;

enum GameType: string
{
    case FPS = 'FPS';
    case HERO_SHOOTER = 'Hero Shooter';
    case THIRD_PERSON_SHOOTER = 'Third Person Shooter';
    case SPORTS = 'Sports';
    case OTHER = 'Other';
}