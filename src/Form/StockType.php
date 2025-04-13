<?php

namespace App\Form;

use App\Entity\Stock;
use App\Entity\Produit;
use App\Entity\Games;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\FormBuilderInterface; // Correct import
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\PositiveOrZero;
use Symfony\Component\Validator\Constraints\Positive;
use Symfony\Component\Validator\Constraints\File;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;

class StockType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('produit', EntityType::class, [
                'class' => Produit::class,
                'choice_label' => 'nomProduit',
                'label' => 'Produit',
                'constraints' => [
                    new NotBlank([
                        'message' => 'Veuillez sélectionner un produit',
                    ]),
                ],
                'attr' => ['class' => 'form-control']
            ])
            ->add('games', EntityType::class, [
                'class' => Games::class,
                'choice_label' => 'gameName',
                'label' => 'Jeu',
                'required' => false,
                'placeholder' => 'Sélectionner un jeu (optionnel)',
                'attr' => ['class' => 'form-control']
            ])
            ->add('quantity', IntegerType::class, [
                'constraints' => [
                    new NotBlank([
                        'message' => 'Veuillez entrer une quantité',
                    ]),
                    new PositiveOrZero([
                        'message' => 'La quantité doit être un nombre positif ou zéro',
                    ]),
                ],
                'label' => 'Quantité',
                'attr' => ['class' => 'form-control', 'min' => '0', 'step' => '1']
            ])
            ->add('prix_produit', IntegerType::class, [
                'constraints' => [
                    new NotBlank([
                        'message' => 'Veuillez entrer un prix',
                    ]),
                    new Positive([
                        'message' => 'Le prix doit être un nombre positif',
                    ]),
                ],
                'label' => 'Prix',
                'attr' => ['class' => 'form-control', 'min' => '0.01', 'step' => '0.01']
            ])
            ->add('fichierImage', FileType::class, [
                'constraints' => [
                    new File([
                        'maxSize' => '5m',
                        'mimeTypes' => [
                            'image/jpeg',
                            'image/png',
                            'image/gif',
                        ],
                        'mimeTypesMessage' => 'Veuillez uploader une image valide (JPEG, PNG, GIF)',
                        'maxSizeMessage' => 'L\'image ne doit pas dépasser 5 Mo',
                    ]),
                ],
                'label' => 'Image (laisser vide pour conserver l\'actuelle)',
                'mapped' => false,
                'required' => false,
                'attr' => ['class' => 'form-control', 'accept' => 'image/jpeg,image/png,image/gif']
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Stock::class,
        ]);
    }
}