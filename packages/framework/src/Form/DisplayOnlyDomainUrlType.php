<?php

namespace Shopsys\FrameworkBundle\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\OptionsResolver\OptionsResolver;

class DisplayOnlyDomainUrlType extends AbstractType
{
    /**
     * @param \Symfony\Component\OptionsResolver\OptionsResolver $resolver
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver
            ->setDefaults([
                'mapped' => false,
                'required' => false,
                'attr' => [
                    'readonly' => 'readonly',
                ],
                'data' => [
                    'route' => '',
                    'route_params' => [],
                    'domain_id' => null,
                    'name' => '',
                ],
            ]);
    }
}
