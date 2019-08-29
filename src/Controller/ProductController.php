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
     * Index de productos
     */
    public function index(EntityManagerInterface $entityManager, Request $request)
    {
        //Cargamos el repositorio de Product
        $repository = $entityManager->getRepository(Product::class);
        //Si no existe el parámetro 'all' en la url (GET), sacamos solo los productos activos
        if($request->get('all')){
            $products = $repository->findAll();
        }else{
            $products = $repository->findBy(['active'=>1]);
        }

        return $this->render('product/index.html.twig', [
            'products' => $products,
        ]);
    }

    /**
     * @Route("/product/new", name="product_new")
     * Creación de productos
     */
    public function new_product(Request $request, EntityManagerInterface $entityManager, UploaderService $uploaderService){
        //Creamos el formulario de ProductFormType
        $form = $this->createForm(ProductFormType::class);

         //el formulario manejará los datos que le vienen del $request
        $form->handleRequest($request);
        //Si el formulario se ha enviado y es válido, accedemos
        if ($form->isSubmitted() && $form->isValid()){
            //Guardamos los datos como objeto Product
            $product = $form->getData();
            //Lo marcamos como activo
            $product->setActive(true);

            /** @var UploadedFile $image */
            $image = $form['image']->getData();

            if ($image){
                //Subimos la imagen representativa
                $newName = $uploaderService->uploadImage($image,'products');
                //Si no ha habido problemas en la subida, procedemos
                if($newName != "0"){
                    //Guardamos el nombre en la bbdd
                    $product->setImage($newName);
                }
                //Si no, mandamos mensaje de error y lo redireccionamos
                else{
                    //Creamos mensaje para notificar error al subir la imagen
                    $this->addFlash('danger', 'Solo se permiten ficheros de imagen, inténtelo de nuevo (jpg, png, gif, jpeg)');
                    return $this->redirectToRoute('products');
                }
            }

            //Guardamos en la base de datos
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
     * Edición de productos
     */
    public function edit_product(Product $product, Request $request, EntityManagerInterface $entityManager, UploaderService $uploaderService){
        //Creamos el formulario de ProductFormType
        $form= $this->createForm(ProductFormType::class,$product);
        //Cargamos la imagen que tiene asignado el producto
        $oldImage = $product->getImage();

         //el formulario manejará los datos que le vienen del $request
        $form->handleRequest($request);
        //Si el formulario se ha enviado y es válido, accedemos
        if ($form->isSubmitted() && $form->isValid()){
            //Obtenemos los datos y formamos un objeto Product
            $product = $form->getData();
            /** @var UploadedFile $image */
            $image = $form['image']->getData();

            if ($image != null){
                $newName = $uploaderService->uploadImage($image,'products');
                //Si no ha habido problemas en la subida, procedemos
                if($newName != "0"){
                    //Guardamos el nombre en la bbdd
                    $product->setImage($newName);
                }
                //Si no, mandamos mensaje de error y lo redireccionamos
                else{
                    //Creamos mensaje para notificar error al subir la imagen
                    $this->addFlash('danger', 'Solo se permiten ficheros de imagen, inténtelo de nuevo (jpg, png, gif, jpeg)');
                    return $this->redirectToRoute('products');
                }
            }
            
            //Si no se quiere modificar la foto, dejamos la que tenia puesta anteriormente
            if (!$image instanceof UploadedFile) {
                $product->setImage($oldImage);
            }

            //Guardamos los datos del producto en la base de datos
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
        //Cargamos el repositorio Product
        $repository = $entityManager->getRepository(Product::class);
        //Cargamos el producto cuyo id sea el que obtenemos por GET
        $product = $repository->findOneBy(['id' => $id]);
        //Si el producto está activo, lo desactivamos y viceversa
        if($product->getActive() == true) {
            $product->setActive(false);
            $msg = "Producto activado con éxito";
        }else {
            $product->setActive(true);
            $msg = "Producto desactivado con éxito";
        }
        //Guardamos en la base de datos
        $entityManager->persist($product);
        $entityManager->flush();

        $this->addFlash('success', $msg);
        return $this->redirectToRoute('products');
    }
}
