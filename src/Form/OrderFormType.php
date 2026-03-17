<?php

namespace App\Form;

use App\Entity\Order;
use App\Entity\User;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\MoneyType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class OrderFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('customer', EntityType::class, [
                'label' => 'Customer *',
                'class' => User::class,
                'choice_label' => function(User $user) {
                    return $user->getFullName() . ' (' . $user->getEmail() . ')';
                },
                'placeholder' => 'Select a customer',
                'attr' => ['class' => 'form-select'],
                'required' => true,
            ])
            ->add('deliveryType', ChoiceType::class, [
                'label' => 'Delivery Type *',
                'choices' => [
                    'Pickup' => Order::DELIVERY_PICKUP,
                    'Delivery' => Order::DELIVERY_DELIVERY,
                ],
                'attr' => ['class' => 'form-select'],
                'required' => true,
            ])
            ->add('paymentMethod', ChoiceType::class, [
                'label' => 'Payment Method *',
                'choices' => [
                    'Cash' => Order::PAYMENT_CASH,
                    'Online' => Order::PAYMENT_ONLINE,
                ],
                'attr' => ['class' => 'form-select'],
                'required' => true,
            ])
            ->add('isUrgent', CheckboxType::class, [
                'label' => 'Urgent Order',
                'required' => false,
                'attr' => ['class' => 'form-check-input'],
                'label_attr' => ['class' => 'form-check-label'],
            ])
            ->add('deliveryAddress', TextareaType::class, [
                'label' => 'Delivery Address',
                'required' => false,
                'attr' => ['class' => 'form-control', 'rows' => 3],
            ])
            ->add('deliveryFee', MoneyType::class, [
                'label' => 'Delivery Fee (₱)',
                'required' => false,
                'currency' => 'PHP',
                'attr' => ['class' => 'form-control'],
                'html5' => true,
            ])
            ->add('notes', TextareaType::class, [
                'label' => 'Order Notes',
                'required' => false,
                'attr' => ['class' => 'form-control', 'rows' => 3, 'placeholder' => 'Special instructions or notes...'],
            ])
            ->add('save', SubmitType::class, [
                'label' => 'Continue to Add Products',
                'attr' => ['class' => 'btn btn-primary'],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Order::class,
        ]);
    }
}