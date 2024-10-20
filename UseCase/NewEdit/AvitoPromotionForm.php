<?php
/*
 *  Copyright 2024.  Baks.dev <admin@baks.dev>
 *  
 *  Permission is hereby granted, free of charge, to any person obtaining a copy
 *  of this software and associated documentation files (the "Software"), to deal
 *  in the Software without restriction, including without limitation the rights
 *  to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 *  copies of the Software, and to permit persons to whom the Software is furnished
 *  to do so, subject to the following conditions:
 *  
 *  The above copyright notice and this permission notice shall be included in all
 *  copies or substantial portions of the Software.
 *  
 *  THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 *  IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 *  FITNESS FOR A PARTICULAR PURPOSE AND NON INFRINGEMENT. IN NO EVENT SHALL THE
 *  AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 *  LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 *  OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
 *  THE SOFTWARE.
 */

namespace BaksDev\Avito\Promotion\UseCase\NewEdit;

use BaksDev\Avito\Promotion\UseCase\NewEdit\Filter\AvitoPromotionFilterForm;
use BaksDev\Core\Services\Fields\FieldsChoice;
use BaksDev\Core\Services\Fields\FieldsChoiceInterface;
use BaksDev\Products\Category\Repository\CategoryChoice\CategoryChoiceInterface;
use BaksDev\Products\Category\Repository\PropertyFieldsCategoryChoice\ModificationCategoryProductSectionField\ModificationCategoryProductSectionFieldInterface;
use BaksDev\Products\Category\Repository\PropertyFieldsCategoryChoice\OffersCategoryProductSectionField\OffersCategoryProductSectionFieldInterface;
use BaksDev\Products\Category\Repository\PropertyFieldsCategoryChoice\PropertyFieldsCategoryChoiceInterface;
use BaksDev\Products\Category\Repository\PropertyFieldsCategoryChoice\VariationCategoryProductSectionField\VariationCategoryProductSectionFieldInterface;
use BaksDev\Products\Category\Type\Id\CategoryProductUid;
use BaksDev\Products\Category\Type\Section\Field\Id\CategoryProductSectionFieldUid;
use BaksDev\Users\Profile\UserProfile\Repository\UserProfileTokenStorage\UserProfileTokenStorageInterface;
use Doctrine\Common\Collections\ArrayCollection;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\CallbackTransformer;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;

final class AvitoPromotionForm extends AbstractType
{
    public function __construct(
        private readonly CategoryChoiceInterface $categoryChoice,
        private readonly OffersCategoryProductSectionFieldInterface $offersCategoryProductSectionField,
        private readonly ModificationCategoryProductSectionFieldInterface $modificationCategoryProductSectionField,
        private readonly PropertyFieldsCategoryChoiceInterface $propertyFields,
        private readonly VariationCategoryProductSectionFieldInterface $variationCategoryProductSectionField,
        private readonly UserProfileTokenStorageInterface $userProfileTokenStorage,
        private readonly FieldsChoice $fieldsChoice,
    ) {}

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {

        $builder->add('name', TextType::class, [
            'label' => false,
            'required' => true,
        ]);

        $builder->add('budget', IntegerType::class, [
            'attr' => ['max' => 100, 'min' => 1],
            'label' => false,
            'required' => true,
        ]);

        $builder->add('budgetLimit', IntegerType::class, [
            'attr' => ['max' => 1000, 'min' => 101],
            'label' => false,
            'required' => true,
        ]);

        $builder->add('dateEnd', DateType::class, [
            'widget' => 'single_text',
            'html5' => false,
            'label' => false,
            'format' => 'dd.MM.yyyy',
            'input' => 'datetime_immutable',
            'attr' => ['class' => 'js-datepicker'],
            'required' => true,
        ]);

        $builder
            ->add('category', ChoiceType::class, [
                'choices' => $this->categoryChoice
                    ->onlyActive()
                    ->findAll(),
                'choice_value' => function(?CategoryProductUid $type) {
                    return $type;
                },
                'choice_label' => function(CategoryProductUid $type) {
                    return $type->getOptions();
                },
                'label' => 'Категория',
                'expanded' => false,
                'multiple' => false,
                'required' => true,
            ]);

        $builder
            ->add('filters', CollectionType::class, [
                'entry_type' => AvitoPromotionFilterForm::class,
                'entry_options' => ['label' => false],
                'label' => false,
                'by_reference' => false,
                'allow_delete' => true,
                'allow_add' => true,
                'prototype_name' => '__filters__',
            ]);

        /** ******** Свойства элемента коллекции ******* */

        $builder->add('preProperty', HiddenType::class);

        $builder->get('preProperty')->addModelTransformer(
            new CallbackTransformer(
                function(?string $field) {
                    return $field instanceof CategoryProductSectionFieldUid ? $field->getValue() : $field;
                },
                function(?string $field) {
                    return $field ? new CategoryProductSectionFieldUid($field) : null;
                },
            ),
        );

        $builder->add('preValue', HiddenType::class);

        $builder->add('predicatePrototype', HiddenType::class);

        /** ******** События формы ******* */

        $builder->addEventListener(FormEvents::PRE_SET_DATA, function(FormEvent $event) use ($options) {

            /** @var AvitoPromotionDTO $avitoPromotionDTO */
            $avitoPromotionDTO = $event->getData();
            $avitoPromotionDTO->setProfile($this->userProfileTokenStorage->getProfile());

            if($avitoPromotionDTO->getCategory())
            {
                $category = $this->categoryChoice->category($avitoPromotionDTO->getCategory())
                    ->onlyActive()
                    ->find();

                if(false !== $category)
                {
                    $avitoPromotionDTO->setCategoryName($category->getOptions());
                }
            }

            // формируем массив для отображения описания элементов коллекции в шаблоне
            if(false === $avitoPromotionDTO->getFilters()->isEmpty())
            {

                if($category = $avitoPromotionDTO->getCategory())
                {
                    // находим свойства продукта по категории
                    $productProperties = $this->getProductProperties($category);

                    foreach($avitoPromotionDTO->getFilters() as $filter)
                    {
                        $filterProperty = (string) $filter->getProperty()->getValue();

                        // находим совпадение по идентификатору из фильтра
                        if($match = $productProperties->get($filterProperty))
                        {
                            $type = $this->fieldsChoice->getChoice($match->getProperty());

                            // формируем массив с информацией, необходимой для отображения в шаблон элемента коллекции
                            $result = [
                                'property_name' => $match->getAttr(),
                                'value' => $filter->getValue(),
                                'domain' => $type->domain(),
                                'predicate' => $filter->getPredicate(),
                                'type' => $match->getProperty(),
                            ];

                            // передаем мета инфо об элементе коллекции в DTO, делаем уникальный ключ
                            $avitoPromotionDTO->addFilterValues($filter->getValue(), $result);
                        }
                    }
                }

                $avitoPromotionDTO->setPredicatePrototype($avitoPromotionDTO->getFilters()->current()->getPredicate());
            }
        });

        $builder->get('preProperty')
            ->addEventListener(FormEvents::POST_SUBMIT, function(FormEvent $event) {

                $form = $event->getForm();
                $parent = $event->getForm()->getParent();

                if(!$parent)
                {
                    return;
                }

                /** @var AvitoPromotionDTO $parentData */
                $parentData = $parent->getData();

                /** @var CategoryProductUid|null $categoryProductUid */
                $categoryProductUid = $parent->get('category')->getData();

                if($categoryProductUid)
                {
                    /** Свойства продукта по категории */
                    $productProperties = $this->getProductProperties($categoryProductUid);

                    $parent->add('preProperty', ChoiceType::class, [
                        'choices' => $productProperties,
                        'choice_value' => function(?CategoryProductSectionFieldUid $property) {
                            return $property?->getValue();
                        },
                        'choice_label' => function(CategoryProductSectionFieldUid $property) {
                            return $property->getAttr();
                        },
                        //                        'label' => 'Свойство продукта',
                        'expanded' => false,
                        'multiple' => false,
                        'required' => false,
                    ]);


                    /** @var CategoryProductSectionFieldUid|null $CategoryProductSectionFieldUid */
                    $CategoryProductSectionFieldUid = $form->getData();

                    if($CategoryProductSectionFieldUid)
                    {

                        //                        $parentData->setPreProperty($CategoryProductSectionFieldUid);

                        $fields = $productProperties->filter(
                            function(CategoryProductSectionFieldUid $element) use ($CategoryProductSectionFieldUid) {

                                return $element->equals($CategoryProductSectionFieldUid);
                            },
                        );


                        if($fields->isEmpty())
                        {
                            return;
                        }

                        /** @var CategoryProductSectionFieldUid $fieldElement */
                        $fieldElement = $fields->current();

                        /** @var FieldsChoiceInterface $FieldChoice */
                        $FieldChoice = $this->fieldsChoice->getChoice($fieldElement->getProperty());

                        if($FieldChoice)
                        {
                            //                            $parentData->setPreValue($fieldElement->getProperty());

                            $form->getParent()->add(
                                'preValue',
                                $FieldChoice->form(),
                                [
                                    'label' => false,
                                    'required' => false,
                                    'mapped' => false,
                                ],
                            );

                            $form->getParent()->add('predicatePrototype', ChoiceType::class, [
                                'choices' => ['И' => 'AND', 'ИЛИ' => 'OR'],
                                'choice_value' => fn(?string $value) => $value ?? null,
                                'choice_label' => fn(string $key, string $value) => $value,
                                'empty_data' => 'OR',
                                'label' => false,
                                'expanded' => false,
                                'multiple' => false,
                                'required' => true,
                            ]);

                        }
                    }
                }
            });

        $builder->add('avito_promotion_add', SubmitType::class, [
            'label' => 'Save',
            'label_html' => true,
            'attr' => ['class' => 'btn-primary'],
        ]);
    }

    /**
     * @return ArrayCollection<CategoryProductSectionFieldUid>
     */
    private function getProductProperties(CategoryProductUid $productCategory): ArrayCollection
    {
        /**
         * Массив с элементами "свойства продукта"
         * @var list<CategoryProductSectionFieldUid> $productProperties
         */
        $productProperties = $this->propertyFields
            ->category($productCategory)
            ->getPropertyFieldsCollection();

        /** @var ArrayCollection<CategoryProductSectionFieldUid> $productFields */
        $productFields = new ArrayCollection();

        if($productProperties)
        {
            foreach($productProperties as $productProperty)
            {
                $productFields->set($productProperty->getValue(), $productProperty);
            }
        }

        /** Торговое предложение */
        $productOffer = $this->offersCategoryProductSectionField
            ->category($productCategory)
            ->findAllCategoryProductSectionField();

        /** Если нет свойств и ТП - отображаем пустой выпадающий список */
        if($productOffer === false && $productProperties === false)
        {
            $productFields->clear();

            return $productFields;
        }

        if($productOffer)
        {
            $productFields->set($productOffer->getValue(), $productOffer);

            /** Вариант торгового предложения */
            $productVariation = $this->variationCategoryProductSectionField
                ->offer($productOffer->getValue())
                ->findAllCategoryProductSectionField();

            if($productVariation)
            {
                $productFields->set($productVariation->getValue(), $productVariation);

                /** Модификация варианта торгового предложения */
                $productModification = $this->modificationCategoryProductSectionField
                    ->variation($productVariation->getValue())
                    ->findAllCategoryProductSectionField();

                if($productModification)
                {
                    $productFields->set($productModification->getValue(), $productModification);
                }
            }
        }

        return $productFields;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => AvitoPromotionDTO::class,
            'method' => 'POST',
            'attr' => ['class' => 'w-100'],
        ]);
    }
}
