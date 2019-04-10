<?php
/**
 * Created by PhpStorm.
 * User: Fran
 * Date: 09/04/2019
 * Time: 18:35
 */

namespace App\Controller;


use App\Entity\Client;
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
            $orders = $repository->findAll();
        }else{
            $orders = $repository->getMyClients($this->getUser()->getId());
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

            //Guardamos los cambios
            $entityManager->persist($order);
            $entityManager->flush();

            //Mensaje de éxito
            $this->addFlash('success', "Encargop creado correctamente");
            //Volvemos a la zona de facturación
            return $this->redirectToRoute("orders");
        }

        return $this->render('order/addOrder.html.twig',[
            'addOrderForm' => $form->createView(),
            'products' => $products,
        ]);
    }

}