<?php

namespace App\Controller;

use App\Entity\Product;
use App\Form\ProductFormType;
use App\Service\UploaderService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\UploadedFile;
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

    /**
     * @Route("/product/edit/{id}", name="product_edit")
     */
    public function edit_product(Product $product, Request $request, EntityManagerInterface $entityManager, UploaderService $uploaderService){
        $form= $this->createForm(ProductFormType::class,$product);
        $oldImage = $product->getImage();

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()){
            $product = $form->getData();
            /** @var UploadedFile $image */
            $image = $form['image']->getData();

            if ($image != null){
                $newName = $uploaderService->uploadImage($image,'products');
                $product->setImage($newName);
            }
            
            //Si no se quiere modificar la foto, dejamos la que tenia puesta anteriormente
            if (!$image instanceof UploadedFile) {
                $product->setImage($oldImage);
            }

            $entityManager->persist($product);
            $entityManager->flush();

            //Creamos mensaje para notificar de que se creó bien el producto
            $this->addFlash('success', 'Producto creado con éxito');

            return $this->redirectToRoute('products');
        }

        return $this->render('product/editP.html.twig',[
            'productForm' => $form->createView(),

        ]);

    }
    /**
     * @Route("/product/activate/{id}", name="product_activate")
     * Funcion encargada de activar/desactivar productos
     */
    public function activate_product($id, EntityManagerInterface $entityManager){
        $repository = $entityManager->getRepository(Product::class);
        $product = $repository->findOneBy(['id' => $id]);
        if($product->getActive() == true) {
            $product->setActive(false);
            $msg = "Producto activado con éxito";
        }else {
            $product->setActive(true);
            $msg = "Producto desactivado con éxito";
        }
        $entityManager->persist($product);
        $entityManager->flush();

        $this->addFlash('success', $msg);
        return $this->redirectToRoute('products');
    }
}
