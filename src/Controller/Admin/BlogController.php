<?php
/**
 * WordPress module Blog controller
 *
 * You may not change or alter any portion of this comment or credits
 * of supporting developers from this source code or any supporting source code
 * which is considered copyrighted (c) material of the original comment or credit authors.
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
 *
 * @copyright       Copyright (c) http://www.eefocus.com
 * @license         http://www.xoopsengine.org/license New BSD License
 * @author          Lijun Dong <lijun@eefocus.com>
 * @since           1.0
 * @package         Module\Wordpress
 */

namespace Module\Wordpress\Controller\Admin;
use Module\Wordpress\Form\CreateBlogFilter;
use Module\WordPress\Form\CreateBlogForm;
use Pi\Mvc\Controller\ActionController;
use Pi;
use Module\Wordpress\Service;

class BlogController extends ActionController
{
    protected function getCreateBlogForm()
    {
        $form = new CreateBlogForm();

        $form->setAttributes(array(
            'action'  => $this->url('', array('action' => 'create')),
            'method'  => 'post',
            'class'   => 'form-horizontal',
        ));

        return $form;
    }

    public function indexAction()
    {
        if (!$this->config('installed')) {
            return $this->redirect()->toRoute(array('action' => 'index', 'controller' => 'setup'));
        }

        $form = $this->getCreateBlogForm();
        $form->setData(array(
            'name'          => __('My Blog'),
            'description'   => __('Another WordPress blog site.'),
        ));

        $this->view()->assign(array(
            'title' => __('Create a blog'),
            'form'  => $form,
        ));
        $this->view()->setTemplate('blog-create-info');
    }

    public function createAction()
    {
        if (!$this->config('installed')) {
            return $this->redirect()->toRoute(array('action' => 'index', 'controller' => 'setup'));
        }

        if (!$this->request->isPost()) {
            return $this->redirect()->toRoute('', array('action' => 'index'));
        }

        $message    = null;
        $successful = false;

        $form = $this->getCreateBlogForm();
        $form->setInputFilter(new CreateBlogFilter());
        $form->setData($this->request->getPost());

        if (!$form->isValid()) {
            $this->view()->assign(array(
                'title' => __('Create a blog'),
                'form'  => $form,
            ));
            return $this->view()->setTemplate('blog-create-info');
        }

        $data = $form->getData();

        if (isset($data['identity']) && ($user = Pi::model('user')->find($data['identity'], 'identity'))) {
            $successful = Service::wpInstallBlog($data['name'], $data['description'], $user->id);
            $message = $successful ? sprintf(__('Create blog successfully, <a href="%s" target="_blank">visit now</a>'), Pi::url('/blog/' . $data['identity'] . '/')) : __('Failed to create blog.');
        } else {
            $message = __('Invalid user identity');
        }

        $this->view()->assign(array(
            'message'       => $message,
            'successful'    => $successful,
        ));

        return $this->view()->setTemplate('setup-error');
    }
}