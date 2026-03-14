<?php

namespace App\Repository\Modules\ScientificPapers;

use App\Entity\Modules\ScientificPapers\MyScientificPaperChecklistItem;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method MyScientificPaperChecklistItem|null find($id, $lockMode = null, $lockVersion = null)
 * @method MyScientificPaperChecklistItem|null findOneBy(array $criteria, array $orderBy = null)
 * @method MyScientificPaperChecklistItem[]    findAll()
 * @method MyScientificPaperChecklistItem[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class MyScientificPaperChecklistItemRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, MyScientificPaperChecklistItem::class);
    }

    /**
     * @return MyScientificPaperChecklistItem[]
     */
    public function findByPaperId(int $paperId): array
    {
        return $this->createQueryBuilder('c')
            ->join('c.paper', 'p')
            ->where('p.id = :paperId')
            ->setParameter('paperId', $paperId)
            ->orderBy('c.sortOrder', 'ASC')
            ->addOrderBy('c.id', 'ASC')
            ->getQuery()
            ->getResult();
    }
}
