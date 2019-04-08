<?php

namespace App\Form;

use App\Entity\Debt;
use App\Entity\Product;
use App\Repository\ProductRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class DebtFormType extends AbstractType
{

    private $productRepository;
    public function __construct(ProductRepository $productRepository)
    {
        $this->productRepository = $productRepository;
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $day = date('d');
        $month = date('m');
        $year = date('Y');
        $builder
            //->add('quantity',NumberType::class,['label' => 'Cantidad', 'mapped' => false])
            ->add('purchaseDate',DateType::class,['label' => 'Fecha de compra','format' => 'dd/MM/yyyy','data' => new \DateTime('@'.strtotime('now'))
            ])
            /*->add('product',EntityType::class, [
                'label'=>' ',
                'class' => Product::class,
                'choices'=> $this->productRepository->getActiveProducts(),
                'choice_label' => function ($prod) {
                    return $prod;
                }
            ])*/
            ->add('paymentDate',DateType::class,['label' => 'Fecha de pago','format' => 'dd/MM/yyyy','data' => new \DateTime('@'.strtotime('now'))])
            /*->add('isPaid',CheckboxType::class,['label' => 'Pagado'])*/
            /*->add('client',TextType::class,['mapped'=> false])
            ->add('product',TextType::class,['mapped'=> false])*/
        ;
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => Debt::class,
        ]);
    }
}
