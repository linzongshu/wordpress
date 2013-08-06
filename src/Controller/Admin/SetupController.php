<?php
/**
 * WordPress module Setup controller
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
use Pi\Mvc\Controller\ActionController;
use Pi;
use Module\Wordpress\Service;
use Module\Wordpress\Form\SiteInfoForm;
use Module\Wordpress\Form\SiteInfoFilter;
use Zend\Config\Factory as ConfigFactory;

class SetupController extends ActionController
{
    protected function getSiteInfoForm()
    {
        $form = new SiteInfoForm();

        $form->setAttributes(array(
            'action'  => $this->url('', array('action' => 'install')),
            'method'  => 'post',
            'class'   => 'form-horizontal',
        ));

        return $form;
    }

    protected function getDefaultValues()
    {
        $data = array(
            'install_path'  => $this->config('install-path'),
            'blog_title'    => '',
            'site_url'      => '',
            'db_name'       => '',
            'db_user'       => '',
            'db_password'   => '',
            'db_host'       => '',
            'table_prefix'  => 'wp_',
        );

        $dbConfig = ConfigFactory::fromFile(Pi::path('var/config/service.database.php'));
        if (isset($dbConfig['connection']) && isset($dbConfig['connection']['dsn'])) {
            $dsn = Service::parseDSN($dbConfig['connection']['dsn']);

            if (isset($dsn['dbname'])) {
                $data['db_name'] = $dsn['dbname'];
            }

            if (isset($dsn['host'])) {
                $data['db_host'] = $dsn['host'];
            }

            if (isset($dsn['username'])) {
                $data['db_user'] = $dsn['username'];
            } else if (isset($dbConfig['connection']['username'])) {
                $data['db_user'] = $dbConfig['connection']['username'];
            }

            if (isset($dsn['password'])) {
                $data['db_password'] = $dsn['password'];
            } else if (isset($dbConfig['connection']['password'])) {
                $data['db_password'] = $dbConfig['connection']['password'];
            }
        }

        if (isset($dbConfig['table_prefix'])) {
            $data['table_prefix'] = $dbConfig['table_prefix'] . $data['table_prefix'];
        }

        $data['site_url'] = Pi::url('/');//Pi::url($data['install_path']);

        return $data;
    }

    public function indexAction()
    {
        if (!$this->config('installed')) {
            return $this->redirect()->toRoute('', array('action' => 'prepare'));
        }

        $this->view()->assign(array(
            'install_path'      => $this->config('install-path'),
            'uninstall_alert'   => __("Do you realy want to uninstall WordPress? This will lose all blog data."),
            'blog_url'          => Pi::url('/blog/' . Pi::registry('user')->identity . '/'),
        ));
    }

    public function prepareAction()
    {
        if ($this->config('installed')) {
            return $this->redirect()->toRoute(array('action' => 'index'));
        }

        $form = $this->getSiteInfoForm();
        $form->setData($this->getDefaultValues());

        $this->view()->assign(array(
            'title' => __('Install WordPress'),
            'form'  => $form,
        ));
        $this->view()->setTemplate('setup-site-info');
    }

    public function installAction()
    {
        if ($this->config('installed')) {
            return $this->redirect()->toRoute(array('action' => 'index'));
        }

        if (!$this->request->isPost()) {
            return $this->redirect()->toRoute('', array('action' => 'prepare'));
        }

        $message    = null;
        $successful = false;

        $form = $this->getSiteInfoForm();
        $form->setInputFilter(new SiteInfoFilter);
        $form->setData($this->request->getPost());

        if (!$form->isValid()) {
            $this->view()->assign(array(
                'title' => __('Install WordPress'),
                'form'  => $form,
            ));
            return $this->view()->setTemplate('setup-site-info');
        }

        $data = $form->getData();

        if (empty($data['site_url'])) {
            $data['site_url'] = Pi::url('www/' . $data['install_path']);
        }

        // Save install_path as module parameter
        if (strcmp($data['install_path'], $this->config('install-path')) !== 0) {
            Service::piUpdateModuleConfig('install-path', $data['install_path']);
        }

        $data['install_user'] = Pi::registry('user')->identity;

        // XDebug may output exception messages to invalidate the setcookie
        // So we disable it explicitly
        if (function_exists('xdebug_disable')) {
            xdebug_disable();
        }

        try {
            // Deploy source code
            Service::wpDeploy(Service::wpDir());

            // Create the config file
            Service::wpConfigure($data);

            // Install site
            Service::wpInstallWordPress($data['blog_title'], Pi::registry('user')->identity, Pi::registry('user')->email, $data['site_url']);

            // Set a flag to mark installing
            Service::piUpdateModuleConfig('installed', 1);

            // Make current user logged in
            Service::wpSetAuthCookie(Pi::registry('user')->identity);

            $message = __('WordPress has been installed successfully.');
            $successful = true;
        } catch (\Exception $e) {
            $message = $e->getMessage();
        }

        $this->view()->assign(array(
            'message'       => $message,
            'successful'    => $successful,
        ));
        $this->view()->setTemplate('setup-error');
    }

    public function uninstallAction()
    {
        Service::wpUninstallWordPress();

        $this->view()->assign(array(
            'message'       => __('WordPress has been uninstalled.'),
            'successful'    => true,
        ));
        $this->view()->setTemplate('setup-error');
    }

    function testAction()
    {
//        Service::wpInstallBlog('M-a-n-a-g-e-r', 'For test purpose', 2);
//        Service::wpSetAuthCookie(1);
//        Service::wpLogout();
//        Pi::service('event')->trigger(array('system', 'login'), Pi::registry('user')->identity);
        exit();
    }
}
