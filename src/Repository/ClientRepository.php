<?php

namespace App\Repository;

use App\Entity\Client;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Symfony\Bridge\Doctrine\RegistryInterface;

/**
 * @method Client|null find($id, $lockMode = null, $lockVersion = null)
 * @method Client|null findOneBy(array $criteria, array $orderBy = null)
 * @method Client[]    findAll()
 * @method Client[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class ClientRepository extends ServiceEntityRepository
{
    public function __construct(RegistryInterface $registry)
    {
        parent::__construct($registry, Client::class);
    }

    // /**
    //  * @return Client[] Returns an array of Client objects
    //  * Devuelve todos los clientes de un trabajador
    //  */

    public function getMyClients($idWorker)
    {
        return $this->createQueryBuilder('c')
            ->andWhere('c.user = :val')
            ->setParameter('val', $idWorker)
            ->orderBy('c.delivery_order', 'ASC')
            ->getQuery()
            ->getResult()
        ;
    }

    public function getNextClient($idWorker, $pos){
        return $this->createQueryBuilder('c')
            ->andWhere('c.user = :val')
            ->andWhere('c.delivery_order > :pos')
            ->setParameter('val', $idWorker)
            ->setParameter('pos', $pos)
            ->orderBy('c.delivery_order', 'ASC')
            ->setMaxResults(1)
            ->getQuery()
            ->getResult()
            ;
    }

    public function getPrevClient($idWorker, $pos){
        return $this->createQueryBuilder('c')
            ->andWhere('c.user = :val')
            ->andWhere('c.delivery_order < :pos')
            ->setParameter('val', $idWorker)
            ->setParameter('pos', $pos)
            ->orderBy('c.delivery_order', 'DESC')
            ->setMaxResults(1)
            ->getQuery()
            ->getResult()
            ;
    }


    /*
    public function findOneBySomeField($value): ?Client
    {
        return $this->createQueryBuilder('c')
            ->andWhere('c.exampleField = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
    */
}
