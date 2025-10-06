<?php

namespace App\Form;

use App\Entity\Character;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Email;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Regex;

class CharacterRegistrationFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('characterName', TextType::class, [
                'label' => 'Netrunner Handle',
                'constraints' => [
                    new NotBlank(['message' => 'Please enter your desired handle.']),
                ],
            ])
            ->add('email', EmailType::class, [
                'label' => 'School Email Address',
                'constraints' => [
                    new NotBlank(['message' => 'Please enter your school email.']),
                    new Email(['message' => 'Please enter a valid email address.']),
                    new Regex([
                        'pattern' => '/^.+@glr\.nl$/i',
                        'message' => 'You must use a valid @glr.nl school email address.',
                    ]),
                ],
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Character::class,
        ]);
    }
}