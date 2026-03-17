<?php

namespace App\Form;

use App\Entity\Product;
use App\Entity\ProductType as ProductTypeEntity;
use App\Entity\ClothesCategory;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\File;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Positive;

class ProductType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('name', TextType::class, [
                'label' => 'Product Name',
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => 'Enter product name',
                ],
                'constraints' => [
                    new NotBlank([
                        'message' => 'Please enter a product name',
                    ]),
                ],
            ])
            ->add('description', TextareaType::class, [
                'label' => 'Description',
                'required' => false,
                'attr' => [
                    'class' => 'form-control',
                    'rows' => 4,
                    'placeholder' => 'Enter product description',
                ],
            ])
            ->add('photo', FileType::class, [
                'label' => 'Product Photo',
                'required' => false,
                'mapped' => false,
                'attr' => ['class' => 'form-control'],
                'constraints' => [
                    new File([
                        'maxSize' => '5M',
                        'mimeTypes' => [
                            'image/jpeg',
                            'image/png',
                            'image/gif',
                        ],
                        'mimeTypesMessage' => 'Please upload a valid image file (JPEG, PNG, GIF)',
                    ])
                ],
            ])
            ->add('productType', EntityType::class, [
                'label' => 'Product Type',
                'class' => ProductTypeEntity::class,
                'choice_label' => 'name',
                'placeholder' => 'Select a product type',
                'attr' => ['class' => 'form-control'],
                'constraints' => [
                    new NotBlank([
                        'message' => 'Please select a product type',
                    ]),
                ],
            ])
            ->add('clothesCategory', EntityType::class, [
                'label' => 'Category',
                'class' => ClothesCategory::class,
                'choice_label' => 'name',
                'placeholder' => 'Select a category',
                'required' => false,
                'attr' => ['class' => 'form-control'],
            ])
            ->add('price', NumberType::class, [
                'label' => 'Price',
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => 'Enter price',
                ],
                'constraints' => [
                    new NotBlank([
                        'message' => 'Please enter a price',
                    ]),
                    new Positive([
                        'message' => 'Price must be positive',
                    ]),
                ],
            ])


            ->add('costPrice', NumberType::class, [
                'label' => 'Cost Price (₱)',
                'required' => false,
                'attr' => [
                    'class' => 'form-control',
                    'min' => 0,
                    'step' => 0.01,
                    'placeholder' => 'Enter cost price',
                ],
            ])


            ->add('sku', TextType::class, [
                'label' => 'SKU (Stock Keeping Unit)',
                'required' => false,
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => 'Leave blank to auto-generate',
                ],
            ])



            ->add('isAvailable', CheckboxType::class, [
                'label' => 'Available for sale',
                'required' => false,
                'attr' => ['class' => 'form-check-input'],
            ]);

            

            
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Product::class,
        ]);
    }

    
}