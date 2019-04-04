<?php

namespace App\Controller;

use App\Entity\Client;
use App\Entity\Debt;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;

class DebtController extends AbstractController
{
    /**
     * @Route("/debts", name="debt")
     */
    public function index(EntityManagerInterface $entityManager)
    {
        //Obtenemos los id de los clientes que tengan alguna deuda pendiente (administrador)
        $debtRepository = $entityManager->getRepository(Debt::class);
        $clients_debts = $debtRepository->getClientsWithDebt();

        //todo hay que filtrar los clientes segun el trabajador

        return $this->render('debt/index.html.twig', [
            'clients_debts' => $clients_debts,
        ]);
    }

    /**
     * @Route("/addproduct/{id}", name="add_product")
     */
    public function add_product(){

    }
}
