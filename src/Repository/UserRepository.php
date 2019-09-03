<?php

namespace App\Repository;

use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Symfony\Bridge\Doctrine\RegistryInterface;

/**
 * @method User|null find($id, $lockMode = null, $lockVersion = null)
 * @method User|null findOneBy(array $criteria, array $orderBy = null)
 * @method User[]    findAll()
 * @method User[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class UserRepository extends ServiceEntityRepository
{
    public function __construct(RegistryInterface $registry)
    {
        parent::__construct($registry, User::class);
    }

    // /**
    //  * @return User[] Devuelve un array de Usuarios/Trabajadores
    //  * Devuelve los usuarios que no son administrador (trabajadores)
    //  */

    public function getWorkers()
    {
        return $this->createQueryBuilder('u')
            ->andWhere('u.roles NOT LIKE :rol')
            ->setParameter('rol', '%ROLE_ADMIN%')
            ->orderBy('u.id', 'ASC')
            ->getQuery()
            ->getResult()
        ;
    }

    // /**
    //  * @return User[] Devuelve un array de Usuarios/Trabajadores
    //  * Devuelve los trabajadores disponibles para ser asignados (no pueden estar desactivados)
    //  */

    public function getAvailableWorkers()
    {
        return $this->createQueryBuilder('u')
            ->andWhere('u.roles NOT LIKE :rol')
            ->andWhere('u.active = 1')
            ->setParameter('rol', '%ROLE_ADMIN%')
            ->orderBy('u.id', 'ASC')
            ->getQuery()
            ->getResult()
            ;
    }
}
