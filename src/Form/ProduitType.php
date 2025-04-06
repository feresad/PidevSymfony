<?php

namespace App\Form;

use App\Entity\Produit;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Range;

class ProduitType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('nom_produit', TextType::class, [
                'constraints' => [
                    new NotBlank([
                        'message' => 'Veuillez entrer un nom de produit',
                    ]),
                    new Length([
                        'min' => 3,
                        'minMessage' => 'Le nom du produit doit contenir au moins {{ limit }} caractères',
                        'max' => 255,
                        'maxMessage' => 'Le nom du produit ne peut pas dépasser {{ limit }} caractères',
                    ]),
                ],
                'label' => 'Nom du produit',
                'attr' => ['class' => 'form-control']
            ])
            ->add('description', TextareaType::class, [
                'constraints' => [
                    new NotBlank([
                        'message' => 'Veuillez entrer une description',
                    ]),
                    new Length([
                        'min' => 10,
                        'minMessage' => 'La description doit contenir au moins {{ limit }} caractères',
                        'max' => 1000,
                        'maxMessage' => 'La description ne peut pas dépasser {{ limit }} caractères',
                    ]),
                ],
                'label' => 'Description',
                'attr' => ['class' => 'form-control', 'rows' => 5]
            ])
            ->add('score', IntegerType::class, [
                'constraints' => [
                    new NotBlank([
                        'message' => 'Veuillez entrer un score',
                    ]),
                    new Range([
                        'min' => 0,
                        'max' => 100,
                        'notInRangeMessage' => 'Le score doit être entre {{ min }} et {{ max }}',
                    ]),
                ],
                'label' => 'Score',
                'attr' => ['class' => 'form-control']
            ])
            ->add('platform', TextType::class, [
                'constraints' => [
                    new NotBlank([
                        'message' => 'Veuillez entrer une plateforme',
                    ]),
                    new Length([
                        'min' => 2,
                        'minMessage' => 'La plateforme doit contenir au moins {{ limit }} caractères',
                        'max' => 50,
                        'maxMessage' => 'La plateforme ne peut pas dépasser {{ limit }} caractères',
                    ]),
                ],
                'label' => 'Plateforme',
                'attr' => ['class' => 'form-control']
            ])
            ->add('type', TextType::class, [
                'constraints' => [
                    new NotBlank([
                        'message' => 'Veuillez entrer un type',
                    ]),
                    new Length([
                        'min' => 2,
                        'minMessage' => 'Le type doit contenir au moins {{ limit }} caractères',
                        'max' => 50,
                        'maxMessage' => 'Le type ne peut pas dépasser {{ limit }} caractères',
                    ]),
                ],
                'label' => 'Type',
                'attr' => ['class' => 'form-control']
            ])
            ->add('region', TextType::class, [
                'constraints' => [
                    new NotBlank([
                        'message' => 'Veuillez entrer une région',
                    ]),
                    new Length([
                        'min' => 2,
                        'minMessage' => 'La région doit contenir au moins {{ limit }} caractères',
                        'max' => 50,
                        'maxMessage' => 'La région ne peut pas dépasser {{ limit }} caractères',
                    ]),
                ],
                'label' => 'Région',
                'attr' => ['class' => 'form-control']
            ])
            ->add('activation_region', TextType::class, [
                'constraints' => [
                    new NotBlank([
                        'message' => 'Veuillez entrer une région d\'activation',
                    ]),
                    new Length([
                        'min' => 2,
                        'minMessage' => 'La région d\'activation doit contenir au moins {{ limit }} caractères',
                        'max' => 50,
                        'maxMessage' => 'La région d\'activation ne peut pas dépasser {{ limit }} caractères',
                    ]),
                ],
                'label' => 'Région d\'activation',
                'attr' => ['class' => 'form-control']
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Produit::class,
        ]);
    }
}