<?php
/**
 * WordPress module event config
 *
 * You may not change or alter any portion of this comment or credits
 * of supporting developers from this source code or any supporting source code
 * which is considered copyrighted (c) material of the original comment or credit authors.
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
 *
 * @copyright       Copyright (c) Pi Engine http://www.xoopsengine.org
 * @license         http://www.xoopsengine.org/license New BSD License
 * @author          Lijun Dong <lijun@eefocus.com>
 * @since           3.0
 * @package         Module\Wordpress
 * @version         $Id$
 */

return array(
    // Event list
    'events'    => array(
    ),
    // Listener list
    'listeners' => array(
        array(
            // event info: module, event name
            'event'     => array('system', 'login'),
            // listener info: class, method
            'listener'  => array('event', 'login'),
        ),
        array(
            'event'     => array('system', 'logout'),
            'listener'  => array('event', 'logout'),
        ),
        array(
            'event'     => array('system', 'activate'),
            'listener'  => array('event', 'activate'),
        ),
    ),
);
