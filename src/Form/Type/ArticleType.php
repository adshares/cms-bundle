<?php

namespace Adshares\CmsBundle\Form\Type;

use Adshares\CmsBundle\Entity\ArticleTag;
use Adshares\CmsBundle\Entity\ArticleType as ArticleTypeEnum;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\Extension\Core\Type\EnumType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\File;

class ArticleType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $isArticle = ArticleTypeEnum::Article === $options['data']->getType();
        $isFAQ = ArticleTypeEnum::FAQ === $options['data']->getType();

        $builder
            ->add('type', EnumType::class, [
                'class' => ArticleTypeEnum::class,
            ])
            ->add('tags', EnumType::class, [
                'multiple' => true,
                'expanded' => true,
                'class' => ArticleTag::class,
                'label_attr' => ['class' => 'pt-0'],
            ])
            ->add('startAt', DateTimeType::class, [
                'widget' => 'single_text',
                'with_seconds' => true,
                'disabled' => $isFAQ,
            ])
            ->add('endAt', DateTimeType::class, [
                'required' => false,
                'widget' => 'single_text',
                'with_seconds' => true,
                'disabled' => $isArticle || $isFAQ,
            ])
            ->add('title', TextType::class)
            ->add('name', TextType::class, [
                'label' => 'Name (URL)',
            ])
            ->add('no', IntegerType::class, [
                'disabled' => $isArticle,
                'label' => 'No.'
            ])
            ->add('image', FileType::class, [
                'mapped' => false,
                'required' => false,
                'constraints' => [
                    new File([
                        'maxSize' => '1024k',
                        'mimeTypes' => [
                            'image/gif',
                            'image/jpeg',
                            'image/png',
                        ],
                        'mimeTypesMessage' => 'Please upload a valid image',
                    ])
                ],
            ])
            ->add('video', TextType::class, [
                'required' => false,
            ])
            ->add('content', TextareaType::class, [
                'attr' => ['rows' => 40],
            ])
            ->add('save', SubmitType::class, ['label' => $options['edit_mode'] ? 'Save article' : 'Create article']);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'csrf_protection' => true,
            'edit_mode' => false,
        ]);
    }
}
