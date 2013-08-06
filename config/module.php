<?php
/**
 * WordPress module config
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
    // Module meta
    'meta'  => array(
        // Module title, required
        'title'         => __('WordPress'),
        // Description, for admin, optional
        'description'   => __('Module to integrate WordPress with Pi.'),
        // Version number, required
        'version'       => '0.91',
        // Distribution license, required
        'license'       => 'New BSD',
        // Logo image, for admin, optional
        'logo'          => 'image/logo.png',
        // Readme file, for admin, optional
        'readme'        => 'docs/readme.txt',
        // Direct download link, available for wget, optional
        //'download'      => 'http://dl.xoopsengine.org/module/demo',
        // Demo site link, optional
        'clonable'      => false,
    ),
    // Author information
    'author'    => array(
        // Author full name, required
        'name'      => 'Lijun Dong',
        // Email address, optional
        'email'     => 'lijun@eefocus.com',
        // Website link, optional
        'website'   => 'http://www.eefocus.com',
        // Credits and aknowledgement, optional
        'credits'   => 'EEFOCUS Team.'
    ),
    // Module dependency: list of module directory names, optional
    'dependency'    => array(
    ),
    // Maintenance actions
    'maintenance'   => array(
        // Class for module maintenace
        // Methods for action event:
        //  preInstall, install, postInstall;
        //  preUninstall, uninstall, postUninstall;
        //  preUpdate, update, postUpdate
        //'class' => 'Module\\Demo\\Maintenance',

        // resource
        'resource' => array(
            // Database meta
            'database'  => array(
                // SQL schema/data file
            ),
            // Module configs
            'config'    => 'config.php',
            // Block definition
            //'block'     => 'block.php',
            // Bootstrap, priority
            'bootstrap' => 1,
            // Event specs
            'event'     => 'event.php',
            // Search registry, 'class:method'
            //'search'    => array('callback' => array('search', 'index')),
            // View pages
            //'page'      => 'page.php',
            // ACL specs
            //'acl'       => 'acl.php',
            // Navigation definition
            'navigation'    => 'navigation.php',
            // Routes, first in last out; bigger priority earlier out
            'route'     => 'route.php',
            'monitor'   => array('callback' => array('monitor', 'index')),
//            'page'      => 'page.php',
        )
    )
);