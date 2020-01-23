<?php

namespace Wf\Bundle\CmsBaseBundle\Form\Type;

use Symfony\Component\Form\Extension\Core\ChoiceList\ObjectChoiceList;
use Symfony\Component\Form\Extension\Core\ChoiceList\SimpleChoiceList;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;
use Symfony\Component\Form\FormBuilderInterface;
use Wf\Bundle\CmsBaseBundle\Entity\UserGroup;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use FOS\UserBundle\Model\GroupManager;
use Symfony\Component\Form\AbstractType;

class ArticleMailType extends AbstractType
{
    public function getName()
    {
        return 'wf_cms_article_mail';
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('sender_name', 'text', array(
                'label' => 'mail.form.sender_name',
                'translation_domain' => 'WfCms',
                'required' => true,
            ))
            ->add('receiver_email', 'email', array(
                'label' => 'mail.form.receiver_email',
                'translation_domain' => 'WfCms',
                'required' => true,
            ))
            ->add('comment', 'textarea', array(
                'max_length' => 150,
                'required' => false,
            ))
            ->add('article_id', 'hidden', array(
                'required' => false,
            ))
            ;
    }

    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults(array(
            'csrf_protection' => false,
        ));
    }

}
