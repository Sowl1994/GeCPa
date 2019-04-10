<?php

namespace App\Repository;

use App\Entity\Orders;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Symfony\Bridge\Doctrine\RegistryInterface;

/**
 * @method Orders|null find($id, $lockMode = null, $lockVersion = null)
 * @method Orders|null findOneBy(array $criteria, array $orderBy = null)
 * @method Orders[]    findAll()
 * @method Orders[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class OrdersRepository extends ServiceEntityRepository
{
    public function __construct(RegistryInterface $registry)
    {
        parent::__construct($registry, Orders::class);
    }

    public function getClientsWithActiveOrder(){
        return $this->createQueryBuilder('o')
            ->andWhere('o.isFinish = :var')
            ->setParameter('var',false)
            ->orderBy('o.deliveryDate', 'ASC')
            ->getQuery()
            ->getResult()
            ;
    }

    // /**
    //  * @return Orders[] Returns an array of Orders objects
    //  */
    public function getMyClientsWithActiveOrder($idWorker)
    {
        return $this->createQueryBuilder('o')
            ->innerJoin('o.client','c')
            ->innerJoin('c.user', 'u')
            ->select('o, c')
            ->andWhere('u.id = :id')
            ->andWhere('o.isFinish = :var')
            ->setParameter('var',false)
            ->setParameter('id', $idWorker)
            ->orderBy('o.deliveryDate', 'ASC')
            ->getQuery()
            ->getResult()
            ;
    }

    public function getOrders($date,$idWorker){
        return $this->createQueryBuilder('o')
            ->innerJoin('o.client','c')
            ->innerJoin('c.user', 'u')
            ->andWhere('u.id = :id')
            ->andWhere('o.isFinish = :var')
            ->andWhere('o.deliveryDate = :date')
            ->setParameter('var',false)
            ->setParameter('id', $idWorker)
            ->setParameter('date', $date)
            ->orderBy('o.deliveryDate', 'ASC')
            ->getQuery()
            ->getResult()
            ;
    }

    /*
    public function findOneBySomeField($value): ?Orders
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
