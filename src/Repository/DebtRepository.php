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
    //  * Nos devuelve los clientes que posean al menos un producto en deuda
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

    // /**
    //  * @return Debt[] Returns an array of Debt objects
    //  * Obtenemos el listado de los porductos que debe un cliente (TODO)
    //  */

    public function getClientBreakdown($id)
    {
        $bd = $this->createQueryBuilder('d')
            ->innerJoin('d.product','p')
            ->select('p.id, p.name, p.price, d.quantity')
            ->andWhere('d.paymentDate IS NULL')
            ->andWhere('d.client = :id')
            ->setParameter('id', $id)
            ->orderBy('d.client', 'ASC')
            ->getQuery()
            ->getResult()
        ;
        return $bd;
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
