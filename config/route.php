<?php
/**
 * Wordpress module route config
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

return array(
    'wordpress' => array(
        'section'  => 'front',
        'priority' => 100,

        'type'     => 'Module\Wordpress\Route\Blog',
        'options'  => array(
            'prefix'                => '/blog',
            'key_value_delimiter'   => '-',
            'defaults'          => array(
                'module'        => 'wordpress',
                'controller'    => 'index',
                'action'        => 'index',
            ),
        ),
    ),
);