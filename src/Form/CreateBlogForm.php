<?php
/**
 * WordPress module CreateBlogForm form
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

class CreateBlogForm extends BaseForm
{
    public function init()
    {
        $this->add(array(
            'name'  => 'identity',
            'options' => array(
                'label' => 'Member name',
            ),
            'attributes'    => array(
                'id'   => 'identity',
                'type' => 'text',
            ),
        ));

        $this->add(array(
            'name'  => 'name',
            'options' => array(
                'label' => 'Blog name',
            ),
            'attributes'    => array(
                'id'   => 'name',
                'type' => 'text',
            ),
        ));

        $this->add(array(
            'name'  => 'description',
            'options' => array(
                'label' => 'Blog description',
            ),
            'attributes'    => array(
                'id'   => 'description',
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