<?php

namespace App\Form;

use App\Entity\Client;
use App\Entity\Orders;
use App\Repository\ClientRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class OrderFormType extends AbstractType
{
    private $clientRepository;
    public function __construct(ClientRepository $clientRepository)
    {
        $this->clientRepository = $clientRepository;
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('orderDate',DateType::class,['label' => 'Fecha de encargo','format' => 'dd/MM/yyyy','data' => new \DateTime('@'.strtotime('now'))])
            ->add('deliveryDate',DateType::class,['label' => 'Fecha de entrega','format' => 'dd/MM/yyyy','data' => new \DateTime('@'.strtotime('now'))])
            ->add('description', TextareaType::class, ['label' => 'Descripción'])
            ->add('client', EntityType::class, [
                'label'=>'Asignar cliente',
                'class' => Client::class,
                'choices'=> $this->clientRepository->findAll(),
                'choice_label' => function ($client) {
                    return $client;
                }
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => Orders::class,
        ]);
    }
}
