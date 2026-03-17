<?php

namespace App\Form;

use App\Entity\Stock;
use App\Entity\Product;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\PositiveOrZero;

class StockType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('product', EntityType::class, [
                'label' => 'Product',
                'class' => Product::class,
                'choice_label' => 'name',
                'placeholder' => 'Select a product',
                'attr' => ['class' => 'form-control'],
                'constraints' => [
                    new NotBlank([
                        'message' => 'Please select a product',
                    ]),
                ],
            ])
            ->add('quantity', IntegerType::class, [
                'label' => 'Quantity',
                'attr' => [
                    'class' => 'form-control',
                    'min' => 0,
                ],
                'constraints' => [
                    new NotBlank([
                        'message' => 'Please enter quantity',
                    ]),
                    new PositiveOrZero([
                        'message' => 'Quantity cannot be negative',
                    ]),
                ],
            ])
            ->add('minimumThreshold', IntegerType::class, [
                'label' => 'Minimum Threshold',
                'attr' => [
                    'class' => 'form-control',
                    'min' => 1,
                ],
                'constraints' => [
                    new NotBlank([
                        'message' => 'Please enter minimum threshold',
                    ]),
                    new PositiveOrZero([
                        'message' => 'Minimum threshold cannot be negative',
                    ]),
                ],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Stock::class,
        ]);
    }
}