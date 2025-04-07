<?php

namespace App\Form;

use App\Entity\Reports;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Choice;
use Symfony\Component\Validator\Constraints\Length;

class ReportType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $validStatuses = ['PENDING', 'RESOLVED', 'DISMISSED']; // Define valid status values

        $builder
            ->add('reason', ChoiceType::class, [
                'choices' => [
                    'Minor Involved' => 'MINEUR_IMPLIQUE',
                    'Harassment' => 'HARCELEMENT',
                    'Suicide/Self-Harm' => 'SUICIDE_AUTOMUTILATION',
                    'Violent Content' => 'CONTENU_VIOLENT',
                    'Restricted Items Sale' => 'VENTE_ARTICLES_RESTREINTS',
                    'Adult Content' => 'CONTENU_ADULTE',
                    'Scam/False Information' => 'ARNAQUE_FAUSSE_INFORMATION',
                    'Unwanted Content' => 'CONTENU_NON_DESIRE',
                ],
                'placeholder' => 'Select a reason',
                'required' => true,
                'constraints' => [
                    new NotBlank([
                        'message' => 'Please select a reason for the report.',
                    ]),
                    new Choice([
                        'choices' => [
                            'MINEUR_IMPLIQUE',
                            'HARCELEMENT',
                            'SUICIDE_AUTOMUTILATION',
                            'CONTENU_VIOLENT',
                            'VENTE_ARTICLES_RESTREINTS',
                            'CONTENU_ADULTE',
                            'ARNAQUE_FAUSSE_INFORMATION',
                            'CONTENU_NON_DESIRE',
                        ],
                        'message' => 'Invalid reason selected.',
                    ]),
                ],
            ])
            ->add('evidence', TextareaType::class, [
                'required' => false,
                'label' => 'Additional Details (optional)',
                'constraints' => [
                    
                    new Length([
                        'max' => 1000,
                        'maxMessage' => 'The evidence cannot be longer than {{ limit }} characters.',
                    ]),
                ],
            ])
            ->add('status', HiddenType::class, [
                'data' => 'PENDING',
                'required' => true,
                'empty_data' => 'PENDING',
                'constraints' => [
                    new NotBlank([
                        'message' => 'Status cannot be blank.',
                    ]),
                    new Choice([
                        'choices' => $validStatuses,
                        'message' => 'Invalid status value. Allowed values are: {{ choices }}.',
                    ]),
                ],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Reports::class,
            'csrf_protection' => false, // Already disabled for API
            'allow_extra_fields' => true, // Allow extra fields like reporterId and reportedUserId
        ]);
    }
}