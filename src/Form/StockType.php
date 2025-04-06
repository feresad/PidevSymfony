<?php

namespace App\Form;

use App\Entity\Stock;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\File;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\PositiveOrZero;

class StockType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('produit', EntityType::class, [
                'class' => 'App\Entity\Produit',
                'choice_label' => 'nomProduit',
                'label' => 'Produit',
                'constraints' => [new NotBlank(['message' => 'Veuillez sélectionner un produit'])]
            ])
            ->add('quantity', IntegerType::class, [
                'label' => 'Quantité',
                'constraints' => [
                    new NotBlank(['message' => 'La quantité est obligatoire']),
                    new PositiveOrZero(['message' => 'La quantité doit être positive'])
                ]
            ])
            ->add('prixProduit', IntegerType::class, [
                'label' => 'Prix',
                'constraints' => [
                    new NotBlank(['message' => 'Le prix est obligatoire']),
                    new PositiveOrZero(['message' => 'Le prix doit être positif'])
                ]
            ])
            ->add('fichierImage', FileType::class, [
                'label' => 'Image',
                'required' => false,
                'mapped' => false,
                'constraints' => [
                    new File([
                        'maxSize' => '5M',
                        'mimeTypes' => ['image/jpeg', 'image/png'],
                        'mimeTypesMessage' => 'Veuillez télécharger une image valide (JPEG ou PNG)',
                    ])
                ]
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Stock::class,
        ]);
    }
}