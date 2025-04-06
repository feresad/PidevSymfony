<?php

namespace App\Form;

use App\Entity\Commentaire;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\Constraints\Callback;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

class CommentFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('contenu', TextareaType::class, [
                'label' => false,
                'required' => true,
                'attr' => ['rows' => 10, 'class' => 'nk-summernote'],
                'constraints' => [
                    new Callback([
                        'callback' => function ($value, ExecutionContextInterface $context) {
                            $plainText = strip_tags($value);
                            $plainText = trim($plainText);
                            if (empty($plainText)) {
                                $context->buildViolation('Comment content cannot be empty.')
                                    ->atPath('contenu')
                                    ->addViolation();
                            }
                        },
                    ]),
                ],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Commentaire::class,
        ]);
    }
}