<?php

namespace App\Entity\Modules\ScientificPapers;

use App\Entity\Interfaces\EntityInterface;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass="App\Repository\Modules\ScientificPapers\MyScientificPaperChecklistItemRepository")
 * @ORM\Table(name="my_scientific_paper_checklist_item")
 */
class MyScientificPaperChecklistItem implements EntityInterface
{
    /**
     * @ORM\Id()
     * @ORM\GeneratedValue()
     * @ORM\Column(type="integer")
     */
    private ?int $id = null;

    /**
     * @ORM\ManyToOne(targetEntity=MyScientificPaper::class, inversedBy="checklistItems")
     * @ORM\JoinColumn(nullable=false, onDelete="CASCADE")
     */
    private ?MyScientificPaper $paper = null;

    /**
     * @ORM\Column(type="string", length=500)
     */
    private string $title = '';

    /**
     * @ORM\Column(type="boolean")
     */
    private bool $completed = false;

    /**
     * @ORM\Column(type="integer")
     */
    private int $sortOrder = 0;

    /**
     * @ORM\Column(type="datetime")
     */
    private ?\DateTimeInterface $createdAt = null;

    public function __construct()
    {
        $this->createdAt = new \DateTime();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getPaper(): ?MyScientificPaper
    {
        return $this->paper;
    }

    public function setPaper(?MyScientificPaper $paper): self
    {
        $this->paper = $paper;
        return $this;
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    public function setTitle(string $title): self
    {
        $this->title = $title;
        return $this;
    }

    public function isCompleted(): bool
    {
        return $this->completed;
    }

    public function setCompleted(bool $completed): self
    {
        $this->completed = $completed;
        return $this;
    }

    public function getSortOrder(): int
    {
        return $this->sortOrder;
    }

    public function setSortOrder(int $sortOrder): self
    {
        $this->sortOrder = $sortOrder;
        return $this;
    }

    public function getCreatedAt(): ?\DateTimeInterface
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTimeInterface $createdAt): self
    {
        $this->createdAt = $createdAt;
        return $this;
    }
}
