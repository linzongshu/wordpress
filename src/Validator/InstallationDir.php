<?php
/**
 * WordPress module InstallationDir validator
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

namespace Module\WordPress\Validator;

use Pi;
use Zend\Validator\AbstractValidator;

class InstallationDir extends AbstractValidator
{
    const INVALID   = 'dirNameInvalid';
    const RESERVED  = 'dirNameReserved';
    const EXISTS    = 'dirNameExists';

    protected $messageTemplates = array(
        self::INVALID   => 'Invalid directory name. Only alphabetic, number and underscore are allowed',
        self::RESERVED  => 'Directory name is reserved',
        self::EXISTS    => 'Directory exists. Please try others',
    );

    protected $options = array(
        'pattern'   => '|^\w+$|',
        'reserved' => array(),
    );

    public function isValid($value, $context = null)
    {
        $this->setValue($value);

        if (!preg_match($this->getOption('pattern'), $value)) {
            $this->error(self::INVALID);
            return false;
        }

        if (array_search($value, $this->getOption('reserved')) !== false) {
            $this->error(self::RESERVED);
            return false;
        }

        if (file_exists(Pi::path('www/' . $value))) {
            $this->error(self::EXISTS);
            return false;
        }

        return true;
    }
}
