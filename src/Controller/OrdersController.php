<?php
/**
 * Created by PhpStorm.
 * User: Fran
 * Date: 09/04/2019
 * Time: 18:35
 */

namespace App\Controller;


use App\Entity\Client;
use App\Entity\Debt;
use App\Entity\OrderProduct;
use App\Entity\Orders;
use App\Entity\Product;
use App\Entity\User;
use App\Form\ClientFormType;
use App\Form\OrderFormType;
use App\Form\OrderPFormType;
use App\Service\UploaderService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class OrdersController extends AbstractController
{
    /**
     * @Route("/orders", name="orders")
     */
    public function index(EntityManagerInterface $entityManager)
    {
        $repository = $entityManager->getRepository(Orders::class);
        /**
         * Si somos el administrador, obtenemos todos los encargos; si somos trabajador solo obtenemos los de nuestros clientes
         */
        if ($this->getUser()->isAdmin()) {
            $orders = $repository->getClientsWithActiveOrder();
        }else{
            $orders = $repository->getMyClientsWithActiveOrder($this->getUser()->getId());
        }

        return $this->render('order/index.html.twig', [
            'orders' => $orders,
        ]);
    }

    /**
     * @Route("/order/{id}", name="order_details")
     */
    public function order_details($id, EntityManagerInterface $entityManager){
        $orderR = $entityManager->getRepository(Orders::class)->findOneBy(['id'=>$id]);
        $count = 0;
        foreach ($orderR->getOrderProducts() as $product)
            $count += $product->getQuantity() * $product->getProduct()->getPrice();

        return $this->render('order/details.html.twig',[
            'order' => $orderR,
            'count' => $count,
        ]);
    }

    /**
     * @Route("/addorder", name="add_order")
     */
    public function add_order(EntityManagerInterface $entityManager, Request $request){
        $productR = $entityManager->getRepository(Product::class);
        $clientR = $entityManager->getRepository(Client::class);
        $myClients = $clientR->findAll();

        if (!$this->getUser()->isAdmin()){
            $myClients = $clientR->findBy(['user' => $this->getUser()->getId()]);
        }

        //Solo cogemos los productos que están activos
        $products = $productR->findBy(['active' => true]);

        $form = $this->createForm(OrderFormType::class);
        $form->handleRequest($request);
        if($form->isSubmitted() && $form->isValid()){
            $data = $request->request->get('order_form');

            //Formateamos la fecha de forma adecuada
            $orderDate = $data['orderDate']['year']."-".$data['orderDate']['month']."-".$data['orderDate']['day'];
            $deliveryDate = $data['deliveryDate']['year']."-".$data['deliveryDate']['month']."-".$data['deliveryDate']['day'];

            //Obtenemos las cantidades de los productos
            $quantity = $data["quantity"];

            //Creamos un objeto Orders que subiremos a la bbdd
            $order = new Orders();
            $order->setDescription($data['description']);
            $order->setOrderDate(new \DateTime($orderDate));
            $order->setDeliveryDate(new \DateTime($deliveryDate));
            $order->setClient($clientR->findOneBy(['id'=>$data['client']]));
            $order->setIsFinish(false);

            foreach ($quantity as $idp => $value) {
                if($value > 0){
                    $orderProducts = new OrderProduct();
                    $orderProducts->setOrders($order);
                    $orderProducts->setProduct($productR->findOneBy(['id'=>$idp]));
                    $orderProducts->setQuantity($value);
                    $order->addOrderProduct($orderProducts);

                    //Cada vez que creamos un objeto OrderProduct, usamos persist para que se guarden los cambios
                    $entityManager->persist($orderProducts);
                }
            }
            //Si el encargo tiene algún producto, lo guardamos
            if($order->getOrderProducts()->count() > 0){
                //Guardamos los cambios
                $entityManager->persist($order);
                $entityManager->flush();
            }

            //Mensaje de éxito
            $this->addFlash('success', "Encargo creado correctamente");
            //Volvemos a la zona de facturación
            return $this->redirectToRoute("orders");
        }

        return $this->render('order/addOrder.html.twig',[
            'addOrderForm' => $form->createView(),
            'products' => $products,
            'clients' => $myClients,
        ]);
    }

    /**
     * @Route("/finishorder/{id}", name="finish_order")
     */
    public function finish_order($id, EntityManagerInterface $entityManager){
        $order = $entityManager->getRepository(Orders::class)->findOneBy(['id' => $id]);

        // Si no es admin, se comprueba que el encargo a finalizar pertenezca a un cliente cuyo trabajador
        // sea el usuario que intenta finalizar el encargo
        if (!$this->getUser()->isAdmin()){
            if($order->getClient()->getUser()->getId() != $this->getUser()->getId()){
                $this->addFlash('danger','Error en la finalización del encargo');
                return $this->redirectToRoute("orders");
            }
        }

        $order->setIsFinish(true);

        $entityManager->persist($order); $entityManager->flush();
        $this->addFlash('success','Encargo marcado como finalizado correctamente');
        return $this->redirectToRoute("orders");
    }

    /**
     * @Route("/ordertodebt/{id}", name="order_debt")
     */
    public function add_order_debt($id, EntityManagerInterface $entityManager){
        $orderR = $entityManager->getRepository(Orders::class)->findOneBy(['id'=>$id]);
        $products = $orderR->getOrderProducts();

        //Marcamos el encargo como finalizado
        $orderR->setIsFinish(true);
        $entityManager->persist($orderR);

        //Cada producto lo vamos metiendo en la deuda
        foreach($products as $idp => $value){
            $debt = new Debt();
            $debt->setClient($orderR->getClient());
            $debt->setIsPaid(false);
            $debt->setPurchaseDate(new \DateTime($orderR->getDeliveryDate()->format('Y-m-d')));
            $debt->setProduct($value->getProduct());
            $debt->setQuantity($value->getQuantity());

            //Para cada producto, hacemos una inserción en la deuda
            $entityManager->persist($debt);
            $entityManager->flush();
        }

        $this->addFlash('success','El encargo ha pasado a la deuda del cliente correctamente');
        return $this->redirectToRoute("orders");
    }
}