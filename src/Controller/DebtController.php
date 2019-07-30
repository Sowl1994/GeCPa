<?php

namespace App\Controller;

use App\Entity\Client;
use App\Entity\Debt;
use App\Entity\Product;
use App\Form\DebtFormType;
use App\Repository\ClientRepository;
use App\Repository\DebtRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class DebtController extends AbstractController
{
    /**
     * @Route("/debts", name="debt")
     */
    public function index(EntityManagerInterface $entityManager)
    {
        //El admin verá todos los clientes que tengan alguna deuda, mientras que los trabajadores solo verán a susu clientes asignados
        if($this->getUser()->isAdmin()){
            $debtRepository = $entityManager->getRepository(Debt::class);
            $clients_debts = $debtRepository->getClientsWithDebt();
            $firstClient = "";
        }else{
            $debtRepository = $entityManager->getRepository(Client::class);
            $clients_debts = $debtRepository->getMyClients($this->getUser()->getId());
            $firstClient = $entityManager->getRepository(Client::class)->getMyClients($this->getUser()->getId());
            //dd($firstClient[0]);
        }

        return $this->render('debt/index.html.twig', [
            'clients_debts' => $clients_debts,
            //'first_client' => $firstClient,
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
        //El administrador podrá obtener los datos de cualquier cliente, mientras que un trabajador solo podrá obtenerlo de sus propios clientes
        //Además, los trabajadores tendrán la posibilidad de cambiar de cliente rápidamente para continuar con la facturación
        if ($this->getUser()->isAdmin()){
            $client = $clientR->findOneBy(['id' => $id]);
            $nextClient = "";
            $prevClient = "";
        }else{
            $client = $clientR->findOneBy(['id' => $id, 'user' => $this->getUser()->getId()]);
            $nextClient = $clientR->getNextClient($this->getUser()->getId(), $client->getDeliveryOrder());
            $prevClient = $clientR->getPrevClient($this->getUser()->getId(), $client->getDeliveryOrder());
        }

        /**
         * Si el cliente no es nuestro, nos mandará de vuelta a la zona de facturación
         */
        if ($client == null){
            return $this->redirectToRoute('debt');
        }

        $form = $this->createForm(DebtFormType::class);
        $form->remove('paymentDate');
        $form->handleRequest($request);
        if($form->isSubmitted() && $form->isValid()){
            $data = $request->request->get("debt_form");
            $next_submit = $request->request->get('nextSubmit');

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
            if($next_submit == true){
                //Pasamos al siguiente cliente
                return $this->redirectToRoute("add_product", ['id' => $nextClient[0]->getId()]);
            }else{
                //Volvemos a la zona de facturación
                return $this->redirectToRoute("debt");
            }

        }

        return $this->render('debt/addProduct.html.twig',[
            'addPForm' => $form->createView(),
            'products' => $products,
            'client' => $client,
            'next_client' => $nextClient,
            'prev_client' => $prevClient,
        ]);
    }

    /**
     * @Route("/breakdown/{id}", name="breakdown")
     */
    public function see_breakdown($id, EntityManagerInterface $entityManager){
        $debtRepository = $entityManager->getRepository(Debt::class);
        $clientR = $entityManager->getRepository(Client::class);

        //El administrador podrá obtener los datos de cualquier cliente, mientras que un trabajador solo podrá obtenerlo de sus propios clientes
        if ($this->getUser()->isAdmin()){
            $client = $clientR->findOneBy(['id' => $id]);
        }else{
            $client = $clientR->findOneBy(['id' => $id, 'user' => $this->getUser()->getId()]);
        }

        /**
         * Si el cliente no es nuestro, nos mandará de vuelta a la zona de facturación
         */
        if ($client == null){
            return $this->redirectToRoute('debt');
        }else{
            $bd = $debtRepository->getClientCompleteDebt($id);
            //$bd = $this->breakdown_api($id, $debtRepository, $clientR);
            //$bd = json_decode($bd->getContent(),true);
            //$count = $this->calculate_debt($bd,new \DateTime('2019-04-01'),new \DateTime('2019-04-07'));
            //$count = $this->calculate_debt($bd);

        }


        return $this->render('debt/breakdown.html.twig',[
            'products' => $bd,
            //'count' => $count,
        ]);

    }

    /**
     * @Route("/collectdebt/{id}", name="collect_debt")
     */
    public function collect_debt($id, EntityManagerInterface $entityManager, Request $request){
        $clientR = $entityManager->getRepository(Client::class);
        $debtRepository = $entityManager->getRepository(Debt::class);

        //El administrador podrá obtener los datos de cualquier cliente, mientras que un trabajador solo podrá obtenerlo de sus propios clientes
        if ($this->getUser()->isAdmin()){
            $client = $clientR->findOneBy(['id' => $id]);
        }else{
            $client = $clientR->findOneBy(['id' => $id, 'user' => $this->getUser()->getId()]);
        }

        /**
         * Si el cliente no es nuestro, nos mandará de vuelta a la zona de facturación
         */
        if ($client == null){
            return $this->redirectToRoute('debt');
        }

        //Cargamos todas las deudas que tenga el cliente y calculamos el coste total
        $bd = $debtRepository->getClientBreakdown($client->getId());
        $count = $this->calculate_debt($bd);

        $form = $this->createForm(DebtFormType::class);
        $form->remove('purchaseDate');

        $form->handleRequest($request);
        if($form->isSubmitted() && $form->isValid()){
            //Obtenemos los datos del formulario que no están mapeados o registrados en DebtFormType
            $data = $request->request->get("debt_form");

            //Formateamos la fecha de forma adecuada
            $payDate = $data['paymentDate']['year']."-".$data['paymentDate']['month']."-".$data['paymentDate']['day'];

            //Si hay cambio de fechas, cargamos las deudas del intervalo fijado
            if($data['d1'] != "" && $data['d2'] != ""){
                $bd = $debtRepository->getClientBreakdown($client->getId(),$data['d1'], $data['d2']);
            }

            //Cobramos las deudas que tengamos cargadas
            foreach($bd as $debt){
                $debt->setPaymentDate(new \DateTime($payDate));
                $debt->setIsPaid(true);

                $entityManager->persist($debt);
                $entityManager->flush();
            }

            //Mensaje de éxito
            $this->addFlash('success', "Deuda marcada como pagada");
            //Volvemos a la zona de facturación
            return $this->redirectToRoute("debt");
        }

        return $this->render('debt/collectDebt.html.twig',[
            'cDebtForm' => $form->createView(),
            'products' => $bd,
            'client' => $client,
            'count' => $count
        ]);
    }

    /**
     * @param $bd
     * @return float
     */
    public function calculate_debt($bd):float {
        $count = 0;

        foreach($bd as $product){
            $q = $product->getQuantity();
            $p = $product->getProduct()->getPrice();

            $count += $q * $p;
        }
        return $count;
    }

    /**
     * @Route("/bdapi/{id}/{date1}/{date2}", methods="GET", name="breakdown_api")
     */
    public function breakdown_api($id, DebtRepository $debtRepository, ClientRepository $clientR, $date1 = null, $date2 = null){

        //El administrador podrá obtener los datos de cualquier cliente, mientras que un trabajador solo podrá obtenerlo de sus propios clientes
        if ($this->getUser()->isAdmin()){
            $client = $clientR->findOneBy(['id' => $id]);
        }else{
            $client = $clientR->findOneBy(['id' => $id, 'user' => $this->getUser()->getId()]);
        }

        /**
         * Si el cliente no es nuestro, nos mandará de vuelta a la zona de facturación
         */
        if ($client == null){
            return $this->redirectToRoute('debt');
        }else{
            //Si no tenemos fechas, devolvemos el desglose completo
            $bd = $debtRepository->getClientBreakdown($id);
            $bd = array("debts" => $bd, "count" => $this->calculate_debt($bd));

            //Si hay fechas limite, filtramos los pedidos del desglose en función a esas fechas y recalculamos el importe de la deuda
            if($date1 != null && $date2 != null){
                $bd = $debtRepository->getClientBreakdown($id,$date1,$date2);
                $bd = array("debts" => $bd, "count" => $this->calculate_debt($bd));
            }
            return $this->json($bd, 200, ['application/json'], ['groups'=>['bdapi']]);
        }
    }
}
