<?php

namespace App\Entity;

use App\Repository\ProposalRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ProposalRepository::class)]
class Proposal
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private $id;

    #[ORM\Column(type: 'integer')]
    private $type;

    #[ORM\Column(type: 'string', length: 512)]
    private $name;

    #[ORM\ManyToOne(targetEntity: Events::class, inversedBy: 'proposals')]
    #[ORM\JoinColumn(nullable: false)]
    private $event_id;

    #[ORM\OneToMany(targetEntity: ResponseType1::class, mappedBy: 'proposal_id', orphanRemoval: true)]
    private $responseType1s;

    public function __construct()
    {
        $this->responseType1s = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getType(): ?int
    {
        return $this->type;
    }

    public function setType(int $type): self
    {
        $this->type = $type;

        return $this;
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

    public function getEventId(): ?Events
    {
        return $this->event_id;
    }

    public function setEventId(?Events $event_id): self
    {
        $this->event_id = $event_id;

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
            $responseType1->setProposalId($this);
        }

        return $this;
    }

    public function removeResponseType1(ResponseType1 $responseType1): self
    {
        if ($this->responseType1s->contains($responseType1)) {
            $this->responseType1s->removeElement($responseType1);
            // set the owning side to null (unless already changed)
            if ($responseType1->getProposalId() === $this) {
                $responseType1->setProposalId(null);
            }
        }

        return $this;
    }
}
