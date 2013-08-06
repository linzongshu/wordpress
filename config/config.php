<?php
/**
 * WordPress module config config
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
    'category' => array(
        array(
            'name'  => 'general',
            'title' => 'General',
        ),
    ),
    'item' => array(
        'install-path' => array(
            'category'      => 'general',
            'title'         => 'Install path',
            'description'   => 'The path where WordPress installs',
            'value'         => 'wordpress',
            'edit'          => array(
                'type'      => 'text',
                'attributes'   => array(
                    'readonly'  => 'readonly',
                ),
            ),
        ),
        'installed' => array(
            'category'      => 'general',
            'title'         => 'Installed',
            'description'   => 'Whether WordPress has been installed, and prevent from being installed again',
            'filter'        => 'number_int',
            'value'         => 0,
            'edit'          => array(
                'type'      => 'text',
                'attributes'   => array(
                    'readonly'  => 'readonly',
                ),
            ),
        ),
        'page_limit_front_tagged_posts' => array(
            'category'    => 'general',
            'title'       => 'Tagged posts per page',
            'description' => 'Maximum count of tagged posts in a front page.',
            'value'       => 40,
            'filter'      => 'number_int',
        ),
    ),
);