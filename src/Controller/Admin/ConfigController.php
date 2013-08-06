<?php
/**
 * WordPress module Config controller
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

class ConfigController extends ActionController
{
    public function indexAction()
    {
        //
    }

    public function apacheAction()
    {
        $installPath = $this->config('install-path');
        $blogPath = 'blog';

        $htaccess = <<<EOF
# Insert this line before rewrite rules of Pi route
RewriteRule ^{$blogPath}/(\w+)$ /{$installPath}/ [L]
RewriteRule ^{$blogPath}/(\w+)/(.*)$ /{$installPath}/$2 [L]
EOF;

        $this->view()->assign(array(
            'headline'  => __('Apache Config Snippet'),
            'conf'      => $htaccess,
        ));

        $this->view()->setTemplate('config-sample');
    }

    public function nginxAction()
    {
        $installPath = $this->config('install-path');
        $blogPath = 'blog';

        $conf = <<<EOF
    # Insert this line before rewrite rules of Pi route
    rewrite ^/{$blogPath}/(\w+)$ /{$installPath}/ last;
    rewrite ^/{$blogPath}/(\w+)/(.*)$ /{$installPath}/$2 last;
EOF;

        $this->view()->assign(array(
            'headline'  => __('Nginx Config Snippet'),
            'conf'      => $conf,
        ));

        $this->view()->setTemplate('config-sample');
    }
}