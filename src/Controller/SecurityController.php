<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;

class SecurityController extends AbstractController
{

    /**
     * @Route("/login", name="app_login")
     * Encargado de iniciar la sesión
     */
    public function login(AuthenticationUtils $authenticationUtils): Response
    {
        // Obtiene el error de login, si hay
        $error = $authenticationUtils->getLastAuthenticationError();
        // Último nombre de usuario
        $lastUsername = $authenticationUtils->getLastUsername();

        return $this->render('security/login.html.twig', ['last_username' => $lastUsername, 'error' => $error]);
    }

    /**
     * @Route("/logout", name="app_logout")
     * Encargado de cerrar la sesión
     */
    public function logout(){
        throw new \Exception('Will be intercepted before getting here');
    }
}
