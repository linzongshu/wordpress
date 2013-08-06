<?php
/**
 * WordPress module event observer class
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
 * @package         Module/Wordpress
 * @version         $Id$
 */

namespace Module\Wordpress;

class Event
{
    /**
     * Listener to handle system::login or user::login event
     *
     * @param $data Expected as array((string) $identity, (bool) $remember);
     * @param $module
     */
    public static function login($data, $module)
    {
        $user_name = '';
        $remember = false;

        if (is_array($data)) {
            list($user_name, $remember, ) = $data;
        } else if (is_string($data)) {
            $user_name = $data;
        }

        Service::wpSetAuthCookie($user_name, $remember);
    }

    /**
     * Listener to handle system::logout or user::logout event
     *
     * @param $data Expected as $identity, but not use now
     * @param $module
     */
    public static function logout($data, $module)
    {
        Service::wpLogout();
    }

    /**
     * Listener to handle system::activate or user::activate event
     *
     * @param $data Expected as array((string) $blogName, (string) $blogDescription, (int) $piUser)
     * @param $module
     */
    public static function activate($data, $module)
    {
        $blogName = $blogDescription = $piUser = null;

        if (is_array($data)) {
            list($blogName, $blogDescription, $piUser, ) = $data;
        } else if (is_string($data)) {
            $blogName = $data;
        }

        Service::wpInstallBlog($blogName, $blogDescription, $piUser);
    }
}
