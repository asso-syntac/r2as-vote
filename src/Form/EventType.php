<?php

namespace App\Form;

use App\Entity\Events;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;

class EventType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('name', null, [
                'constraints' => [new Assert\NotBlank(), new Assert\Length(['max' => 255])],
                'trim' => true,
            ])
            ->add('description', null, [
                'constraints' => [new Assert\NotBlank(), new Assert\Length(['max' => 1000])],
                'trim' => true,
            ])
            ->add('mail', EmailType::class, [
                'constraints' => [new Assert\NotBlank(), new Assert\Email(), new Assert\Length(['max' => 255])],
                'trim' => true,
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Events::class,
        ]);
    }
}

