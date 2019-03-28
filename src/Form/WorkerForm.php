<?php
/**
 * Created by PhpStorm.
 * User: Fran
 * Date: 28/03/2019
 * Time: 11:26
 */

namespace App\Form;


use App\Entity\User;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\TelType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class WorkerForm extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add('firstName', TextType::class,['label'=> 'Nombre'])
            ->add('lastName', TextType::class,['label'=> 'Apellidos'])
            ->add('email', TextType::class,['label'=> 'Correo electrónico'])
            ->add('password',PasswordType::class,['label'=> 'Contraseña', 'help' => 'Escriba su contraseña'])
            ->add('telephone', TelType::class,['label'=> 'Teléfono'])
            //->add('sex',TextType::class,['label'=> 'Sexo'])
            //->add('avatar',FileType::class,['label'=> 'Avatar'])
        ;
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => User::class
        ]);
    }

}