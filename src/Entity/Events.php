<?php

namespace App\Entity;

use App\Repository\EventsRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: EventsRepository::class)]
class Events
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private $id;

    #[ORM\Column(type: 'string', length: 255)]
    private $name;

    #[ORM\Column(type: 'string', length: 1024, nullable: true)]
    private $description;

    #[ORM\Column(type: 'string', length: 255, unique: true)]
    private $uuid;

    #[ORM\Column(type: 'string', length: 255)]
    private $mail;

    #[ORM\Column(type: 'boolean')]
    private $state;

    #[ORM\OneToMany(targetEntity: Proposal::class, mappedBy: 'event_id', orphanRemoval: true)]
    private $proposals;

    #[ORM\OneToMany(targetEntity: Users::class, mappedBy: 'event_id', orphanRemoval: true)]
    private $users;

    #[ORM\OneToMany(targetEntity: ResponseType1::class, mappedBy: 'event_id', orphanRemoval: true)]
    private $responseType1s;

    public function __construct()
    {
        $this->proposals = new ArrayCollection();
        $this->users = new ArrayCollection();
        $this->responseType1s = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): self
    {
        $this->name = $name;

        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): self
    {
        $this->description = $description;

        return $this;
    }

    public function getUuid(): ?string
    {
        return $this->uuid;
    }

    public function setUuid(string $uuid): self
    {
        $this->uuid = $uuid;

        return $this;
    }

    public function getMail(): ?string
    {
        return $this->mail;
    }

    public function setMail(string $mail): self
    {
        $this->mail = $mail;

        return $this;
    }

    public function getState(): ?bool
    {
        return $this->state;
    }

    public function setState(bool $state): self
    {
        $this->state = $state;

        return $this;
    }

    /**
     * @return Collection|Proposal[]
     */
    public function getProposals(): Collection
    {
        return $this->proposals;
    }

    public function addProposal(Proposal $proposal): self
    {
        if (!$this->proposals->contains($proposal)) {
            $this->proposals[] = $proposal;
            $proposal->setEventId($this);
        }

        return $this;
    }

    public function removeProposal(Proposal $proposal): self
    {
        if ($this->proposals->contains($proposal)) {
            $this->proposals->removeElement($proposal);
            // set the owning side to null (unless already changed)
            if ($proposal->getEventId() === $this) {
                $proposal->setEventId(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection|Users[]
     */
    public function getUsers(): Collection
    {
        return $this->users;
    }

    public function addUser(Users $user): self
    {
        if (!$this->users->contains($user)) {
            $this->users[] = $user;
            $user->setEventId($this);
        }

        return $this;
    }

    public function removeUser(Users $user): self
    {
        if ($this->users->contains($user)) {
            $this->users->removeElement($user);
            // set the owning side to null (unless already changed)
            if ($user->getEventId() === $this) {
                $user->setEventId(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection|ResponseType1[]
     */
    public function getResponseType1s(): Collection
    {
        return $this->responseType1s;
    }

    public function addResponseType1(ResponseType1 $responseType1): self
    {
        if (!$this->responseType1s->contains($responseType1)) {
            $this->responseType1s[] = $responseType1;
            $responseType1->setEventId($this);
        }

        return $this;
    }

    public function removeResponseType1(ResponseType1 $responseType1): self
    {
        if ($this->responseType1s->contains($responseType1)) {
            $this->responseType1s->removeElement($responseType1);
            // set the owning side to null (unless already changed)
            if ($responseType1->getEventId() === $this) {
                $responseType1->setEventId(null);
            }
        }

        return $this;
    }
}
