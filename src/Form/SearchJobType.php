<?php

namespace App\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\SearchType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;

class SearchJobType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('keyword', SearchType::class, [
                'label' => 'Métier',
                'required' => false,
                'attr' => [
                    'placeholder' => 'Développeur',
                    'class' => 'form-input',
                ],
            ])
            ->add('location', TextType::class, [
                'label' => 'Département',
                'required' => false,
                'attr' => [
                    'placeholder' => 'Ex: 75, 33, 18',
                    'class' => 'form-input',
                ],
            ])
            ->add('rechercher', SubmitType::class, [
                'label' => 'Rechercher',
                'attr' => [
                    'class' => 'btn-search',
                ],
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            // Configure your form options here
            'method' => 'GET',          // On veut voir les paramètres dans l'URL
            'csrf_protection' => false, // Pas besoin de token de sécu pour une recherche publique (ça rend l'URL plus propre)
        ]);
    }
}
