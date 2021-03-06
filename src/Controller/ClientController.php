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
     * Index de clientes
     */
    public function index(EntityManagerInterface $entityManager, Request $request)
    {
        //Cargamos el repository de Client
        $repository = $entityManager->getRepository(Client::class);

        //Si somos el administrador, obtenemos todos los clientes; si somos trabajador solo obtenemos nuestros clientes
        if (in_array("ROLE_ADMIN", $this->getUser()->getRoles())) {
            //Si por GET obtenemos el parámetro all, mostramos todos los clientes, activos y desactivados. Si no, solo mostramos los clientes activos
            if($request->get('all')){
                $clients = $repository->findAll();
            }else{
                $clients = $repository->findBy(['active'=>1]);
            }
        }else{
            //Si por GET obtenemos el parámetro all, mostramos todos los clientes del trabajador, activos y desactivados. Si no, solo mostramos los clientes activos
            if($request->get('all')){
                $clients = $repository->getMyClients($this->getUser()->getId());
            }else{
                $clients = $repository->getMyActiveClients($this->getUser()->getId());
            }
        }

        return $this->render('client/index.html.twig', [
            'clients' => $clients,
        ]);
    }

    /**
     * @Route("/client/{id}", name="client_detail")
     * Página de detalles del cliente con id = {id}
     */
    public function clientDetail($id,EntityManagerInterface $entityManager){
        //Cargamos el repositorio de Client
        $repository = $entityManager->getRepository(Client::class);
        //Obtenemos los datos del cliente con id = $id
        $client = $repository->findOneBy(['id' => $id]);

        //Si somos trabajador, comprobamos si el cliente es nuestro.
        if (!$this->getUser()->isAdmin()) {
            $client = $repository->findOneBy(['id' => $id, 'user' => $this->getUser()->getId()]);
        }

        //Si el cliente no es nuestro, nos mandará de vuelta a la zona de clientes
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
        //Creamos el formulario que viene de ClientFormType
        $form = $this->createForm(ClientFormType::class);
        //Si el usuario no es un admin, quitamos la opción de asignar trabajador, ya que el cliente se asignará al propio usuario
        if (!$this->getUser()->isAdmin())  $form->remove('user');

        //el formulario manejará los datos que le vienen del $request
        $form->handleRequest($request);
        //Si el formulario se ha enviado y es válido, accedemos
        if ($form->isSubmitted() && $form->isValid()) {
            //Formamos un objeto Client con los datos del formulario y marcamos active a true
            $client = $form->getData();
            $client->setActive(true);

            //Si el usuario no es admin, le asignamos su id al cliente que está creando, asi como sacamos la ultima posicion en el orden de reparto
            if (!$this->getUser()->isAdmin()){
                $client->setUser($this->getUser());
                $getClientLastPosition = $entityManager->getRepository(Client::class)->getLastClient($this->getUser()->getId());
            }else
                $getClientLastPosition = $entityManager->getRepository(Client::class)->getLastClient($client->getUser()->getId());

            //Si no tiene clientes, le asignamos el valor 1, si no, le asignamos el valor de la ultima posición (el nuevo cliente será el último en el reparto)
            if(empty($getClientLastPosition))
                $client->setDeliveryOrder(1);
            else
                $client->setDeliveryOrder($getClientLastPosition[0]->getDeliveryOrder()+1);



            /**
             * Funcionalidad de subir imágenes de perfil
             */
            //Obtenemos los datos de la imagen
            /** @var UploadedFile $avatar */
            $avatar = $form['avatar']->getData();
            //Si el fichero existe
            if($avatar){
                //LLamamos al uploaderService para que se encargue de la subida de la imagen
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

            //Guardamos los datos en la BBDD
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

        //Si somos trabajador, comprobamos si el cliente es nuestro.
        if (!$this->getUser()->isAdmin()) {
            $cl = $repository->findOneBy(['id' => $id, 'user' => $this->getUser()->getId()]);
        }

        //Si el cliente no es nuestro, nos mandará de vuelta a la zona de clientes
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
                if($oldAvatar != null)
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
        //Obtenemos el repositorio de Client y cargamos los datos del cliente cuyo id = $id
        $repository = $entityManager->getRepository(Client::class);
        $client = $repository->findOneBy(['id' => $id]);

        //Si somos trabajador, comprobamos si el cliente es nuestro.
        if (!$this->getUser()->isAdmin()) {
            $client = $repository->findOneBy(['id' => $id, 'user' => $this->getUser()->getId()]);
        }

        //Si el cliente no es nuestro, nos mandará de vuelta a la zona de clientes
        if ($client == null){
            return new RedirectResponse("/clients");
        }

        //Si el cliente está activo, lo desactivamos y viceversa
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
        //Cargamos el repositorio de Client y obtenemos los datos del cliente cuyo id = $id
        $clientR = $entityManager->getRepository(Client::class)->findOneBy(['id'=>$id]);

        return $this->render('client/route.html.twig',[
           'client' => $clientR,
        ]);
    }

    /**
     * @Route("/myroute", name="my_route")
     * Funcion encargada de mostrar la ruta del trabajador (función exclusiva de los trabajadores)
     */
    public function get_my_route(EntityManagerInterface $entityManager){
        //Si no es admin, obtenemos los clientes activos, los cuales mostraremos en el mapa de la ruta
        if(!$this->getUser()->isAdmin()){
            $myClients = $entityManager->getRepository(Client::class)->getMyActiveClients($this->getUser()->getId());

            return $this->render('worker/myRoute.html.twig',[
                'clients' => $myClients,
            ]);
        }
    }

    /**
     * @Route("/editDO/{id}", name="edit_delivery_order")
     * Encargado de modificar el orden de reparto de los clientes
     */
    public function edit_delivery_order($id, EntityManagerInterface $entityManager, Request $request){
        $client = $entityManager->getRepository(Client::class)->findOneBy(['id'=>$id]);
        $client_DO = $entityManager->getRepository(Client::class)->getMyClients($this->getUser()->getId());
        $del_order = $request->request->get('order');

        //Si hemos introducido un numero y no hemos dejado el espacio en blanco
        if ($del_order != ""){
            //Si el numero no es igual que el numero de orden que posee el cliente actualmente
            if($del_order != $client->getDeliveryOrder()){
                //Datos del cliente en la posición a ocupar
                $position = $entityManager->getRepository(Client::class)->findOneBy(['delivery_order'=> $del_order, 'user' => $this->getUser()->getId()]);
                //Si hay algun cliente en esa posicion
                if($position != null){

                    //Numero de la posicion que queremos asignar al cliente
                    $i = intval($del_order);
                    //Posicion actual del cliente a modificar el orden
                    $end = $client->getDeliveryOrder();
                    // Posicion de destino < posicion actual
                    if(intval($del_order) < $client->getDeliveryOrder()){
                        for($i; $i <= $end; $i++){
                            $client_aux = $entityManager->getRepository(Client::class)->findOneBy(['delivery_order'=>$i, 'user' => $this->getUser()->getId()]);
                            $client->setDeliveryOrder($i);
                            $entityManager->persist($client);
                            $entityManager->flush();
                            $client = $client_aux;
                        }
                    }
                    // Posicion de destino > posicion actual
                    else{
                        for($i; $i >= $end; $i--){
                            $client_aux = $entityManager->getRepository(Client::class)->findOneBy(['delivery_order'=>$i, 'user' => $this->getUser()->getId()]);
                            $client->setDeliveryOrder($i);
                            $entityManager->persist($client);
                            $entityManager->flush();
                            $client = $client_aux;
                        }
                    }
                }
                else{
                    //Alerta
                    $this->addFlash('warning', 'Error: la posicion a intercambiar estaba fuera del rango (1-'.count($client_DO).')');
                    return $this->redirectToRoute('clients');
                }
            }
        }
        else{
            //Alerta
            $this->addFlash('warning', 'Error: debe establecer una posicion a intercambiar dentro del siguiente rango (1-'.count($client_DO).')');
            return $this->redirectToRoute('clients');
        }

        //Finalización correcta
        $this->addFlash('success', 'Orden cambiado correctamente');
        return $this->redirectToRoute('clients');
    }
}
