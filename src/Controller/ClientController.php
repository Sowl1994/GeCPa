<?php

namespace App\Controller;

use App\Entity\Client;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;

class ClientController extends AbstractController
{
    /**
     * @Route("/client", name="client")
     */
    public function index(EntityManagerInterface $entityManager)
    {
        $repository = $entityManager->getRepository(Client::class);
        /**
         * Si somos el administrador, obtenemos todos los clientes; si somos trabajador solo obtenemos nuestros clientes
         */
        if (in_array("ROLE_ADMIN", $this->getUser()->getRoles())) {
            $clients = $repository->findAll();
        }else{
            $clients = $repository->findBy(['user' => $this->getUser()->getId()]);
        }


        return $this->render('client/index.html.twig', [
            'clients' => $clients,
        ]);
    }

    public function getClients($user){

    }
}
