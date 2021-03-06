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

    /**
     * @return Client[] Devuelve un array de objetos Client
     * Nos devuelve los clientes que posean al menos un producto en deuda
     */

    public function getClientsWithDebt()
    {
        $clients = $this->createQueryBuilder('d')
            ->innerJoin('d.client','c')
            ->select('DISTINCT c.id, c.firstName, c.lastName, c.avatar, c.alias, c.active')
            ->andWhere('d.paymentDate IS NULL')
            ->orderBy('d.client', 'ASC')
            ->getQuery()
            ->getResult()
        ;
        return $clients;
    }

    /**
     * @return Client[] Devuelve un array de objetos Client
     * Nos devuelve los clientes que están asignados al id del trabajador que pasamos por parámetro que posean al menos un producto en deuda
     */

    public function getMyClientsWithDebt($idWorker)
    {
        $clients = $this->createQueryBuilder('d')
            ->innerJoin('d.client','c')
            ->innerJoin('c.user', 'u')
            ->select('DISTINCT c.id, c.firstName, c.lastName, c.avatar, c.alias')
            ->andWhere('d.paymentDate IS NULL')
            ->andWhere('u.id = :id')
            ->setParameter('id', $idWorker)
            ->orderBy('c.delivery_order', 'ASC')
            ->getQuery()
            ->getResult()
        ;
        return $clients;
    }

    /**
     * @return Debt[] Devuelve un array de objetos Debt
     * Obtenemos el listado de las deudas que tiene un cliente. Este listado puede ser el listado total
     * o estar en un intervalo con fecha de inicio(d1) y fecha de fin(d2)
     */
    public function getClientBreakdown($id, $d1=null, $d2=null)
    {
        $bd = $this->createQueryBuilder('d')
            ->innerJoin('d.product','p')
            ->select('d, p')
            ->andWhere('d.paymentDate IS NULL')
            ->andWhere('d.client = :id')
            ->setParameter('id', $id)
            ->orderBy('d.purchaseDate', 'ASC')
            ->getQuery()
            ->getResult()
        ;

        if($d1 != null && $d2 != null){
            $bd = $this->createQueryBuilder('d')
                ->innerJoin('d.product','p')
                ->select('d, p')
                ->andWhere('d.paymentDate IS NULL')
                ->andWhere('d.client = :id')
                ->andWhere("d.purchaseDate >= '$d1'")
                ->andWhere("d.purchaseDate <= '$d2'")
                ->setParameter('id', $id)
                ->orderBy('d.purchaseDate', 'ASC')
                ->getQuery()
                ->getResult()
            ;
        }

        return $bd;
    }

     /**
      * @return Debt[] Devuelve un array de objetos Debt
      * Obtenemos el listado completo de los productos que debe un cliente
      */
    public function getClientCompleteDebt($id){
        return $this->createQueryBuilder('d')
            ->innerJoin('d.product','p')
            ->select('d, p')
            ->andWhere('d.client = :id')
            ->setParameter('id', $id)
            ->orderBy('d.purchaseDate', 'ASC')
            ->getQuery()
            ->getResult()
        ;
    }
}
