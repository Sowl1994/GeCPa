<?php

namespace App\Repository;

use App\Entity\Debt;
use App\Entity\Client;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Symfony\Bridge\Doctrine\RegistryInterface;

/**
 * @method Debt|null find($id, $lockMode = null, $lockVersion = null)
 * @method Debt|null findOneBy(array $criteria, array $orderBy = null)
 * @method Debt[]    findAll()
 * @method Debt[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class DebtRepository extends ServiceEntityRepository
{
    public function __construct(RegistryInterface $registry)
    {
        parent::__construct($registry, Debt::class);
    }

    // /**
    //  * @return Debt[] Returns an array of Debt objects
    //  */

    public function getClientsWithDebt()
    {
        $clients = $this->createQueryBuilder('d')
            ->innerJoin('d.client','c')
            ->select('DISTINCT c.id, c.firstName, c.lastName, c.avatar, c.alias')
            ->andWhere('d.paymentDate IS NULL')
            ->orderBy('d.client', 'ASC')
            ->getQuery()
            ->getResult()
        ;
        return $clients;
    }


    /*
    public function findOneBySomeField($value): ?Debt
    {
        return $this->createQueryBuilder('d')
            ->andWhere('d.exampleField = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
    */
}
