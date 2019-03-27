<?php

namespace App\Controller;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;

/**
 * Class WorkerController
 * @package App\Controller
 * @IsGranted("ROLE_ADMIN")
 */
class WorkerController extends AbstractController
{
    /**
     * @Route("/workers", name="workers")
     */
    public function index(EntityManagerInterface $entityManager)
    {
        $repository = $entityManager->getRepository(User::class);
        $workers = $repository->findAll();

        return $this->render('worker/index.html.twig', [
            'workers' => $workers,
        ]);
    }

    /**
     * @Route("/worker/{id}", name="worker_detail")
     */
    public function worker_detail($id, EntityManagerInterface $entityManager){
        $repository = $entityManager->getRepository(User::class);
        $worker = $repository->findOneBy(['id' => $id]);

        return $this->render('worker/detail.html.twig', [
            'worker' => $worker,
        ]);
    }
}
