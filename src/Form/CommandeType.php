<?php

namespace App\Form;

use App\Entity\Commande;
use App\Entity\Produit;
use App\Entity\Utilisateur;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class CommandeType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('utilisateur', EntityType::class, [
                'class' => Utilisateur::class,
                'choice_label' => 'nickname',
                'label' => 'Client',
                'attr' => [
                    'class' => 'form-control',
                    'style' => 'background-color: #091221; color: white; border-color: #0236a5;'
                ],
                'required' => true
            ])
            ->add('produit', EntityType::class, [
                'class' => Produit::class,
                'choice_label' => 'nomProduit',
                'label' => 'Produit',
                'attr' => [
                    'class' => 'form-control',
                    'style' => 'background-color: #091221; color: white; border-color: #0236a5;'
                ],
                'required' => true
            ])
            ->add('status', ChoiceType::class, [
                'choices' => [
                    'En attente de paiement' => 'pending_payment',
                    'Terminé' => 'terminé',
                    'Annulé' => 'annulé'
                ],
                'label' => 'Statut',
                'attr' => [
                    'class' => 'form-control',
                    'style' => 'background-color: #091221; color: white; border-color: #0236a5;'
                ],
                'required' => true
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Commande::class,
            'validation_groups' => ['Default'],
        ]);
    }
} 