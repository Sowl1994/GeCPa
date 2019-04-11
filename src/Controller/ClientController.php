<?php

namespace App\Controller;

use App\Entity\Client;
use App\Entity\User;
use App\Form\ClientFormType;
use App\Service\UploaderService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
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
        if (!$this->getUser()->isAdmin()) {
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

    /**
     * @Route("/clients/new", name="client_new")
     * Encargada de la creación de clientes
     */
    public function new_client(Request $request, EntityManagerInterface $entityManager, UploaderService $uploaderService){
        $form = $this->createForm(ClientFormType::class);
        //Si el usuario no es un admin, quitamos la opción de asignar trabajador, ya que el cliente se asignará a él mismo
        if (!$this->getUser()->isAdmin())  $form->remove('user');

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $client = $form->getData();
            $client->setActive(true);

            //Si el usuario no es admin, le asignamos su id al cliente que está creando
            if (!$this->getUser()->isAdmin())  $client->setUser($this->getUser());
            
            /**
             * Funcionalidad de subir imágenes de perfil
             */
            //Obtenemos los datos del fichero
            /** @var UploadedFile $avatar */
            $avatar = $form['avatar']->getData();
            //Elegimos la carpeta de destino y le modificamos el nombre con un id unico
            /*$destiny = $this->getParameter('kernel.project_dir').'/public/uploads';
            $originalFilename = pathinfo($avatar->getClientOriginalName(), PATHINFO_FILENAME);
            $newFilename = $originalFilename.'-'.uniqid().'.'.$avatar->guessExtension();
            //Movemos la imagen al directorio especificado
            $avatar->move($destiny, $newFilename);*/
            if($avatar){
                $newFilename = $uploaderService->uploadImage($avatar,"client_avatar");
                //Si no ha habido problemas en la subida, procedemos
                if($newFilename != "0"){
                    //Guardamos el nombre en la bbdd
                    $client->setAvatar($newFilename);
                }
                //Si no, mandamos mensaje de error y lo redireccionamos
                else{
                    //Creamos mensaje para notificar error al subir la imagen
                    $this->addFlash('danger', 'Solo se permiten ficheros de imagen, inténtelo de nuevo (jpg, png, gif, jpeg)');
                    return $this->redirectToRoute('clients');
                }
            }

            $entityManager->persist($client);
            $entityManager->flush();
            //Creamos mensaje para notificar de que se creó bien el trabajador
            $this->addFlash('success', 'Cliente creado con éxito');

            return $this->redirectToRoute('clients');
        }

        return $this->render("client/createC.html.twig",[
            'clientForm' => $form->createView()
        ]);
    }

    /**
     * @Route("/client/edit/{id}", name="client_edit")
     * Encargada de la edición de clientes
     */
    public function edit_client($id, Client $client, EntityManagerInterface $entityManager, Request $request, UploaderService $uploaderService){
        //Primero comprobamos si el cliente lo tenemos asignado
        $repository = $entityManager->getRepository(Client::class);
        $cl = $repository->findOneBy(['id' => $id]);
        /**
         * Si somos trabajador, comprobamos si el cliente es nuestro.
         */
        if (!$this->getUser()->isAdmin()) {
            $cl = $repository->findOneBy(['id' => $id, 'user' => $this->getUser()->getId()]);
        }

        /**
         * Si el cliente no es nuestro, nos mandará de vuelta a la zona de clientes
         */
        if ($cl == null){
            return new RedirectResponse("/clients");
        }

        $form = $this->createForm(ClientFormType::class,$client);
        //Guardamos el antiguo avatar
        $oldAvatar = $client->getAvatar();

        //Si el usuario no es un admin, quitamos la opción de asignar trabajador, ya que el cliente se asignará a él mismo
        if (!$this->getUser()->isAdmin())  $form->remove('user');

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            /** @var UploadedFile $avatar */
            $avatar = $form['avatar']->getData();
            //Si no hay cambio de imagen, nos saltamos este paso
            if($avatar != null){
                $newFilename = $uploaderService->uploadImage($avatar,"client_avatar");
                //Si no ha habido problemas en la subida, procedemos
                if($newFilename != "0"){
                    //Guardamos el nombre en la bbdd
                    $client->setAvatar($newFilename);
                }
                //Si no, mandamos mensaje de error y lo redireccionamos
                else{
                    //Creamos mensaje para notificar error al subir la imagen
                    $this->addFlash('danger', 'Solo se permiten ficheros de imagen, inténtelo de nuevo (jpg, png, gif, jpeg)');
                    return $this->redirectToRoute('clients');
                }

            }

            //Si no se quiere modificar la foto, dejamos la que tenia puesta anteriormente
            if (!$avatar instanceof UploadedFile) {
                $client->setAvatar($oldAvatar);
            }

            //Introducimos los datos en la bbdd
            $entityManager->persist($client);
            $entityManager->flush();
            //Creamos mensaje para notificar de que se creó bien el trabajador
            $this->addFlash('success', 'Cliente editado con éxito');

            return $this->redirectToRoute('clients');
        }

        return $this->render("client/editC.html.twig",[
            'clientForm' => $form->createView(),
            'client' => $client
        ]);
    }

    /**
     * @Route("/client/activate/{id}", name="client_activate")
     * Funcion encargada de activar/desactivar clientes
     */
    public function activate_client($id,EntityManagerInterface $entityManager){
        $repository = $entityManager->getRepository(Client::class);
        $client = $repository->findOneBy(['id' => $id]);

        /**
         * Si somos trabajador, comprobamos si el cliente es nuestro.
         */
        if (!$this->getUser()->isAdmin()) {
            $client = $repository->findOneBy(['id' => $id, 'user' => $this->getUser()->getId()]);
        }

        /**
         * Si el cliente no es nuestro, nos mandará de vuelta a la zona de clientes
         */
        if ($client == null){
            return new RedirectResponse("/clients");
        }

        if ($client->getActive() == true){
            $client->setActive(false);
            $msg = "Cliente desactivado con éxito";
        }else{
            $client->setActive(true);
            $msg = "Cliente activado con éxito";
        }
        //Pasamos los cambios a la bbdd
        $entityManager->persist($client);
        $entityManager->flush();

        //Creamos mensaje para notificar de que se editó bien el cliente
        $this->addFlash('success', $msg);

        return $this->redirectToRoute('client_detail',['id' => $id]);
    }

    /**
     * @Route("/client/route/{id}", name="client_route")
     * Funcion encargada de mostrar la ruta hacia un cliente
     */
    public function route_client($id,EntityManagerInterface $entityManager){
        $clientR = $entityManager->getRepository(Client::class)->findOneBy(['id'=>$id]);

        return $this->render('client/route.html.twig',[
           'client' => $clientR,
        ]);
    }
}
