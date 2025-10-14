<?php

namespace App\Form;

use App\Entity\Users;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;

class UserType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('mail', EmailType::class, [
                'constraints' => [new Assert\NotBlank(), new Assert\Email(), new Assert\Length(['max' => 255])],
                'trim' => true,
            ])
            ->add('name', null, [
                'constraints' => [new Assert\NotBlank(), new Assert\Length(['max' => 255])],
                'trim' => true,
            ])
            ->add('factor', ChoiceType::class, [
                'choices' => array_combine(range(1, 10), range(1, 10)),
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Users::class,
        ]);
    }
}

