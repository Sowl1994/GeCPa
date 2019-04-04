<?php

namespace App\Controller;

use App\Entity\Client;
use App\Entity\Debt;
use App\Entity\Product;
use App\Form\DebtFormType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

class DebtController extends AbstractController
{
    /**
     * @Route("/debts", name="debt")
     */
    public function index(EntityManagerInterface $entityManager)
    {
        //Obtenemos los id de los clientes que tengan alguna deuda pendiente (administrador)
        $debtRepository = $entityManager->getRepository(Debt::class);
        $clients_debts = $debtRepository->getClientsWithDebt();

        //todo hay que filtrar los clientes segun el trabajador

        return $this->render('debt/index.html.twig', [
            'clients_debts' => $clients_debts,
        ]);
    }

    /**
     * @Route("/addproduct/{id}", name="add_product")
     */
    public function add_product($id, EntityManagerInterface $entityManager, Request $request){
        $debtRepository = $entityManager->getRepository(Debt::class);
        $productR = $entityManager->getRepository(Product::class);
        $clientR = $entityManager->getRepository(Client::class);

        //Solo cogemos los productos que están activos
        $products = $productR->findBy(['active' => true]);
        //Obtenemos los datos del cliente al que vamos a modificar su deuda
        $client = $clientR->findOneBy(['id' => $id]);

        $form = $this->createForm(DebtFormType::class);

        $form->handleRequest($request);
        if($form->isSubmitted() && $form->isValid()){
            $data = $request->request->get("debt_form");
            //Formateamos la fecha de forma adecuada
            $purDate = $data['purchaseDate']['year']."-".$data['purchaseDate']['month']."-".$data['purchaseDate']['day'];
            //Obtenemos las cantidades de los productos
            $quantity = $data["quantity"];

            //Recorremos los productos para meter en la deuda solo los que tengan una cantidad no nula y mayor que 0
            foreach ($quantity as $idp => $value) {
                if ($value != "" && $value > 0){
                    //Para cada producto con cantidad válida, creamos un objeto deuda
                    $debt = new Debt();
                    $debt->setIsPaid(false);
                    $debt->setPurchaseDate(new \DateTime($purDate));
                    //$debt->setPaymentDate(null);
                    $debt->setClient($client);
                    $debt->setProduct($productR->findOneBy(['id'=>$idp]));
                    $debt->setQuantity($value);

                    //Para cada producto, hacemos una inserción en la deuda
                    $entityManager->persist($debt);
                    $entityManager->flush();
                }
            }
            //Mensaje de éxito
            $this->addFlash('success', "Productos añadidos al cliente correctamente");
            //Volvemos a la zona de facturación
            return $this->redirectToRoute("debt");
        }

        return $this->render('debt/addProduct.html.twig',[
            'addPForm' => $form->createView(),
            'products' => $products,
        ]);
    }

    /**
     * @Route("/breakdown/{id}", name="breakdown")
     */
    public function see_breakdown($id, EntityManagerInterface $entityManager){
        $debtRepository = $entityManager->getRepository(Debt::class);
        $bd = $debtRepository->getClientBreakdown($id);

        //$count = $this->calculate_debt($id, $bd,new \DateTime('2019-04-02'),new \DateTime('2019-04-04'));
        $count = $this->calculate_debt($id, $bd);

        return $this->render('debt/breakdown.html.twig',[
            'products' => $bd,
            'count' => $count,
        ]);

    }

    /**
     * @param $id
     * @param $bd
     * @param null $date1
     * @param null $date2
     * @return float
     */
    public function calculate_debt($id, $bd, $date1 = null, $date2 = null ):float {
        $count = 0;
        if ($date1 != null && $date2 != null){
            foreach($bd as $product){
                if ($product['purchaseDate'] >= $date1 && $product['purchaseDate'] <= $date2){
                    $q = $product['quantity'];
                    $p = $product['price'];

                    $count += $q * $p;
                }
            }
        }else{
            foreach($bd as $product){
                $q = $product['quantity'];
                $p = $product['price'];

                $count += $q * $p;
            }
        }

        return $count;
    }
}