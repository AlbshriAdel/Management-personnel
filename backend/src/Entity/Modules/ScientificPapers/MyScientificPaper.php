<?php

namespace App\Entity\Modules\ScientificPapers;

use App\Entity\Interfaces\EntityInterface;
use App\Entity\Interfaces\SoftDeletableEntityInterface;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass="App\Repository\Modules\ScientificPapers\MyScientificPaperRepository")
 * @ORM\Table(name="my_scientific_paper")
 */
class MyScientificPaper implements SoftDeletableEntityInterface, EntityInterface
{
    public const STATUS_IN_PROGRESS = 'in_progress';
    public const STATUS_UNDER_REVIEW = 'under_review';
    public const STATUS_PUBLISHED = 'published';

    public const STATUSES = [
        self::STATUS_IN_PROGRESS,
        self::STATUS_UNDER_REVIEW,
        self::STATUS_PUBLISHED,
    ];

    /**
     * @ORM\Id()
     * @ORM\GeneratedValue()
     * @ORM\Column(type="integer")
     */
    private ?int $id = null;

    /**
     * @ORM\Column(type="string", length=500)
     */
    private string $title = '';

    /**
     * @ORM\Column(type="text", nullable=true)
     */
    private ?string $abstract = null;

    /**
     * @ORM\Column(type="string", length=50)
     */
    private string $status = self::STATUS_IN_PROGRESS;

    /**
     * @ORM\Column(type="boolean")
     */
    private bool $deleted = false;

    /**
     * @ORM\Column(type="datetime")
     */
    private ?\DateTimeInterface $createdAt = null;

    /**
     * @ORM\Column(type="datetime")
     */
    private ?\DateTimeInterface $updatedAt = null;

    /**
     * @ORM\OneToMany(targetEntity=MyScientificPaperChecklistItem::class, mappedBy="paper", cascade={"persist", "remove"}, orphanRemoval=true)
     * @ORM\OrderBy({"sortOrder" = "ASC"})
     */
    private Collection $checklistItems;

    /**
     * @ORM\OneToMany(targetEntity=MyScientificPaperVersion::class, mappedBy="paper", cascade={"persist", "remove"}, orphanRemoval=true)
     * @ORM\OrderBy({"createdAt" = "DESC"})
     */
    private Collection $versions;

    public function __construct()
    {
        $this->checklistItems = new ArrayCollection();
        $this->versions = new ArrayCollection();
        $this->createdAt = new \DateTime();
        $this->updatedAt = new \DateTime();
    }

    public function getId(): ?int
    {
        return $this->id;
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

    public function getAbstract(): ?string
    {
        return $this->abstract;
    }

    public function setAbstract(?string $abstract): self
    {
        $this->abstract = $abstract;
        return $this;
    }

    public function getStatus(): string
    {
        return $this->status;
    }

    public function setStatus(string $status): self
    {
        if (!in_array($status, self::STATUSES, true)) {
            throw new \InvalidArgumentException("Invalid status: {$status}");
        }
        $this->status = $status;
        return $this;
    }

    public function isDeleted(): ?bool
    {
        return $this->deleted;
    }

    public function setDeleted(bool $deleted): self
    {
        $this->deleted = $deleted;
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

    public function getUpdatedAt(): ?\DateTimeInterface
    {
        return $this->updatedAt;
    }

    public function setUpdatedAt(\DateTimeInterface $updatedAt): self
    {
        $this->updatedAt = $updatedAt;
        return $this;
    }

    /**
     * @return Collection|MyScientificPaperChecklistItem[]
     */
    public function getChecklistItems(): Collection
    {
        return $this->checklistItems;
    }

    public function addChecklistItem(MyScientificPaperChecklistItem $item): self
    {
        if (!$this->checklistItems->contains($item)) {
            $this->checklistItems[] = $item;
            $item->setPaper($this);
        }
        return $this;
    }

    public function removeChecklistItem(MyScientificPaperChecklistItem $item): self
    {
        if ($this->checklistItems->removeElement($item)) {
            if ($item->getPaper() === $this) {
                $item->setPaper(null);
            }
        }
        return $this;
    }

    /**
     * @return Collection|MyScientificPaperVersion[]
     */
    public function getVersions(): Collection
    {
        return $this->versions;
    }

    public function addVersion(MyScientificPaperVersion $version): self
    {
        if (!$this->versions->contains($version)) {
            $this->versions[] = $version;
            $version->setPaper($this);
        }
        return $this;
    }

    public function removeVersion(MyScientificPaperVersion $version): self
    {
        if ($this->versions->removeElement($version)) {
            if ($version->getPaper() === $this) {
                $version->setPaper(null);
            }
        }
        return $this;
    }
}
