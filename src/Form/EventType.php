<?php

namespace App\Form;

use App\Entity\Event;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
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
            ->add('title', TextType::class)
            ->add('description', TextareaType::class)
            ->add('startDateTime', DateTimeType::class, ['widget' => 'single_text'])
            ->add('endDateTime', DateTimeType::class, ['widget' => 'single_text'])
            ->add('maxPlayers', IntegerType::class)
            ->add('isApproved', CheckboxType::class, [
                'required' => false,
                'label' => 'Approuvé ?'
            ])
            ->add('isStarted', CheckboxType::class, [
                'required' => false,
                'label' => 'Commencé ?'
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
