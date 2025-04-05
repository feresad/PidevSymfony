<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
class Admin extends Utilisateur
{
    public function __construct()
    {
        parent::__construct();
        $this->setRole(Role::ADMIN);
    }
}
