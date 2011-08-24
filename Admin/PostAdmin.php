<?php

/*
 * This file is part of the Sonata package.
 *
 * (c) Thomas Rabaix <thomas.rabaix@sonata-project.org>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Sonata\NewsBundle\Admin;

use Sonata\AdminBundle\Admin\Admin;
use Sonata\AdminBundle\Form\FormMapper;
use Sonata\AdminBundle\Datagrid\DatagridMapper;
use Sonata\AdminBundle\Datagrid\ListMapper;
use Sonata\AdminBundle\Show\ShowMapper;

use Knp\Bundle\MenuBundle\MenuItem;

use Application\Sonata\NewsBundle\Entity\Comment;

class PostAdmin extends Admin
{
    protected $userManager;

    protected function configureShowField(ShowMapper $showMapper)
    {
        $showMapper
            ->add('author')
            ->add('enabled')
            ->add('title')
            ->add('abstract')
            ->add('content')
            ->add('tags')
        ;
    }

    protected function configureFormFields(FormMapper $formMapper)
    {
        $formMapper
            ->with('General')
                ->add('enabled', null, array('required' => false))
                ->add('author', 'sonata_type_model', array(), array('edit' => 'list'))
                ->add('title')
                ->add('abstract')
                ->add('content')
            ->end()
            ->with('Tags')
                ->add('tags', 'sonata_type_model', array('expanded' => true))
            ->end()
            ->with('Options', array('collapsed' => true))
                ->add('commentsCloseAt')
                ->add('commentsEnabled', null, array('required' => false))
                ->add('commentsDefaultStatus', 'choice', array('choices' => Comment::getStatusList()))
            ->end()
        ;
    }

    protected function configureListFields(ListMapper $listMapper)
    {
        $listMapper
            ->addIdentifier('title')
            ->add('author')
            ->add('enabled')
            ->add('tags')
            ->add('commentsEnabled')
        ;
    }

    protected function configureDatagridFilters(DatagridMapper $datagridMapper)
    {
//        return;
        $datagridMapper
            ->add('title')
            ->add('enabled')
            ->add('tags', null, array('field_options' => array('expanded' => true, 'multiple' => true)))
            ->add('author')
            ->add('with_open_comments', 'doctrine_orm_callback', array(
//                'callback'   => array($this, 'getWithOpenCommentFilter'),
                'callback' => function($queryBuilder, $alias, $field, $value) {
                    if (!$value) {
                        return;
                    }

                    $queryBuilder->leftJoin(sprintf('%s.comments', $alias), 'c');
                    $queryBuilder->andWhere('c.status = :status');
                    $queryBuilder->setParameter('status', Comment::STATUS_MODERATE);
                },
                'field_type' => 'checkbox'
            ))
        ;
    }

    public function getWithOpenCommentFilter($queryBuilder, $alias, $field, $value)
    {
        if (!$value) {
            return;
        }

        $queryBuilder->leftJoin(sprintf('%s.comments', $alias), 'c');
        $queryBuilder->andWhere('c.status = :status');
        $queryBuilder->setParameter('status', Comment::STATUS_MODERATE);
    }

    public function preInsert($post)
    {
        parent::preInsert($post);

        if (isset($this->formFieldDescriptions['author'])) {
            $this->getUserManager()->updatePassword($post->getAuthor());
        }
    }

    public function preUpdate($post)
    {
        parent::preUpdate($post);

        if (isset($this->formFieldDescriptions['author'])) {
            $this->getUserManager()->updatePassword($post->getAuthor());
        }
    }

    protected function configureSideMenu(MenuItem $menu, $action, Admin $childAdmin = null)
    {
        if (!$childAdmin && !in_array($action, array('edit'))) {
            return;
        }

        $admin = $this->isChild() ? $this->getParent() : $this;

        $id = $admin->getRequest()->get('id');

        $menu->addChild(
            $this->trans('view_post'),
            $admin->generateUrl('edit', array('id' => $id))
        );

        $menu->addChild(
            $this->trans('link_view_comment'),
            $admin->generateUrl('sonata.news.admin.comment.list', array('id' => $id))
        );
    }

    public function setUserManager($userManager)
    {
        $this->userManager = $userManager;
    }

    public function getUserManager()
    {
        return $this->userManager;
    }
}