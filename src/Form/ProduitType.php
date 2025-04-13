<?php

namespace App\Form;

use App\Entity\Produit;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Length;

class ProduitType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('nom_produit', TextType::class, [
                'constraints' => [
                    new NotBlank([
                        'message' => 'Le nom du produit ne peut pas être vide.',
                    ]),
                    new Length([
                        'max' => 255,
                        'maxMessage' => 'Le nom du produit ne doit pas dépasser {{ limit }} caractères.',
                    ]),
                ],
                'label' => 'Nom du produit',
                'attr' => ['class' => 'form-control', 'maxlength' => '255'],
            ])
            ->add('description', TextareaType::class, [
                'constraints' => [
                    new NotBlank([
                        'message' => 'La description ne peut pas être vide.',
                    ]),
                    new Length([
                        'max' => 1000,
                        'maxMessage' => 'La description ne doit pas dépasser {{ limit }} caractères.',
                    ]),
                ],
                'label' => 'Description',
                'attr' => ['class' => 'form-control', 'rows' => '5'],
            ])
            ->add('platform', TextType::class, [
                'constraints' => [
                    new NotBlank([
                        'message' => 'La plateforme ne peut pas être vide.',
                    ]),
                    new Length([
                        'max' => 100,
                        'maxMessage' => 'Le nom de la plateforme ne doit pas dépasser {{ limit }} caractères.',
                    ]),
                ],
                'label' => 'Plateforme',
                'attr' => ['class' => 'form-control'],
            ])
            ->add('type', TextType::class, [
                'constraints' => [
                    new NotBlank([
                        'message' => 'Le type ne peut pas être vide.',
                    ]),
                    new Length([
                        'max' => 100,
                        'maxMessage' => 'Le type ne doit pas dépasser {{ limit }} caractères.',
                    ]),
                ],
                'label' => 'Type',
                'attr' => ['class' => 'form-control'],
            ])
            ->add('region', TextType::class, [
                'constraints' => [
                    new NotBlank([
                        'message' => 'La région ne peut pas être vide.',
                    ]),
                    new Length([
                        'max' => 100,
                        'maxMessage' => 'La région ne doit pas dépasser {{ limit }} caractères.',
                    ]),
                ],
                'label' => 'Région',
                'attr' => ['class' => 'form-control'],
            ])
            ->add('activation_region', TextType::class, [
                'constraints' => [
                    new NotBlank([
                        'message' => 'La région d\'activation ne peut pas être vide.',
                    ]),
                    new Length([
                        'max' => 100,
                        'maxMessage' => 'La région d\'activation ne doit pas dépasser {{ limit }} caractères.',
                    ]),
                ],
                'label' => 'Région d\'activation',
                'attr' => ['class' => 'form-control'],
            ])
            ->add('score', \Symfony\Component\Form\Extension\Core\Type\IntegerType::class, [
                'constraints' => [
                    new NotBlank([
                        'message' => 'Le score ne peut pas être vide.',
                    ]),
                ],
                'label' => 'Score',
                'attr' => ['class' => 'form-control', 'min' => '0', 'max' => '100'],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Produit::class,
        ]);
    }
}