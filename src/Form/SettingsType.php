<?php

namespace App\Form;

use App\Entity\Settings;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class SettingsType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $settings = $options['settings'];
        
        foreach ($settings as $setting) {
            $type = $setting->getDataType();
            $label = ucfirst(str_replace('_', ' ', $setting->getSettingKey()));
            $value = $setting->getTypedValue();
            
            switch ($type) {
                case 'boolean':
                    $builder->add($setting->getSettingKey(), CheckboxType::class, [
                        'label' => $label,
                        'required' => false,
                        'data' => $value,
                        'attr' => ['class' => 'form-check-input'],
                    ]);
                    break;
                    
                case 'integer':
                    $builder->add($setting->getSettingKey(), NumberType::class, [
                        'label' => $label,
                        'required' => false,
                        'data' => $value,
                        'html5' => true,
                        'attr' => ['class' => 'form-control'],
                    ]);
                    break;
                    
                case 'float':
                    $builder->add($setting->getSettingKey(), NumberType::class, [
                        'label' => $label,
                        'required' => false,
                        'data' => $value,
                        'scale' => 2,
                        'html5' => true,
                        'attr' => ['class' => 'form-control'],
                    ]);
                    break;
                    
                case 'text':
                case 'string':
                    $builder->add($setting->getSettingKey(), TextareaType::class, [
                        'label' => $label,
                        'required' => false,
                        'data' => $value,
                        'attr' => ['class' => 'form-control', 'rows' => 3],
                    ]);
                    break;
                    
                case 'choice':
                    $choices = [];
                    if (is_array($value) && isset($value['choices'])) {
                        $choices = $value['choices'];
                        $default = $value['default'] ?? null;
                        
                        $builder->add($setting->getSettingKey(), ChoiceType::class, [
                            'label' => $label,
                            'choices' => array_combine($choices, $choices),
                            'data' => $default,
                            'attr' => ['class' => 'form-control'],
                        ]);
                    }
                    break;
                    
                default:
                    $builder->add($setting->getSettingKey(), TextType::class, [
                        'label' => $label,
                        'required' => false,
                        'data' => $value,
                        'attr' => ['class' => 'form-control'],
                    ]);
            }
        }
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'settings' => [],
            'data_class' => null,
        ]);
        
        $resolver->setRequired('settings');
    }
}