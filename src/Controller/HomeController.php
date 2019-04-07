<?php

namespace App\Controller;

use App\Form\ChangePasswordFormType;
use App\Form\WorkerForm;
use App\Service\UploaderService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;

class HomeController extends AbstractController
{
    /**
     * @Route("/home", name="app_home")
     */
    public function index()
    {
        $user = $this->getUser();
        $commission = 3;
        return $this->render('home/index.html.twig', [
            'worker' => $user,
            'commission' => $commission,
        ]);
    }

    /**
     * @Route("/admin", name="app_admin")
     */
    public function admin()
    {
        return $this->render('home/index.html.twig', [
            'worker' => $this->getUser(),
        ]);
    }

    /**
     * @Route("/user/edit/", name="user_edit")
     * Permite la edición del usuario logueado
     */
    public function edit_user(EntityManagerInterface $entityManager, Request $request, UploaderService $uploaderService){
        $user = $this->getUser();
        $form = $this->createForm(WorkerForm::class, $user);
        $oldAvatar = $user->getAvatar();

        //Para editar no necesitamos la contraseña, eso va aparte
        $form->remove('password');

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            /** @var UploadedFile $avatar */
            $avatar = $form['avatar']->getData();
            //Si no hay cambio de imagen, nos saltamos este paso
            if($avatar != null){
                $newFilename = $uploaderService->uploadImage($avatar,"worker_avatar");
                //Si no ha habido problemas en la subida, procedemos
                if($newFilename != "0"){
                    //Guardamos el nombre en la bbdd
                    $user->setAvatar($newFilename);
                }
                //Si no, mandamos mensaje de error y lo redireccionamos
                else{
                    //Creamos mensaje para notificar error al subir la imagen
                    $this->addFlash('danger', 'Solo se permiten ficheros de imagen, inténtelo de nuevo (jpg, png, gif, jpeg)');
                    return $this->redirectToRoute('app_home');
                }
            }

            //Si no se quiere modificar la foto, dejamos la que tenia puesta anteriormente
            if (!$avatar instanceof UploadedFile) {
                $user->setAvatar($oldAvatar);
            }

            //Introducimos los datos en la bbdd
            $entityManager->persist($user);
            $entityManager->flush();
            //Creamos mensaje para notificar de que se creó bien el trabajador
            $this->addFlash('success', 'Tus datos se han modificado correctamente');

            return $this->redirectToRoute('app_home');
        }

        return $this->render('worker/editW.html.twig', [
            'workerForm' => $form->createView()
        ]);
    }

    /**
     * @Route("/user/changepass/", name="change_pass")
     * Encargada del cambio de contraseña
     */
    public function change_pass(EntityManagerInterface $entityManager, Request $request, UserPasswordEncoderInterface $passwordEncoder){
        $form = $this->createForm(ChangePasswordFormType::class);

        $form->handleRequest($request);
        if($form->isSubmitted() && $form->isValid()){
            $oldPass = $form['oldPass']->getData();
            $newPass = $form['newPass']->getData();
            //Comprobamos si la contraseña es del usuario
            if(!$passwordEncoder->isPasswordValid($this->getUser(),$oldPass)){
                //Creamos mensaje para notificar de que hubo error
                $this->addFlash('danger', 'Error en la verificación, pruebe a introducir de nuevo su contraseña');
                return $this->redirectToRoute('change_pass');
            }else{
                //Codificación de la contraseña
                $encoded = $passwordEncoder->encodePassword($this->getUser(), $newPass);
                $this->getUser()->setPassword($encoded);

                $entityManager->persist($this->getUser());
                $entityManager->flush();

                //Creamos mensaje para notificar de que el procedimiento acabó correctamente
                $this->addFlash('success', 'Contraseña modificada con éxito');
                return $this->redirectToRoute('app_home');
            }

        }

        return $this->render('home/changePass.html.twig',[
            'changePassForm' => $form->createView()
        ]);
    }
}
