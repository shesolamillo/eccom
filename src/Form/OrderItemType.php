<?php

namespace App\Form;

use App\Entity\OrderItem;
use App\Entity\Product;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Positive;
use Symfony\Component\Validator\Constraints\NotBlank;

class OrderItemType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('product', EntityType::class, [
                'label' => 'Product',
                'class' => Product::class,
                'choice_label' => 'name',
                'placeholder' => 'Select a product',
                'attr' => ['class' => 'form-control product-select'],
                'constraints' => [
                    new NotBlank([
                        'message' => 'Please select a product',
                    ]),
                ],
            ])
            ->add('quantity', IntegerType::class, [
                'label' => 'Quantity',
                'attr' => [
                    'class' => 'form-control quantity-input',
                    'min' => 1,
                ],
                'constraints' => [
                    new NotBlank([
                        'message' => 'Please enter quantity',
                    ]),
                    new Positive([
                        'message' => 'Quantity must be positive',
                    ]),
                ],
            ])
            ->add('unitPrice', NumberType::class, [
                'label' => 'Unit Price',
                'attr' => [
                    'class' => 'form-control unit-price',
                    'readonly' => true,
                ],
                'required' => false,
            ])
            
            
;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => OrderItem::class,
        ]);
    }
}