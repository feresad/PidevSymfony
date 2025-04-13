<?php

namespace App\Form;

use App\Entity\CategorieEvent;
use App\Entity\Evenement;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\File;

class EvenementType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('nomEvent')
            ->add('categorie', EntityType::class, [
                'class' => CategorieEvent::class,
                'choice_label' => 'nom',
                'label' => 'Catégorie de l\'événement',
                'placeholder' => 'Sélectionner une catégorie',
                'required' => true,
            ])
            ->add('maxPlacesEvent')
            ->add('dateEvent', null, [
                'widget' => 'single_text',
            ])
            ->add('lieuEvent')
            ->add('photoFile', FileType::class, [
                'label' => 'Photo de l\'événement',
                'mapped' => false,
                'required' => false,
                'constraints' => [
                    new File([
                        'maxSize' => '5M',
                        'mimeTypes' => ['image/jpeg', 'image/png', 'image/webp'],
                        'mimeTypesMessage' => 'Veuillez uploader une image valide (JPG, PNG, WEBP).',
                    ]),
                ],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Evenement::class,
        ]);
    }
}
