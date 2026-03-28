<?php

namespace App\Form;

use App\Entity\UserProfile;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\File;
use App\Form\ProfileFormType;


class ProfileFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('profilePicture', FileType::class, [
                'label' => 'Profile Picture',
                'required' => false,
                'mapped' => false,
                'attr' => ['class' => 'form-control'],
                'constraints' => [
                    new File([
                        'maxSize' => '2M',
                        'mimeTypes' => [
                            'image/jpeg',
                            'image/png',
                            'image/gif',
                        ],
                        'mimeTypesMessage' => 'Please upload a valid image file (JPEG, PNG, GIF)',
                    ])
                ],
            ])
            ->add('address', TextareaType::class, [
                'label' => 'Address',
                'required' => false,
                'attr' => [
                    'rows' => 3,
                    'class' => 'form-control',
                    'placeholder' => 'Enter your address',
                ],
            ])
            ->add('city', TextType::class, [
                'label' => 'City',
                'required' => false,
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => 'Enter your city',
                ],
            ])
            ->add('state', TextType::class, [
                'label' => 'State/Province',
                'required' => false,
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => 'Enter your state/province',
                ],
            ])
            ->add('zipCode', TextType::class, [
                'label' => 'ZIP/Postal Code',
                'required' => false,
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => 'Enter your ZIP/postal code',
                ],
            ])
            ->add('dateOfBirth', DateType::class, [
                'label' => 'Date of Birth',
                'required' => false,
                'widget' => 'single_text',
                'attr' => ['class' => 'form-control'],
            ])
            ->add('gender', TextType::class, [
                'label' => 'Gender',
                'required' => false,
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => 'Enter your gender',
                ],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => UserProfile::class,
        ]);
    }
}