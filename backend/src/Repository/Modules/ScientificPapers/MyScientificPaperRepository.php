<?php

namespace App\Repository\Modules\ScientificPapers;

use App\Entity\Modules\ScientificPapers\MyScientificPaper;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method MyScientificPaper|null find($id, $lockMode = null, $lockVersion = null)
 * @method MyScientificPaper|null findOneBy(array $criteria, array $orderBy = null)
 * @method MyScientificPaper[]    findAll()
 * @method MyScientificPaper[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class MyScientificPaperRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, MyScientificPaper::class);
    }

    /**
     * @return MyScientificPaper[]
     */
    public function getAllNotDeleted(): array
    {
        return $this->createQueryBuilder('p')
            ->where('p.deleted = :deleted')
            ->setParameter('deleted', false)
            ->orderBy('p.updatedAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * @return MyScientificPaper|null
     */
    public function findOneById(int $id): ?MyScientificPaper
    {
        return $this->createQueryBuilder('p')
            ->where('p.id = :id')
            ->andWhere('p.deleted = :deleted')
            ->setParameter('id', $id)
            ->setParameter('deleted', false)
            ->getQuery()
            ->getOneOrNullResult();
    }
}
