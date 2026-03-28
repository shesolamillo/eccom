<?php

namespace App\Form;

use App\Entity\User;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\RepeatedType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\IsTrue;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;

class RegistrationFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('email', EmailType::class, [
                'label' => 'Email Address',
                'attr' => [
                    'placeholder' => 'Enter your email',
                    'class' => 'form-control',
                ],
                'constraints' => [
                    new NotBlank([
                        'message' => 'Please enter an email address',
                    ]),
                ],
            ])
            ->add('firstName', TextType::class, [
                'label' => 'First Name',
                'required' => false,
                'attr' => [
                    'placeholder' => 'Enter your first name',
                    'class' => 'form-control',
                ],
            ])
            ->add('lastName', TextType::class, [
                'label' => 'Last Name',
                'required' => false,
                'attr' => [
                    'placeholder' => 'Enter your last name',
                    'class' => 'form-control',
                ],
            ])
            ->add('phoneNumber', TextType::class, [
                'label' => 'Phone Number',
                'required' => false,
                'attr' => [
                    'placeholder' => 'Enter your phone number',
                    'class' => 'form-control',
                ],
            ])
            ->add('plainPassword', RepeatedType::class, [
                'type' => PasswordType::class,
                'mapped' => false, 
                'invalid_message' => 'The password fields must match.',
                'options' => [
                    'attr' => ['class' => 'password-field form-control'],
                ],
                'required' => true,
                'first_options'  => [
                    'label' => 'Password',
                    'attr' => ['placeholder' => 'Enter password'],
                ],
                'second_options' => [
                    'label' => 'Repeat Password',
                    'attr' => ['placeholder' => 'Repeat password'],
                ],
                'constraints' => [
                    new NotBlank([
                        'message' => 'Please enter a password',
                    ]),
                    new Length([
                        'min' => 6,
                        'minMessage' => 'Your password should be at least {{ limit }} characters',
                        'max' => 4096,
                    ]),
                ],
                
            ])
            ->add('agreeTerms', CheckboxType::class, [
                'mapped' => false,   // not in your User entity
                'label' => 'I agree to the terms and conditions',
                'constraints' => [
                    new IsTrue(message: 'You must agree to the terms.'),
                ],
            ])

             ->add('email', EmailType::class, [
                'attr' => ['autocomplete' => 'email'],
                'constraints' => [
                    new NotBlank([
                        'message' => 'Please enter an email',
                    ]),
                ],
            ]);
            


    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => User::class,
        ]);
    }
}