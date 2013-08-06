<?php
/**
 * WordPress module navigation config
 *
 * You may not change or alter any portion of this comment or credits
 * of supporting developers from this source code or any supporting source code
 * which is considered copyrighted (c) material of the original comment or credit authors.
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
 *
 * @copyright       Copyright (c) http:www.eefocus.com
 * @license         http:www.xoopsengine.org/license New BSD License
 * @author          Lijun Dong <lijun@eefocus.com>
 * @since           1.0
 * @package         Module\WordPress
 */

return array(
    'meta'  => array(
        'wordpress' => array(
            'title'     => __('WordPress module navigation'),
            'section'   => 'front',
        ),
    ),
    'item'  => array(
        'admin' => array(
            'setup' => array(
                'label'         => __('Setup WordPress'),
                'route'         => 'admin',
                'controller'    => 'setup',
                'action'        => 'index',
            ),
            'config' => array(
                'label'         => __('Configure web server'),
                'route'         => 'admin',
                'controller'    => 'config',
                'action'        => 'index',
            ),
            'create' => array(
                'label'         => __('Create a blog'),
                'route'         => 'admin',
                'controller'    => 'blog',
                'action'        => 'index',
            ),
        ),
    ),
);