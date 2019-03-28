<?php

namespace App\Controller;

use App\Entity\User;
use App\Form\WorkerForm;
use Doctrine\ORM\EntityManagerInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;

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

        /**
         * Si no existe el trabajador, nos mandará de vuelta a la zona de trabajadores
         */
        if ($worker == null){
            return new RedirectResponse("/workers");
        }

        return $this->render('worker/detail.html.twig', [
            'worker' => $worker,
        ]);
    }

    /**
     * @Route("/workers/new", name="worker_new")
     */
    public function new_worker(Request $request, UserPasswordEncoderInterface $encoder, EntityManagerInterface $entityManager){

        $form = $this->createForm(WorkerForm::class);

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            //Obtenemos los datos del formulario
            $data = $form->getData();
            //Los datos vienen como objeto de la clase, asi que vamos añadiendo los campos extra que queramos
            $worker = $data;
            //Codificación de la contraseña
            $encoded = $encoder->encodePassword($worker, $worker->getPassword());
            $worker->setPassword($encoded);
            $worker->setActive(1);
            $worker->setRoles(["ROLE_USER"]);

            //Introducimos los datos en la bbdd
            $entityManager->persist($worker);
            $entityManager->flush();
            //Creamos mensaje para notificar de que se creó bien el trabajador
            $this->addFlash('success', 'Trabajador creado con éxito');

            return $this->redirectToRoute('app_home');
        }

        return $this->render('worker/createW.html.twig', [
            'workerForm' => $form->createView()
        ]);
    }
}
