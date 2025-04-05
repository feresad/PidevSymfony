<?php

namespace App\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\NotBlank;

class LoginFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('email', TextType::class, [
                'label' => "Nom d'utilisateur",
                'attr' => [
                    'placeholder' => "Votre nom d'utilisateur",
                    'class' => 'form-control'
                ],
                'constraints' => [
                    new NotBlank([
                        'message' => "Veuillez entrer votre nom d'utilisateur",
                    ]),
                ],
            ])
            ->add('password', PasswordType::class, [
                'label' => 'Mot de passe',
                'attr' => [
                    'placeholder' => 'Votre mot de passe',
                    'class' => 'form-control'
                ],
                'constraints' => [
                    new NotBlank([
                        'message' => 'Veuillez entrer votre mot de passe',
                    ]),
                ],
            ])
            ->add('remember_me', CheckboxType::class, [
                'label' => 'Se souvenir de moi',
                'required' => false,
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'csrf_protection' => true,
            'csrf_field_name' => '_csrf_token',
            'csrf_token_id'   => 'authenticate',
        ]);
    }

    public function getBlockPrefix(): string
    {
        return '';
    }
} 