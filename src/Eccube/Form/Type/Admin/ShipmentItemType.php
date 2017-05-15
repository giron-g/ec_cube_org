<?php
/*
 * This file is part of EC-CUBE
 *
 * Copyright(c) 2000-2015 LOCKON CO.,LTD. All Rights Reserved.
 *
 * http://www.lockon.co.jp/
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License
 * as published by the Free Software Foundation; either version 2
 * of the License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA  02111-1307, USA.
 */


namespace Eccube\Form\Type\Admin;

use Eccube\Form\DataTransformer;
use Eccube\Entity\Master\OrderItemType;
use Eccube\Entity\Master\TaxType;
use Eccube\Entity\Master\TaxDisplayType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\MoneyType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;

class ShipmentItemType extends AbstractType
{
    protected $app;

    public function __construct($app)
    {
        $this->app = $app;
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $config = $this->app['config'];

        $builder
            ->add('new', HiddenType::class, array(
                'required' => false,
                'mapped' => false,
                'data' => 1
            ))
            ->add('price', MoneyType::class, array(
                'currency' => 'JPY',
                'scale' => 0,
                'grouping' => true,
                'constraints' => array(
                    new Assert\NotBlank(),
                    new Assert\Length(array(
                        'max' => $config['int_len'],
                    )),
                ),
            ))
            ->add('quantity', TextType::class, array(
                'constraints' => array(
                    new Assert\NotBlank(),
                    new Assert\Length(array(
                        'max' => $config['int_len'],
                    )),
                ),
            ))
            ->add('tax_rate', TextType::class, array(
                'constraints' => array(
                    new Assert\NotBlank(),
                    new Assert\Length(array(
                        'max' => $config['int_len'],
                    )),
                    new Assert\Regex(array(
                        'pattern' => "/^\d+(\.\d+)?$/u",
                        'message' => 'form.type.float.invalid'
                    )),
                )
            ))
            ->add('product_name', HiddenType::class)
            ->add('product_code', HiddenType::class)
            ->add('class_name1', HiddenType::class)
            ->add('class_name2', HiddenType::class)
            ->add('class_category_name1', HiddenType::class)
            ->add('class_category_name2', HiddenType::class)
            ->add('tax_rule', HiddenType::class)
            // ->add('order_id', HiddenType::class)
        ;

        $builder
            ->add($builder->create('order_item_type', HiddenType::class)
                ->addModelTransformer(new DataTransformer\EntityToIdTransformer(
                    $this->app['orm.em'],
                    '\Eccube\Entity\Master\OrderItemType'
                )))
            ->add($builder->create('tax_type', HiddenType::class)
                ->addModelTransformer(new DataTransformer\EntityToIdTransformer(
                    $this->app['orm.em'],
                    '\Eccube\Entity\Master\TaxType'
                )))
            ->add($builder->create('tax_display_type', HiddenType::class)
                ->addModelTransformer(new DataTransformer\EntityToIdTransformer(
                    $this->app['orm.em'],
                    '\Eccube\Entity\Master\TaxDisplayType'
                )))
            ->add($builder->create('Product', HiddenType::class)
                ->addModelTransformer(new DataTransformer\EntityToIdTransformer(
                    $this->app['orm.em'],
                    '\Eccube\Entity\Product'
                )))
            ->add($builder->create('ProductClass', HiddenType::class)
                ->addModelTransformer(new DataTransformer\EntityToIdTransformer(
                    $this->app['orm.em'],
                    '\Eccube\Entity\ProductClass'
                )))
            ->add($builder->create('Order', HiddenType::class)
                ->addModelTransformer(new DataTransformer\EntityToIdTransformer(
                    $this->app['orm.em'],
                    '\Eccube\Entity\Order'
                )))
            ->add($builder->create('Shipping', HiddenType::class)
                ->addModelTransformer(new DataTransformer\EntityToIdTransformer(
                    $this->app['orm.em'],
                    '\Eccube\Entity\Shipping'
                )));

        $app = $this->app;
        $builder->addEventListener(FormEvents::PRE_SUBMIT, function (FormEvent $event) use ($app) {
            // モーダルからのPOST時に、金額等をセットする.
            if ('modal' === $app['request_stack']->getCurrentRequest()->get('modal')) {
                $data = $event->getData();
                // 新規明細行の場合にセット.
                if (isset($data['new'])) {
                    switch ($data['order_item_type']) {
                        case OrderItemType::DELIVERY_FEE:
                            $data['product_name'] = '送料';
                            $data['product_code'] = null;
                            $data['class_name1'] = null;
                            $data['class_name2'] = null;
                            $data['class_category_name1'] = null;
                            $data['class_category_name2'] = null;
                            $data['price'] = 0;
                            $data['quantity'] = 1;
                            $data['tax_type'] = TaxType::TAXATION;
                            $data['tax_display_type'] = TaxDisplayType::INCLUDED;
                            break;
                        case OrderItemType::CHARGE:
                            $data['product_name'] = '手数料';
                            $data['product_code'] = null;
                            $data['class_name1'] = null;
                            $data['class_name2'] = null;
                            $data['class_category_name1'] = null;
                            $data['class_category_name2'] = null;
                            $data['price'] = 0;
                            $data['quantity'] = 1;
                            $data['tax_type'] = TaxType::TAXATION;
                            $data['tax_display_type'] = TaxDisplayType::INCLUDED;
                            break;
                        case OrderItemType::DISCOUNT:
                            $data['product_name'] = '値引き';
                            $data['product_code'] = null;
                            $data['class_name1'] = null;
                            $data['class_name2'] = null;
                            $data['class_category_name1'] = null;
                            $data['class_category_name2'] = null;
                            $data['price'] = -0;
                            $data['quantity'] = 1;
                            $data['tax_type'] = TaxType::NON_TAXABLE;
                            $data['tax_display_type'] = TaxDisplayType::INCLUDED;
                            break;
                        case OrderItemType::PRODUCT:
                        default:
                            /** @var \Eccube\Entity\ProductClass $ProductClass */
                            $ProductClass = $app['eccube.repository.product_class']
                                ->find($data['ProductClass']);
                            /** @var \Eccube\Entity\Product $Product */
                            $Product = $ProductClass->getProduct();

                            $data['product_name'] = $Product->getName();
                            $data['product_code'] = $ProductClass->getCode();
                            $data['class_name1'] = $ProductClass->hasClassCategory1() ?
                                $ProductClass->getClassCategory1()->getClassName() :
                                null;
                            $data['class_name2'] = $ProductClass->hasClassCategory2() ?
                                $ProductClass->getClassCategory2()->getClassName() :
                                null;
                            $data['class_category_name1'] = $ProductClass->hasClassCategory1() ?
                                $ProductClass->getClassCategory1()->getName() :
                                null;
                            $data['class_category_name2'] = $ProductClass->hasClassCategory2() ?
                                $ProductClass->getClassCategory2()->getName() :
                                null;
                            $data['price'] = $ProductClass->getPrice02();
                            $data['quantity'] = empty($data['quantity']) ? 1 : $data['quantity'];
                            $data['tax_type'] = TaxType::TAXATION;
                            $data['tax_display_type'] = TaxDisplayType::EXCLUDED;
                    }
                    $event->setData($data);
                }
            }
        });
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(array(
            'data_class' => 'Eccube\Entity\ShipmentItem',
        ));
    }

    /**
     * {@inheritdoc}
     */
    public function getBlockPrefix()
    {
        return 'shipment_item';
    }
}
