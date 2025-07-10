<?php

namespace App\Form;

use App\Entity\Event;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class EventType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('title', TextType::class,  [
                'label' => 'Titre : '
            ])
            ->add('description', TextareaType::class, [
                'label' => 'Description : '
            ])
            ->add('startDateTime', DateTimeType::class, [
                'label' => 'Début : ',
                'widget' => 'single_text'])
            ->add('endDateTime', DateTimeType::class, [
                'label' => 'Fin : ',
                'widget' => 'single_text'])
            ->add('maxPlayers', IntegerType::class, [
                'label' => 'Nombre de joueurs : '
            ])
            ->add('game', ChoiceType::class, [
                'label' => 'Jeu concerné',
                'choices' => [
                    'CS2' => 'CS2',
                    'Dota2' => 'Dota2',
                    'League of Legends' => 'League of Legends',
                    'Rocket League' => 'Rocket League',
                    'Super Smash Bros.' => 'Super Smash Bros.',
                    'TFT' => 'TFT',
                    'Valorant' => 'Valorant',
                ],
                'placeholder' => 'Sélectionnez un jeu',
                'required' => true,
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Event::class,
        ]);
    }
}
