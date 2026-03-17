<?php

namespace App\Form;

use App\Entity\Order;
use App\Entity\OrderItem;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class OrderType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('deliveryType', ChoiceType::class, [
                'label' => 'Delivery Type',
                'choices' => [
                    'Pickup' => Order::DELIVERY_PICKUP,
                    'Delivery' => Order::DELIVERY_DELIVERY,
                ],
                'attr' => ['class' => 'form-control'],
            ])
            ->add('paymentMethod', ChoiceType::class, [
                'label' => 'Payment Method',
                'choices' => [
                    'Cash' => Order::PAYMENT_CASH,
                    'Online' => Order::PAYMENT_ONLINE,
                ],
                'attr' => ['class' => 'form-control'],
            ])
            ->add('deliveryAddress', TextareaType::class, [
                'label' => 'Delivery Address',
                'required' => false,
                'attr' => [
                    'class' => 'form-control',
                    'rows' => 3,
                    'placeholder' => 'Enter delivery address (if delivery selected)',
                ],
            ])
            ->add('deliveryFee', NumberType::class, [
                'label' => 'Delivery Fee',
                'required' => false,
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => 'Enter delivery fee',
                ],
            ])
            ->add('pickupDate', DateTimeType::class, [
                'label' => 'Pickup Date & Time',
                'required' => false,
                'widget' => 'single_text',
                'attr' => ['class' => 'form-control'],
            ])
            ->add('deliveryDate', DateTimeType::class, [
                'label' => 'Delivery Date & Time',
                'required' => false,
                'widget' => 'single_text',
                'attr' => ['class' => 'form-control'],
            ])
            ->add('notes', TextareaType::class, [
                'label' => 'Notes',
                'required' => false,
                'attr' => [
                    'class' => 'form-control',
                    'rows' => 3,
                    'placeholder' => 'Any special instructions or notes',
                ],
            ])
            ->add('orderItems', CollectionType::class, [
                'entry_type' => OrderItemType::class,
                'entry_options' => ['label' => false],
                'allow_add' => true,
                'allow_delete' => true,
                'by_reference' => false,
                'label' => false,
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Order::class,
        ]);
    }
}