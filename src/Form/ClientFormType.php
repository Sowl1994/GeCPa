<?php

namespace App\Form;

use App\Entity\Client;
use App\Entity\User;
use App\Repository\UserRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\TelType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;


class ClientFormType extends AbstractType
{
    private $userRepository;
    public function __construct(UserRepository $userRepository)
    {
        $this->userRepository = $userRepository;
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('firstName',TextType::class,['label'=> 'Nombre'])
            ->add('lastName',TextType::class,['label'=> 'Apellidos'])
            ->add('alias',TextType::class,['label'=> 'Alias', 'required'=>false])
            ->add('address',TextType::class,['label'=> 'Dirección'])
            //->add('latitude')
            //->add('longitude')
            ->add('telephone',TelType::class,['label'=> 'Teléfono', 'required'=>false])
            ->add('sex',ChoiceType::class,['label'=> 'Sexo', 'required'=>false,
                'choices'  => [
                    'Hombre' => '1',
                    'Mujer' => '2',
                ],
            ])
            ->add('avatar', FileType::class,['label'=>'Avatar', 'required'=>false,'data_class'=>null])
            ->add('email',TextType::class,['label'=> 'Correo electrónico', 'required'=>false])
            ->add('user',EntityType::class, [
                'label'=>'Asignar trabajador',
                'class' => User::class,
                'choices'=> $this->userRepository->getNormalUsers(),
                'choice_label' => function ($user) {
                    return $user->getCompleteName();
                }
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => Client::class,
        ]);
    }
}
