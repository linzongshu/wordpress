<?php
/**
 * WordPress module SiteInfoForm form
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
 * @package         Module\WordPress
 */

namespace Module\WordPress\Form;

use Pi;
use Pi\Form\Form as BaseForm;

class SiteInfoForm extends BaseForm
{
    public function init()
    {
        $this->add(array(
            'name'       => 'install_path',
            'options'    => array(
                'label' => __('Install Path'),
            ),
            'attributes' => array(
                'id'   => 'install_path',
                'type' => 'text',
            ),
        ));

        $this->add(array(
            'name' => 'blog_title',
            'options' => array(
                'label' => 'Blog Title',
            ),
            'attributes' => array(
                'id'   => 'blog_title',
                'type' => 'text',
            ),
        ));

        $this->add(array(
            'name' => 'site_url',
            'options' => array(
                'label' => 'Site URL',
            ),
            'attributes' => array(
                'id'   => 'site_url',
                'type' => 'text',
            ),
        ));

        $this->add(array(
            'name'  => 'db_name',
            'options' => array(
                'label' => 'Database Name',
            ),
            'attributes' => array(
                'id'   => 'db_name',
                'type' => 'text',
            ),
        ));

        $this->add(array(
            'name'  => 'db_user',
            'options' => array(
                'label' => 'User Name',
            ),
            'attributes' => array(
                'id'   => 'db_user',
                'type' => 'text',
            ),
        ));

        $this->add(array(
            'name'  => 'db_password',
            'options' => array(
                'label' => 'Password',
            ),
            'attributes' => array(
                'id'   => 'db_password',
                'type' => 'text',
            ),
        ));

        $this->add(array(
            'name'  => 'db_host',
            'options' => array(
                'label' => 'Database Host',
            ),
            'attributes' => array(
                'id'   => 'db_host',
                'type' => 'text',
            ),
        ));

        $this->add(array(
            'name'  => 'table_prefix',
            'options' => array(
                'label' => 'Table Prefix',
            ),
            'attributes'    => array(
                'id'   => 'table_prefix',
                'type' => 'text',
            ),
        ));

        $this->add(array(
            'name'          => 'submit',
            'attributes'    => array(
                'value' => __('Submit'),
            ),
            'type'  => 'submit',
        ));
    }
}