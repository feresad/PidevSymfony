<?php

namespace App\Entity;

enum Role: string
{
    case CLIENT = 'CLIENT';
    case ADMIN = 'ADMIN';
    case COACH = 'COACH';
}
