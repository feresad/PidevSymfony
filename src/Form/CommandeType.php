<?php
namespace App\Form;

use App\Entity\Commande;
use App\Entity\Produit;
use App\Entity\Utilisateur;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;

class CommandeType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('utilisateur', EntityType::class, [
                'class' => Utilisateur::class,
                'choice_label' => 'email', // ou 'nickname' ou autre
            ])
            ->add('produit', EntityType::class, [
                'class' => Produit::class,
                'choice_label' => 'nom', // ou autre champ de Produit
            ])
            ->add('status', ChoiceType::class, [
                'choices' => [
                    'En attente' => 'en attente',
                    'Terminé' => 'terminé',
                    'Annulé' => 'annulé',
                    'Échec' => 'échec',
                    'Paiement en attente' => 'pending_payment',
                ],
            ])
            ->add('save', SubmitType::class, [
                'label' => 'Valider la commande'
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Commande::class,
        ]);
    }
}
