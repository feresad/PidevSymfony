<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;

#[ORM\Entity]
#[ORM\Table(name: "utilisateur")]
class Utilisateur implements UserInterface, PasswordAuthenticatedUserInterface
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: "integer")]
    private int $id;

    #[ORM\Column(type: "string", length: 255, name: "nom")]
    private string $nom;

    #[ORM\Column(type: "string", length: 255, name: "prenom")]
    private string $prenom;

    #[ORM\Column(type: "string", length: 255, unique: true)]
    private string $email;

    #[ORM\Column(type: "string", length: 255, unique: true)]
    private string $nickname;

    #[ORM\Column(type: "integer", name: "numero")]
    private int $numero;

    #[ORM\Column(type: "string", length: 255, name: "mot_passe")]
    private string $mot_passe;

    #[ORM\Column(type: "string", enumType: Role::class)]
    private Role $role;

    public function setRole(string|Role $role): self
    {
        if (is_string($role)) {
            $this->role = Role::from($role);
        } else {
            $this->role = $role;
        }
        return $this;
    }

    public function getRole(): Role
    {
        return $this->role;
    }

    public function getRoles(): array
    {
        return ["ROLE_" . $this->role->value];
    }

    #[ORM\Column(type: "string", length: 50, options: ["default" => "regular"])]
    private string $privilege = "regular";

    #[ORM\Column(type: "boolean", options: ["default" => false])]
    private bool $ban = false;

    #[ORM\Column(name: "banTime", type: "datetime", nullable: true)]
    private ?\DateTimeInterface $banTime = null;

    #[ORM\Column(name: "countRep", type: "integer", options: ["default" => 0])]
    private int $countRep = 0;

    #[ORM\Column(type: "string", length: 255, nullable: true)]
    private ?string $photo = null;

    public function getId(): int
    {
        return $this->id;
    }

    public function getNom(): string
    {
        return $this->nom;
    }
    public function setNom(string $nom): self
    {
        $this->nom = $nom;
        return $this;
    }

    public function getPrenom(): string
    {
        return $this->prenom;
    }
    public function setPrenom(string $prenom): self
    {
        $this->prenom = $prenom;
        return $this;
    }

    public function getEmail(): string
    {
        return $this->email;
    }
    public function setEmail(string $email): self
    {
        $this->email = $email;
        return $this;
    }

    public function getNickname(): string
    {
        return $this->nickname;
    }
    public function setNickname(string $nickname): self
    {
        $this->nickname = $nickname;
        return $this;
    }

    public function getNumero(): int
    {
        return $this->numero;
    }
    public function setNumero(int $numero): self
    {
        $this->numero = $numero;
        return $this;
    }

    public function getPassword(): string
    {
        return $this->mot_passe;
    }

    public function setMotPasse(string $motPasse): self
    {
        $this->mot_passe = $motPasse;
        return $this;
    }

    public function verifyPassword(string $plainPassword): bool
    {
        return password_verify($plainPassword, $this->mot_passe);
    }

    public function getPrivilege(): string
    {
        return $this->privilege;
    }
    public function setPrivilege(string $privilege): self
    {
        $this->privilege = $privilege;
        return $this;
    }

    public function isBan(): bool
    {
        return $this->ban;
    }
    public function setBan(bool $ban): self
    {
        $this->ban = $ban;
        return $this;
    }

    public function getBanTime(): ?\DateTimeInterface
    {
        return $this->banTime;
    }
    public function setBanTime(?\DateTimeInterface $banTime): self
    {
        $this->banTime = $banTime;
        return $this;
    }

    public function getCountRep(): int
    {
        return $this->countRep;
    }
    public function setCountRep(int $countRep): self
    {
        $this->countRep = $countRep;
        return $this;
    }

    public function getPhoto(): ?string
    {
        return $this->photo;
    }
    public function setPhoto(?string $photo): self
    {
        $this->photo = $photo;
        return $this;
    }

    // Implémentation UserInterface pour Symfony Security
    public function getUserIdentifier(): string
    {
        return $this->email;
    }

    public function eraseCredentials(): void
    {
        // If you store any temporary, sensitive data on the user, clear it here
    }

    public function getUsername(): string
    {
        return $this->nickname;
    }

    public function setUsername(string $username): self
    {
        $this->nickname = $username;
        return $this;
    }

    public function __construct()
    {
        $this->role = Role::CLIENT;
        $this->privilege = "regular";
        $this->ban = false;
        $this->countRep = 0;
    }

    // Static method to handle localStorage data
    public static function createFromLocalStorage(): ?self
    {
        $userId = '<script>document.write(localStorage.getItem("userId"))</script>';
        $userEmail = '<script>document.write(localStorage.getItem("userEmail"))</script>';
        $userRole = '<script>document.write(localStorage.getItem("userRole"))</script>';

        if (!$userId || !$userEmail || !$userRole) {
            return null;
        }

        $user = new self();
        $user->id = (int)$userId;
        $user->email = $userEmail;
        $user->setRole($userRole);

        return $user;
    }

    public static function getUserIdFromLocalStorage(): ?int
    {
        $userId = '<script>document.write(localStorage.getItem("userId"))</script>';
        return $userId ? (int)$userId : null;
    }

    public static function getUserEmailFromLocalStorage(): ?string
    {
        $userEmail = '<script>document.write(localStorage.getItem("userEmail"))</script>';
        return $userEmail ?: null;
    }
}
