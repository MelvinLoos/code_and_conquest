<?php

namespace App\Form;

use App\Entity\Character;
use App\Service\CharacterStatsService;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
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
            ->add('characterClass', ChoiceType::class, [
                'label' => 'Choose Your Archetype',
                'choices' => CharacterStatsService::getClasses(),
                'mapped' => false,
                'constraints' => [
                    new NotBlank(['message' => 'Please select an archetype.']),
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