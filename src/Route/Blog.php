<?php
/**
 * WordPress module blog class
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

namespace Module\Wordpress\Route;

use Zend\Mvc\Router\Http\RouteMatch;
use Zend\Stdlib\RequestInterface as Request;
use Pi\Mvc\Router\Http\Standard;

class Blog extends Standard
{
    protected $keyValueDelimiter = '-';
    protected $prefix = '/blog';

    protected $defaults = array(
        'module'     => 'wordpress',
        'controller' => 'index',
        'action'     => 'index',
    );

    public function match(Request $request, $pathOffset = null)
    {
        $result = $this->canonizePath($request, $pathOffset);
        if (null === $result) {
            return null;
        }
        list($path, $pathLength) = $result;

        if (empty($path)) {
            return null;
        }

        $matches  = $pMatches = $cMatches = $vMatches = array();
        $pPattern = '|^p(\d+)$|';
        $tPattern = '|^tag-([^/]+)$|';

        $chunks = explode($this->paramDelimiter, trim($path, $this->paramDelimiter));
        $count = count($chunks);

        if ($count == 0) {
            //
        } else if (preg_match($tPattern, $chunks[0], $tMatches)) {
            $matches['controller']  = 'blog';
            $matches['action']      = 'tag';
            $matches['tag']         = $tMatches[1];

            if ($count > 1 && preg_match($pPattern, $chunks[1], $pMatches)) {
                $matches['p'] = (int) $pMatches[1] ?: 1;
            }
        } else {
            return null;
        }

        return new RouteMatch(array_merge($this->defaults, $matches), $pathLength);
    }

    public function assemble(array $params = array(), array $options = array())
    {
        $url = '';

        $mergedParams = array_merge($this->defaults, $params);
        if ($mergedParams) {
            if ($mergedParams['controller'] == 'index' && $mergedParams['action'] == 'index') {
                $url .= '';
            } else if ($mergedParams['controller'] == 'blog' && $mergedParams['action'] == 'tag'
                & isset($mergedParams['tag'])) {
                $url .= $this->paramDelimiter . 'tag' . $this->keyValueDelimiter . rawurlencode($mergedParams['tag']);
            }

            if (isset($mergedParams['p']) && $mergedParams['p'] > 1) {
                $url .= $this->paramDelimiter . 'p' . $mergedParams['p'];
            }
        }

        return $this->prefix . $url;
    }
}
