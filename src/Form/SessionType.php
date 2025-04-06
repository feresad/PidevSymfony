<?php

namespace App\Form;

use App\Entity\Session_game;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class SessionType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('game', TextType::class, [
                'label' => 'Jeu',
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => 'Ex: League of Legends, Fortnite, Minecraft...',
                    'title' => 'Entrez le nom du jeu pour cette session'
                ]
            ])
            ->add('prix', NumberType::class, [
                'label' => 'Prix',
                'scale' => 2,
                'attr' => [
                    'class' => 'form-control',
                    'step' => '0.01',
                    'placeholder' => 'Ex: 25.00',
                    'title' => 'Entrez le prix en euros (€)'
                ]
            ])
            ->add('dureeSession', TextType::class, [
                'label' => 'Durée',
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => 'Ex: 2 heures, 90 minutes...',
                    'title' => 'Spécifiez la durée de la session'
                ]
            ])
            ->add('image', FileType::class, [
                'label' => 'Image',
                'mapped' => false,
                'required' => false,
                'attr' => [
                    'class' => 'form-control',
                    'accept' => 'image/*',
                    'title' => 'Formats acceptés: JPG, PNG, GIF (max 2MB)'
                ]
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Session_game::class,
        ]);
    }
}