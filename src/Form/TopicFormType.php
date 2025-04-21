<?php

namespace App\Form;

use App\Entity\Games;
use App\Entity\Questions;
use App\Enum\MediaType;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class TopicFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('title', TextType::class, [
                'label' => 'Topic Title',
                'required' => false, 
                'attr' => ['placeholder' => 'Enter topic title'],
            ])
            ->add('content', TextareaType::class, [
                'label' => false,
                'required' => false,
                'attr' => ['rows' => 5, 'placeholder' => "What's on your mind?"],
            ])
            ->add('media_type', ChoiceType::class, [
                'label' => 'Media Type',
                'choices' => [
                    'None' => null,
                    'Image' => MediaType::IMAGE,
                    'Video' => MediaType::VIDEO,
                ],
                'required' => false,
                'attr' => ['placeholder' => 'e.g., image or video'],
            ])
            ->add('media_file', FileType::class, [
                'label' => 'Upload Media (Image, GIF, or Video)',
                'required' => false,
                'mapped' => false,
                'attr' => ['class' => 'form-control-file'],
            ])
            ->add('game_id', EntityType::class, [
                'class' => Games::class,
                'choice_label' => 'gameName',
                'label' => 'Game',
                'required' => false,
                'attr' => ['class' => 'form-control'],
            ])
            ->add('submit', SubmitType::class, [
                'label' => 'Post Topic',
                'attr' => ['class' => 'btn btn-primary'],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Questions::class,
        ]);
    }
}