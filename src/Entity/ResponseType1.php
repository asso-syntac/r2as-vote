<?php

namespace App\Entity;

use App\Repository\ResponseType1Repository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ResponseType1Repository::class)]
class ResponseType1
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private $id;

    #[ORM\ManyToOne(targetEntity: Events::class, inversedBy: 'responseType1s')]
    #[ORM\JoinColumn(nullable: false)]
    private $event_id;

    #[ORM\ManyToOne(targetEntity: Users::class)]
    #[ORM\JoinColumn(nullable: false)]
    private $user_id;

    #[ORM\ManyToOne(targetEntity: Proposal::class, inversedBy: 'responseType1s')]
    #[ORM\JoinColumn(nullable: false)]
    private $proposal_id;

    #[ORM\Column(type: 'integer', nullable: true)]
    private $positive;

    #[ORM\Column(type: 'integer', nullable: true)]
    private $negative;

    #[ORM\Column(type: 'integer', nullable: true)]
    private $abstention;

    public function getId(): ?int
    {
        return $this->id;
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

    public function getUserId(): ?Users
    {
        return $this->user_id;
    }

    public function setUserId(?Users $user_id): self
    {
        $this->user_id = $user_id;

        return $this;
    }

    public function getProposalId(): ?Proposal
    {
        return $this->proposal_id;
    }

    public function setProposalId(?Proposal $proposal_id): self
    {
        $this->proposal_id = $proposal_id;

        return $this;
    }

    public function getpositive(): ?int
    {
        return $this->positive;
    }

    public function setpositive(int $positive): self
    {
        $this->positive = $positive;

        return $this;
    }

    public function getNegative(): ?int
    {
        return $this->negative;
    }

    public function setNegative(?int $negative): self
    {
        $this->negative = $negative;

        return $this;
    }

    public function getAbstention(): ?int
    {
        return $this->abstention;
    }

    public function setAbstention(?int $abstention): self
    {
        $this->abstention = $abstention;

        return $this;
    }
}
