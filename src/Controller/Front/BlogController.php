<?php
/**
 * WordPress module blog controller
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

namespace Module\Wordpress\Controller\Front;

use Pi\Mvc\Controller\ActionController;
use Pi;
use Pi\Paginator\Paginator;
use Module\Wordpress\Service;
use Zend\View\Helper\Placeholder\Container\AbstractContainer;

class BlogController extends ActionController
{
    public function indexAction()
    {
        //
    }

    public function tagAction()
    {
        $tag    = Service::getParam($this, 'tag', '');
        $tag    = rawurldecode($tag);
        $page   = Service::getParam($this, 'p', 1);
        $page   = $page > 0 ? $page : 1;

        if (empty($tag)) {
            return $this->jumpTo404(__('Cannot find this page'));
        }

        $limit  = $this->config('page_limit_front_tagged_posts') ?: 40;
        $limit  = 2;
        list($posts, $totalCount) = Service::wpTaggedPosts($tag, $page, $limit);

        // Pagination
        $paginator = Paginator::factory((int) $totalCount);
        $paginator->setItemCountPerPage($limit)
            ->setCurrentPageNumber($page)
            ->setUrlOptions(array(
                'pageParam' => 'p',
                'router'    => $this->getEvent()->getRouter(),
                'route'     => $this->getEvent()->getRouteMatch()->getMatchedRouteName(),
                'params'    => array(
                    'module'        => $this->getModule(),
                    'controller'    => 'blog',
                    'action'        => 'tag',
                    'tag'           => $tag,
                ),
            ));

        $this->view()->assign(array(
            'title'     => __('Posts with tag'),
            'posts'     => $posts,
            'paginator' => $paginator,
            'p'         => $page,
            'tag'       => $tag,
//            'seo'       => $this->setupSeo($tag),
        ));
    }
}