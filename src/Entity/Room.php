<?php

namespace App\Entity;

use App\Repository\RoomRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: RoomRepository::class)]
class Room
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 50)]
    #[Assert\NotBlank(message: "Room number is required.")]
    private ?string $number = null;

    #[ORM\Column(length: 50)]
    #[Assert\NotBlank(message: "Room type is required.")]
    private ?string $type = null;

    #[ORM\Column]
    #[Assert\NotBlank(message: "Capacity is required.")]
    #[Assert\Positive(message: "Capacity must be a positive number.")]
    private ?int $capacity = null;

    #[ORM\Column]
    #[Assert\NotBlank(message: "Price is required.")]
    #[Assert\PositiveOrZero(message: "Price must be zero or positive.")]
    private ?float $price = null;

    #[ORM\Column(length: 50)]
    #[Assert\NotBlank(message: "Status is required.")]
    #[Assert\Choice(choices: ['available', 'reserved', 'maintenance'], message: "Invalid status.")]
    private ?string $status = 'available';

    #[ORM\ManyToOne(inversedBy: 'rooms')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Hotel $hotel = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getNumber(): ?string
    {
        return $this->number;
    }

    public function setNumber(string $number): self
    {
        $this->number = $number;

        return $this;
    }

    public function getType(): ?string
    {
        return $this->type;
    }

    public function setType(string $type): self
    {
        $this->type = $type;

        return $this;
    }

    public function getCapacity(): ?int
    {
        return $this->capacity;
    }

    public function setCapacity(int $capacity): self
    {
        $this->capacity = $capacity;

        return $this;
    }

    public function getPrice(): ?float
    {
        return $this->price;
    }

    public function setPrice(float $price): self
    {
        $this->price = $price;

        return $this;
    }

    public function getStatus(): ?string
    {
        return $this->status;
    }

    public function setStatus(string $status): self
    {
        $this->status = $status;

        return $this;
    }

    public function getHotel(): ?Hotel
    {
        return $this->hotel;
    }

    public function setHotel(?Hotel $hotel): self
    {
        $this->hotel = $hotel;

        return $this;
    }
}
