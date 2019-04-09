<?php

namespace App\Repository;

use App\Entity\OrderP;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Symfony\Bridge\Doctrine\RegistryInterface;

/**
 * @method OrderP|null find($id, $lockMode = null, $lockVersion = null)
 * @method OrderP|null findOneBy(array $criteria, array $orderBy = null)
 * @method OrderP[]    findAll()
 * @method OrderP[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class OrderPRepository extends ServiceEntityRepository
{
    public function __construct(RegistryInterface $registry)
    {
        parent::__construct($registry, OrderP::class);
    }

    // /**
    //  * @return OrderP[] Returns an array of OrderP objects
    //  */
    public function getMyClients($idWorker)
    {
        return $this->createQueryBuilder('o')
            ->innerJoin('o.client','c')
            ->innerJoin('c.user', 'u')
            ->select('o, c')
            ->andWhere('u.id = :id')
            ->setParameter('id', $idWorker)
            ->orderBy('o.client', 'ASC')
            ->getQuery()
            ->getResult()
        ;
    }

    /*
    public function findOneBySomeField($value): ?OrderP
    {
        return $this->createQueryBuilder('o')
            ->andWhere('o.exampleField = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
    */
}
