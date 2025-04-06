<?php

namespace App\Enum;

enum RoleType: string
{
    case CLIENT = 'CLIENT';
    case ADMIN = 'ADMIN';
    case COACH = 'COACH';
}