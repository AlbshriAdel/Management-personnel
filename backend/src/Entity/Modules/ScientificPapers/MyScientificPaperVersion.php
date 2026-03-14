<?php

namespace App\Entity\Modules\ScientificPapers;

use App\Entity\Interfaces\EntityInterface;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass="App\Repository\Modules\ScientificPapers\MyScientificPaperVersionRepository")
 * @ORM\Table(name="my_scientific_paper_version")
 */
class MyScientificPaperVersion implements EntityInterface
{
    /**
     * @ORM\Id()
     * @ORM\GeneratedValue()
     * @ORM\Column(type="integer")
     */
    private ?int $id = null;

    /**
     * @ORM\ManyToOne(targetEntity=MyScientificPaper::class, inversedBy="versions")
     * @ORM\JoinColumn(nullable=false, onDelete="CASCADE")
     */
    private ?MyScientificPaper $paper = null;

    /**
     * @ORM\Column(type="string", length=100)
     */
    private string $name = '';

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

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): self
    {
        $this->name = $name;
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
