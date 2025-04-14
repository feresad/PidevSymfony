<?php

namespace App\Form;

use App\Entity\Games;
use App\Enum\GameType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class GameFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('game_name', TextType::class, [
                'label' => 'Game Name',
                'required' => true,
            ])
            ->add('image_path', FileType::class, [
                'label' => 'Game Image',
                'required' => false,
                'mapped' => false, // Handle file upload manually
            ])
            ->add('game_type', ChoiceType::class, [
                'label' => 'Game Type',
                'choices' => [
                    'FPS' => GameType::FPS,
                    'Hero Shooter' => GameType::HERO_SHOOTER,
                    'Third Person Shooter' => GameType::THIRD_PERSON_SHOOTER,
                    'Sports' => GameType::SPORTS,
                    'Other' => GameType::OTHER,
                ],
                'required' => true,
            ])
            ->add('submit', SubmitType::class, [
                'label' => 'Save Game',
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Games::class,
        ]);
    }
}