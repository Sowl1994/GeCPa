<?php

namespace App\Controller;

use App\Entity\Product;
use App\Form\ProductFormType;
use App\Service\UploaderService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

class ProductController extends AbstractController
{
    /**
     * @Route("/products", name="products")
     */
    public function index(EntityManagerInterface $entityManager)
    {
        $repository = $entityManager->getRepository(Product::class);
        $products = $repository->findAll();


        return $this->render('product/index.html.twig', [
            'products' => $products,
        ]);
    }

    /**
     * @Route("/product/new", name="product_new")
     */
    public function new_product(Request $request, EntityManagerInterface $entityManager, UploaderService $uploaderService){
        $form = $this->createForm(ProductFormType::class);

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()){
            $product = $form->getData();
            $product->setActive(true);

            /** @var UploadedFile $image */
            $image = $form['image']->getData();

            if ($image){
                $newName = $uploaderService->uploadImage($image,'products');
                $product->setImage($newName);
            }

            $entityManager->persist($product);
            $entityManager->flush();

            //Creamos mensaje para notificar de que se creó bien el producto
            $this->addFlash('success', 'Producto creado con éxito');

            return $this->redirectToRoute('products');
        }

        return $this->render("product/createP.html.twig",[
            'productForm' => $form->createView()
        ]);
    }
}
