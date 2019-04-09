<?php
/**
 * Created by PhpStorm.
 * User: Fran
 * Date: 09/04/2019
 * Time: 11:46
 */

namespace App\Controller;


use App\Entity\Client;
use App\Entity\OrderP;
use App\Entity\User;
use App\Form\ClientFormType;
use App\Form\OrderPFormType;
use App\Service\UploaderService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class OrderPController extends AbstractController
{
    /**
     * @Route("/orders", name="orders")
     */
    public function index(EntityManagerInterface $entityManager)
    {
        $repository = $entityManager->getRepository(OrderP::class);
        /**
         * Si somos el administrador, obtenemos todos los encargos; si somos trabajador solo obtenemos los de nuestros clientes
         */
        if ($this->getUser()->isAdmin()) {
            $orders = $repository->findAll();
        }else{
            $orders = $repository->getMyClients($this->getUser()->getId());
        }

        return $this->render('order/index.html.twig', [
            'orders' => $orders,
        ]);
    }

    /**
     * @Route("/addorder", name="add_order")
     */
    public function add_order(EntityManagerInterface $entityManager, Request $request){
        $form = $this->createForm(OrderPFormType::class);


        return $this->render('order/addOrder.html.twig',[
           'addOrderForm' => $form->createView()
        ]);
    }
}