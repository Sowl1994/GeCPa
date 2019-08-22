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

     /**
      * @return Client[] Devuelve un array de objetos Client
      * Devuelve todos los clientes de un trabajador, ordenados por orden de reparto
      */
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

    /**
     * @return Client[] Devuelve un array de objetos Client
     * Devuelve todos los clientes activos de un trabajador, ordenados por orden de reparto
     */
    public function getMyActiveClients($idWorker)
    {
        return $this->createQueryBuilder('c')
            ->andWhere('c.active = 1')
            ->andWhere('c.user = :val')
            ->setParameter('val', $idWorker)
            ->orderBy('c.delivery_order', 'ASC')
            ->getQuery()
            ->getResult()
            ;
    }

    /**
     * @return Client Devuelve un objeto Client
     * Devuelve el cliente siguiente a la posición $pos según el orden de reparto
     */
    public function getNextClient($idWorker, $pos){
        return $this->createQueryBuilder('c')
            ->andWhere('c.user = :val')
            ->andWhere('c.delivery_order > :pos')
            ->andWhere('c.active = 1')
            ->setParameter('val', $idWorker)
            ->setParameter('pos', $pos)
            ->orderBy('c.delivery_order', 'ASC')
            ->setMaxResults(1)
            ->getQuery()
            ->getResult()
            ;
    }

    /**
     * @return Client Devuelve un objeto Client
     * Devuelve el cliente anterior a la posición $pos según el orden de reparto
     */
    public function getPrevClient($idWorker, $pos){
        return $this->createQueryBuilder('c')
            ->andWhere('c.user = :val')
            ->andWhere('c.delivery_order < :pos')
            ->andWhere('c.active = 1')
            ->setParameter('val', $idWorker)
            ->setParameter('pos', $pos)
            ->orderBy('c.delivery_order', 'DESC')
            ->setMaxResults(1)
            ->getQuery()
            ->getResult()
            ;
    }

    /**
     * @return Client Devuelve un objeto Client
     * Devuelve el último cliente según el orden de reparto
     */
    public function getLastClient($idWorker){
        return $this->createQueryBuilder('c')
            ->andWhere('c.user = :val')
            ->andWhere('c.active = 1')
            ->setParameter('val', $idWorker)
            ->orderBy('c.delivery_order', 'DESC')
            ->setMaxResults(1)
            ->getQuery()
            ->getResult()
            ;
    }
}
