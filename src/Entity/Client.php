<?php

namespace App\Entity;

use App\Repository\ClientRepository;
use Doctrine\ORM\Mapping as ORM;

class Client extends Utilisateur
{
    public function __construct()
    {
        $this->setRole("CLIENT");
    }
}