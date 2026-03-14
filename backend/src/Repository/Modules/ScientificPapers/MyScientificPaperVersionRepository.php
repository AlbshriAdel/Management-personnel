<?php

namespace App\Repository\Modules\ScientificPapers;

use App\Entity\Modules\ScientificPapers\MyScientificPaperVersion;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method MyScientificPaperVersion|null find($id, $lockMode = null, $lockVersion = null)
 * @method MyScientificPaperVersion|null findOneBy(array $criteria, array $orderBy = null)
 * @method MyScientificPaperVersion[]    findAll()
 * @method MyScientificPaperVersion[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class MyScientificPaperVersionRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, MyScientificPaperVersion::class);
    }

    /**
     * @return MyScientificPaperVersion[]
     */
    public function findByPaperId(int $paperId): array
    {
        return $this->createQueryBuilder('v')
            ->join('v.paper', 'p')
            ->where('p.id = :paperId')
            ->setParameter('paperId', $paperId)
            ->orderBy('v.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }
}
