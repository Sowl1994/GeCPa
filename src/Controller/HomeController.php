<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;

class HomeController extends AbstractController
{
    /**
     * @Route("/home", name="app_home")
     */
    public function index()
    {
        $name = $this->getUser()->getFirstName();
        $commission = 3;
        return $this->render('home/index.html.twig', [
            'worker_name' => $name,
            'commission' => $commission,
        ]);
    }

    /**
     * @Route("/admin", name="app_admin")
     */
    public function admin()
    {
        return $this->render('home/index.html.twig', [
            'worker_name' => 'Administrador',
        ]);
    }
}
