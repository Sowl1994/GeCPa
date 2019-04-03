<?php

namespace App\Controller;

use App\Entity\User;
use App\Form\WorkerForm;
use App\Service\UploaderService;
use Doctrine\ORM\EntityManagerInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\UploadedFile;
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
     * Muestra una lista de los trabajadores
     */
    public function index(EntityManagerInterface $entityManager)
    {
        $repository = $entityManager->getRepository(User::class);
        //Cogemos los usuarios que no son administrador
        $workers = $repository->getWorkers();

        return $this->render('worker/index.html.twig', [
            'workers' => $workers,
        ]);
    }

    /**
     * @Route("/worker/{id}", name="worker_detail")
     * Muestra en detalle al trabajador por el id pasado por parametro
     */
    public function worker_detail($id, EntityManagerInterface $entityManager){
        $repository = $entityManager->getRepository(User::class);
        $worker = $repository->findOneBy(['id' => $id]);

        /**
         * Si no existe el trabajador o es otro admin, nos mandará de vuelta a la zona de trabajadores
         */
        if ($worker == null || in_array('ROLE_ADMIN',$worker->getRoles())){
            return new RedirectResponse("/workers");
        }

        return $this->render('worker/detail.html.twig', [
            'id' => $id,
            'worker' => $worker,
        ]);
    }

    /**
     * @Route("/workers/new", name="worker_new")
     * Encargada de la creación de trabajadores
     */
    public function new_worker(Request $request, UserPasswordEncoderInterface $encoder, EntityManagerInterface $entityManager, UploaderService $uploaderService){

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

            /** @var UploadedFile $avatar */
            $avatar = $form['avatar']->getData();
            if ($avatar){
                $newFileName = $uploaderService->uploadImage($avatar,"worker_avatar");
                $worker->setAvatar($newFileName);
            }

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

    /**
     * @Route("/worker/edit/{id}", name="worker_edit")
     * Edita a los trabajadores
     */
    public function edit_worker(User $user, EntityManagerInterface $entityManager, Request $request){
        $form = $this->createForm(WorkerForm::class,$user);

        //Para editar no necesitamos la contraseña ni el avatar, eso va aparte
        $form->remove('password');
        $form->remove('avatar');

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {

            //Introducimos los datos en la bbdd
            $entityManager->persist($user);
            $entityManager->flush();
            //Creamos mensaje para notificar de que se creó bien el trabajador
            $this->addFlash('success', 'Trabajador editado con éxito');

            return $this->redirectToRoute('workers');
        }

        return $this->render('worker/editW.html.twig', [
            'workerForm' => $form->createView()
        ]);
    }

    /**
     * @Route("/worker/activate/{id}", name="worker_activate")
     * Funcion encargada de activar/desactivar trabajadores
     */
    public function activate_worker($id,EntityManagerInterface $entityManager){
        $repository = $entityManager->getRepository(User::class);
        $worker = $repository->findOneBy(['id' => $id]);
        if ($worker->getActive() == true){
            $worker->setActive(false);
        }else{
            $worker->setActive(true);
        }
        //Pasamos los cambios a la bbdd
        $entityManager->persist($worker);
        $entityManager->flush();

        return new RedirectResponse("/workers");
    }
}
