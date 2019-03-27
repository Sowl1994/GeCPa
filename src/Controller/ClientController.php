<?php

namespace App\Controller;

use App\Entity\Client;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class ClientController extends AbstractController
{
    /**
     * @Route("/clients", name="clients")
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

    /**
     * @Route("/client/{id}", name="client_detail")
     */
    public function clientDetail($id,EntityManagerInterface $entityManager){
        $repository = $entityManager->getRepository(Client::class);
        $client = $repository->findOneBy(['id' => $id]);

        /**
         * Si somos trabajador, comprobamos si el cliente es nuestro.
         */
        if (!in_array("ROLE_ADMIN", $this->getUser()->getRoles())) {
            $client = $repository->findOneBy(['id' => $id, 'user' => $this->getUser()->getId()]);
        }

        /**
         * Si el cliente no es nuestro, nos mandará de vuelta a la zona de clientes
         */
        if ($client == null){
            return new RedirectResponse("/clients");
        }


        return $this->render("client/detail.html.twig",[
           "client" => $client,
        ]);
    }
}
