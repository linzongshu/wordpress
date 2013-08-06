<?php
/**
 * WordPress module service api
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

namespace Module\Wordpress;

use Pi;
use Pi\Mvc\Controller\ActionController;

class Service
{
    protected static $module = 'wordpress';

    public static function getParam(ActionController $handler = null, $param = null, $default = null)
    {
        // Route parameter first
        $result = $handler->params()->fromRoute($param);

        // Then query string
        if (is_null($result) || '' === $result) {
            $result = $handler->params()->fromQuery($param);

            // Then post data
            if (is_null($result) || '' === $result) {
                $result = $handler->params()->fromPost($param);

                if (is_null($result) || '' === $result) {
                    $result = $default;
                }
            }
        }

        return $result;
    }

    public static function wpDir()
    {
        // Pi::service('module')->config() always caches config, we cannot use it
        $moduleConfig = Pi::service('registry')->config->read(static::$module);

        return Pi::path('www/' . $moduleConfig['install-path']);
    }

    public static function wpLoad()
    {
        if (!defined('WP_ADMIN')) {
            define('WP_ADMIN', true);
        }

        require_once(static::wpDir() . DIRECTORY_SEPARATOR . 'wp-load.php');
    }

    public static function wpDeploy($targetDir)
    {
        $originalDir = Pi::service('module')->path(static::$module . '/data/wp-original');

        if (file_exists($targetDir)) {
            throw new \Exception(sprintf(__('%s exists, please remove it and try again.'), $targetDir));
        }

        Pi::service('file')->mirror($originalDir, $targetDir);

        return $originalDir;
    }

    public static function wpConfigure($config)
    {
        // If config exists, exit this setup
        $wpDir = static::wpDir();
        $configFilePath = $wpDir . DIRECTORY_SEPARATOR . 'wp-config.php';
        if (file_exists($configFilePath)) {
            throw new \Exception(sprintf(__('%s exists, please remove it and try again.'), $configFilePath));
        }

        // If sample config doesn't exist, exit this setup
        $sampleFilePath = $wpDir . DIRECTORY_SEPARATOR . 'wp-config-sample.php';
        if (!file_exists($sampleFilePath)) {
            throw new \Exception(__('Sorry, I need a wp-config-sample.php file to work from. Please re-upload this file from your WordPress installation.'));
        }

        $sampleFile = file($sampleFilePath);

        define('DB_NAME', $config['db_name']);
        define('DB_HOST', $config['db_host']);
        define('DB_USER', $config['db_user']);
        define('DB_PASSWORD', $config['db_password']);

        $prefix = $config['table_prefix'];

        if (empty($config['db_name']) || empty($config['db_host']) || empty($config['db_user']) || empty($config['table_prefix'])) {
            throw new \Exception(__('Missing database parameters'));
        }

        // Generate secret keys
        $secret_keys = array();
        for ($i = 0; $i < 8; $i++) {
            $secret_keys[] = static::wpGeneratePassword(64, true, true);
        }

        $key = 0;
        $lines = array();
        $authKey = '';
        foreach ($sampleFile as $line_num => $line) {
            $continue = false;

            if ('$table_prefix  =' == substr($line, 0, 16)) {
                $lines[] = '$table_prefix  = \'' . addcslashes($prefix, "\\'") . "';\r\n";
                continue;
            }

            if (!preg_match('/^define\(\'([A-Z_]+)\',([ ]+)/', $line, $match)) {
                $lines[] = $line;
                continue;
            }

            $constant = $match[1];
            $padding  = $match[2];

            switch ($constant) {
                case 'DB_NAME'     :
                case 'DB_USER'     :
                case 'DB_PASSWORD' :
                case 'DB_HOST'     :
                    $lines[] = "define('" . $constant . "'," . $padding . "'" . addcslashes(constant($constant), "\\'") . "');\r\n";
                    $continue = true;
                    break;
                case 'AUTH_KEY'         :
                    // We'll use the key to create COOKIEHASH
                    $authKey = $secret_keys[$key];
                case 'SECURE_AUTH_KEY'  :
                case 'LOGGED_IN_KEY'    :
                case 'NONCE_KEY'        :
                case 'AUTH_SALT'        :
                case 'SECURE_AUTH_SALT' :
                case 'LOGGED_IN_SALT'   :
                case 'NONCE_SALT'       :
                    $lines[] = "define('" . $constant . "'," . $padding . "'" . $secret_keys[$key++] . "');\r\n";
                    $continue = true;
                    break;
            }

            if ($continue) {
                continue;
            }

            $lines[] = $line;

            // Insert constant WP_DIR_IN_PI_WWW
            if ($constant == 'WP_DEBUG') {
                $lines[] = "\r\n";
                $lines[] = '/**#@+' . "\r\n";
                $lines[] = ' * Hacked for Pi' . "\r\n";
                $lines[] = ' * 1) Define where WordPress is installed in' . "\r\n";
                $lines[] = ' * 2) Define cookie paths' . "\r\n";
                $lines[] = ' */' . "\r\n";
                $lines[] = "define('WP_ROUTE_TOKEN'," . $padding . "'blog');\r\n";
                $lines[] = "\r\n";
                $lines[] = "define('WP_DIR_IN_PI_WWW'," . $padding . "'" . $config['install_path'] . "');\r\n";
                $lines[] = "\r\n";
                $lines[] = "define('PI_DEFAULT_USER'," . $padding . "'" . $config['install_user'] . "');\r\n";
                $lines[] = "\r\n";
                $lines[] = "define('COOKIEHASH'," . $padding . "'" . md5($authKey) . "');\r\n";
                $lines[] = "define('COOKIEPATH', '/');\r\n";
                $lines[] = "define('SITECOOKIEPATH', '/');\r\n";
                $lines[] = "define('ADMIN_COOKIE_PATH', '/');\r\n";
                $lines[] = "define('PLUGINS_COOKIE_PATH', '/');\r\n";
                $lines[] = '/**#@-*/' . "\r\n";
                $lines[] = "\r\n";
            }
        }
        unset($line);
        unset($sampleFile);

        if (!is_writable($wpDir)) {
            throw new \Exception(sprintf(__('%s is not writable.'), $wpDir));
        } else {
            $handle = fopen($configFilePath, 'w');
            foreach ($lines as $line) {
                fwrite($handle, $line);
            }
            fclose($handle);
            chmod($configFilePath, 0666);
        }
    }

    public static function wpInstallWordPress($blogTitle, $userName = '', $userEmail = '', $siteurl = '')
    {
        define('WP_INSTALLING', true);
        // Define this constant to prevent redeclare error
        define('WP_ADMIN', true);

        $wpDir = static::wpDir();
        require_once($wpDir . DIRECTORY_SEPARATOR . 'wp-load.php');
        require_once($wpDir . DIRECTORY_SEPARATOR . 'wp-admin/includes/upgrade.php');
        require_once($wpDir . DIRECTORY_SEPARATOR . 'wp-includes/wp-db.php');

        if (is_blog_installed()) {
            throw new \Exception(__('You appear to have already installed WordPress. To reinstall please clear your old database tables first.'));
        }

        // Prepare site info, the following parameters should be configurable
        $weblog_title = $blogTitle;
        $user_name = $userName ?: Pi::registry('user')->identity;
        $admin_password = md5(microtime());
        $admin_email = $userEmail ?: Pi::registry('user')->email;
        $public = 1;

        // Links in first blog will use this constant
        $moduleConfig = Pi::service('registry')->config->read(static::$module);
        define('WP_SITEURL', $siteurl ?: Pi::url('www/' . $moduleConfig['install-path']));

        $result = wp_install($weblog_title, $user_name, $admin_email, $public, '', $admin_password);

        // Set db version explicitly
        update_option('db_version', $wp_db_version);

        return $result;
    }

    public static function wpUninstallWordPress()
    {
        global $table_prefix, $wpdb;

        // Define this constant to prevent redeclare error
        define('WP_ADMIN', true);

        $wpDir = static::wpDir();
        require_once($wpDir . DIRECTORY_SEPARATOR . 'wp-load.php');
        require_once($wpDir . DIRECTORY_SEPARATOR . 'wp-admin/includes/upgrade.php');
        require_once($wpDir . DIRECTORY_SEPARATOR . 'wp-includes/wp-db.php');

        // Delete tables first
        // Use show tables could be easy, but also be dangerous, so we use a predefined table list
        $dropTables = array(
            'commentmeta',
            'comments',
            'links',
            'options',
            'postmeta',
            'posts',
            'terms',
            'term_relationships',
            'term_taxonomy',
            'usermeta',
            'users',
        );
        $dropSql = 'DROP TABLE IF EXISTS `%s%s`;';

        foreach ($dropTables as $key => $val) {
            $wpdb->query(sprintf($dropSql, $table_prefix, $val));
        }

        // Remove source code
        Pi::service('file')->remove($wpDir);

        // Set flag to uninstalled
        static::piUpdateModuleConfig('installed', 0);
        static::piUpdateModuleConfig('install-path', 'wordpress');
    }

    public static function wpInstallBlog($blogName, $blogDescription, $piUser = null)
    {
        // Use specific user or current user
        if (empty($piUser)) {
            $currentUser = Pi::registry('user');
            $piUser = $currentUser->id;
        } else {
            $currentUser = Pi::model('user')->find($piUser);
        }

        if (!$currentUser) {
            return false;
        }

        $user_name = $currentUser->identity;
        $user_email = $currentUser->email;
        $user_password = md5(microtime());

        // Start installation in WordPress
        static::wpLoad();

        if (!function_exists('is_blog_installed') || !is_blog_installed()) {
            return false;
        }

        $user_id = username_exists($user_name);
        // If user exists, skip blog installation
        if (!$user_id) {
            $user_id = wp_create_user($user_name, $user_password, $user_email);

            // Set site info
            update_user_meta($user_id, 'blogname', $blogName ?: __('My Blog'));
            update_user_meta($user_id, 'blogdescription', $blogDescription ?: __('Another WordPress blog site.'));

            $user = new \WP_User($user_id);
            $user->set_role('author');

            static::wpInstallDefaults($user_id);

            wp_cache_flush();
        }

        return true;
    }

    public static function wpInstallDefaults($user_id)
    {
        global $wpdb;

        // Maybe we should create the default category for user
        // Default category of current user
        $cat_name = __('Uncategorized');
        /* translators: Default category slug */
        $cat_slug = sanitize_title(_x('Uncategorized', 'Default category slug'));

        $wpdb->insert( $wpdb->terms, array('name' => $cat_name, 'slug' => $cat_slug, 'term_group' => 0, 'term_owner' => $user_id) );
        $cat_id = $wpdb->insert_id;
//        update_option('default_category', $cat_id);
        update_user_meta($user_id, 'default_category', $cat_id);
        $wpdb->insert( $wpdb->term_taxonomy, array('term_id' => $cat_id, 'taxonomy' => 'category', 'description' => '', 'parent' => 0, 'count' => 1));
        $cat_tt_id = $wpdb->insert_id;

        // Then post the first blog
        $now = date('Y-m-d H:i:s');
        $now_gmt = gmdate('Y-m-d H:i:s');
        $max_blog_id = $wpdb->get_var($wpdb->prepare("SELECT MAX(ID) FROM {$wpdb->posts}"));
        $first_post_guid = get_option('home') . '/?p=' . ($max_blog_id + 1);
        $first_post = __('Welcome to WordPress. This is your first post. Edit or delete it, then start blogging!');
        $first_post = str_replace("SITE_URL", home_url(), $first_post);
        $first_post = str_replace("SITE_NAME", get_the_author_meta('blogname', $user_id), $first_post);

        $wpdb->insert($wpdb->posts, array(
            'post_author' => $user_id,
            'post_date' => $now,
            'post_date_gmt' => $now_gmt,
            'post_content' => $first_post,
            'post_excerpt' => '',
            // Translation maybe have problems, because we override the same name function in WP
            'post_title' => __('Hello world!'),
            /* translators: Default post slug */
            'post_name' => sanitize_title(_x('hello-world', 'Default post slug')),
            'post_modified' => $now,
            'post_modified_gmt' => $now_gmt,
            'guid' => $first_post_guid,
            'comment_count' => 1,
            'to_ping' => '',
            'pinged' => '',
            'post_content_filtered' => ''
        ));
        $post_id = $wpdb->insert_id;
        // Set category
        $wpdb->insert($wpdb->term_relationships, array('term_taxonomy_id' => $cat_tt_id, 'object_id' => $post_id));

        // Default comment
        $first_comment_author = __('Mr WordPress');
        $first_comment_url = 'http://wordpress.org/';
        $first_comment = __('Hi, this is a comment.
To delete a comment, just log in and view the post&#039;s comments. There you will have the option to edit or delete them.');
        $wpdb->insert($wpdb->comments, array(
            'comment_post_ID' => $post_id,
            'comment_author' => $first_comment_author,
            'comment_author_email' => '',
            'comment_author_url' => $first_comment_url,
            'comment_date' => $now,
            'comment_date_gmt' => $now_gmt,
            'comment_content' => $first_comment
        ));

        // No default page now
    }

    public static function wpSetAuthCookie($piUserIdentity, $remember = false, $secure = '')
    {
        // Use specific user or current user
        if (empty($piUserIdentity)) {
            $user_name = Pi::registry('user')->identity;
        } else {
            $user_name = $piUserIdentity;
        }

        static::wpLoad();

        // Ensure WordPress is installed
        if (function_exists('username_exists')) {
            $user_id = username_exists($user_name);

            if ($user_id && function_exists('wp_set_auth_cookie')) {
                wp_set_auth_cookie($user_id, $remember, $secure);
            }
        }
    }

    public static function wpLogout()
    {
        static::wpLoad();

        if (function_exists('wp_logout')) {
            wp_logout();
        }
    }

    public static function wpGeneratePassword($length = 12, $special_chars = true, $extra_special_chars = false)
    {
        $chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
        if ($special_chars)
            $chars .= '!@#$%^&*()';
        if ($extra_special_chars)
            $chars .= '-_ []{}<>~`+=,.;:/?|';

        $password = '';
        for ($i = 0; $i < $length; $i++) {
            $password .= substr($chars, rand(0, strlen($chars) - 1), 1);
        }

        return $password;
    }

    public static function parseDSN($dsn)
    {
        $data = array();

        $parsed = parse_url($dsn);
        if ($parsed['path']) {
            $parsed = explode(';', $parsed['path']);

            foreach ($parsed as $item) {
                if (strpos($item, '=') !== false) {
                    list($key, $val, ) = explode('=', $item);

                    $data[trim($key)] = trim($val);
                }
            }
        }

        return $data;
    }

    public static function piUpdateModuleConfig($name, $value)
    {
        $modelConfig = Pi::model('config');

        $row = $modelConfig->select(array(
            'module' => static::$module,
            'name'   => $name,
        ))->current();
        $row->value = $value;
        $row->save();

        Pi::service('registry')->config->clear(static::$module);
    }

    public static function wpRecentPosts($limit = 10)
    {
        $posts = array();

        static::wpLoad();

        $r = new \WP_Query(apply_filters('block_recent_posts_args', array(
            'posts_per_page' => $limit,
            'no_found_rows' => true,
            'post_status' => 'publish',
            'ignore_sticky_posts' => true
        )));
        if ($r->have_posts()) {
            $posts = $r->get_posts();

            foreach ($posts as $key => $val) {
                $posts[$key] = get_object_vars($val);
                $posts[$key]['permalink'] = get_permalink($val->ID);
                $posts[$key]['author_name'] = get_userdata($val->post_author)->user_login;
            }
        }

        return $posts;
    }

    public static function wpHotPosts($limit = 10)
    {
        $posts = array();

        static::wpLoad();

        $r = new \WP_Query(apply_filters('block_hot_posts_args', array(
            'posts_per_page' => $limit,
            'no_found_rows' => true,
            'post_status' => 'publish',
            'ignore_sticky_posts' => true,
            'orderby' => 'comment_count post_date',
        )));
        if ($r->have_posts()) {
            $posts = $r->get_posts();

            foreach ($posts as $key => $val) {
                $posts[$key] = get_object_vars($val);
                $posts[$key]['permalink'] = get_permalink($val->ID);
                $posts[$key]['author_name'] = get_userdata($val->post_author)->user_login;
            }
        }

        return $posts;
    }

    public static function wpGetTags($args)
    {
        global $wpdb;

        $tags = array();
        $defaults = array('orderby' => 'name', 'order' => 'DESC',
            'hide_empty' => true, 'exclude' => array(), 'exclude_tree' => array(), 'include' => array(),
            'number' => '', 'slug' => '',
            'hierarchical' => true, 'get' => '', 'name__like' => '',
            'pad_counts' => false, 'offset' => '', 'search' => '');

        static::wpLoad();

        $args = wp_parse_args($args, $defaults);
        $args['number'] = absint($args['number']);
        $args['offset'] = absint($args['offset']);
        $args['child_of'] = 0;
        $args['hierarchical'] = false;
        $args['pad_counts'] = false;
        if ('all' == $args['get']) {
            $args['hide_empty'] = 0;
        }

        extract($args, EXTR_SKIP);

        $orderby = 'total_count';
        if (!empty($orderby)) {
            $orderby = "ORDER BY $orderby";
        } else {
            $order = '';
        }

        $order = strtoupper($order);
        if ( '' !== $order && !in_array($order, array('ASC', 'DESC'))) {
            $order = 'DESC';
        }

        $where = "tt.taxonomy='post_tag'";
        $inclusions = '';
        if (!empty($include)) {
            $exclude = '';
            $exclude_tree = '';
            $interms = wp_parse_id_list($include);
            foreach ($interms as $interm) {
                if (empty($inclusions)) {
                    $inclusions = ' AND (t.term_id = ' . intval($interm) . ' ';
                } else {
                    $inclusions .= ' OR t.term_id = ' . intval($interm) . ' ';
                }
            }
        }

        if (!empty($inclusions)) {
            $inclusions .= ')';
        }
        $where .= $inclusions;

        $exclusions = '';
        if (!empty($exclude_tree)) {
            $excluded_trunks = wp_parse_id_list($exclude_tree);
            foreach ($excluded_trunks as $extrunk) {
                $excluded_children = (array) get_terms('post_tag', array('child_of' => intval($extrunk), 'fields' => 'ids', 'hide_empty' => 0));
                $excluded_children[] = $extrunk;
                foreach($excluded_children as $exterm) {
                    if (empty($exclusions)) {
                        $exclusions = ' AND (t.term_id <> ' . intval($exterm) . ' ';
                    } else {
                        $exclusions .= ' AND t.term_id <> ' . intval($exterm) . ' ';
                    }
                }
            }
        }

        if (!empty($exclude)) {
            $exterms = wp_parse_id_list($exclude);
            foreach ($exterms as $exterm) {
                if (empty($exclusions)) {
                    $exclusions = ' AND (t.term_id <> ' . intval($exterm) . ' ';
                } else {
                    $exclusions .= ' AND t.term_id <> ' . intval($exterm) . ' ';
                }
            }
        }

        if (!empty($exclusions)) {
            $exclusions .= ')';
        }
        $exclusions = apply_filters('list_terms_exclusions', $exclusions, $args);
        $where .= $exclusions;

        if (!empty($slug)) {
            $slug = sanitize_title($slug);
            $where .= " AND t.slug = '$slug'";
        }

        if (!empty($name__like)) {
            $name__like = like_escape($name__like);
            $where .= $wpdb->prepare( " AND t.name LIKE %s", $name__like . '%' );
        }

        if ($hide_empty && !$hierarchical) {
            $where .= ' AND tt.count > 0';
        }

        // don't limit the query results when we have to descend the family tree
        if (!empty($number) && !$hierarchical) {
            if ($offset) {
                $limits = 'LIMIT ' . $offset . ',' . $number;
            } else {
                $limits = 'LIMIT ' . $number;
            }
        } else {
            $limits = '';
        }

        if (!empty($search)) {
            $search = like_escape($search);
            $where .= $wpdb->prepare( " AND (t.name LIKE %s)", '%' . $search . '%');
        }

        $join = "INNER JOIN $wpdb->term_taxonomy AS tt ON t.term_id = tt.term_id";

        $groupby = 'group by t.name';

        $fields = 't.name, sum(tt.count) AS total_count';

        $pieces = array('fields', 'join', 'where', 'orderby', 'order', 'limits', 'groupby');
        $clauses = apply_filters('terms_clauses', compact($pieces), 'post_tag', $args);
        foreach ($pieces as $piece) {
            $$piece = isset($clauses[$piece]) ? $clauses[$piece] : '';
        }

        /**
         * select t.name, sum(tt.count) as total_count from terms t
         * inner join term_taxonomy tt on tt.term_id=t.term_id
         * where tt.taxonomy='post_tag'
         * group by t.name
         * order by total_count
         * limit 20
         */
        $query = "SELECT $fields FROM $wpdb->terms AS t $join WHERE $where $groupby $orderby $order $limits";

        $tags = $wpdb->get_results($query);

        foreach ($tags as $key => $val) {
            $tags[$key] = get_object_vars($val);
        }

        return $tags;
    }

    public static function wpTagCloud($limit = 20)
    {
        static::wpLoad();

        $tags = static::wpGetTags(apply_filters('block_tag_cloud_args', array('number' => $limit)));

        return $tags;
    }

    public static function wpGetTermsBy($field, $value, $taxonomy = 'post_tag')
    {
        global $wpdb;

        static::wpLoad();

        if (!taxonomy_exists($taxonomy)) {
            return false;
        }

        if ('slug' == $field) {
            $field = 't.slug';
            $value = sanitize_title($value);
            if (empty($value)) {
                return false;
            }
        } else if ('name' == $field) {
            // Assume already escaped
            $value = stripslashes($value);
            $field = 't.name';
        } else {
            return false;
        }

        $terms = $wpdb->get_results($wpdb->prepare("SELECT t.*, tt.* FROM $wpdb->terms AS t INNER JOIN $wpdb->term_taxonomy AS tt ON t.term_id = tt.term_id WHERE tt.taxonomy = %s AND $field = %s", $taxonomy, $value));

        foreach ($terms as $key => $val) {
            $terms[$key] = get_object_vars($val);
        }

        return $terms;
    }

    public static function wpTaggedPosts($tag, $page = 1, $limit = 20)
    {
        global $wpdb;

        $terms = $objects = $posts = array();

        static::wpLoad();

        $terms = static::wpGetTermsBy('name', $tag);
        foreach ($terms as $val) {
            $ids[] = $val['term_taxonomy_id'];
        }

        $objects = $wpdb->get_col(sprintf("SELECT object_id FROM $wpdb->term_relationships WHERE term_taxonomy_id in (%s)", implode(',', $ids)));

        $r = new \WP_Query(apply_filters('block_tagged_posts_args', array(
            'paged'                 => $page,
            'posts_per_page'        => $limit,
            'no_found_rows'         => false,
            'post_status'           => 'publish',
            'ignore_sticky_posts'   => true,
            'post__in'              => $objects,
        )));
        if ($r->have_posts()) {
            $posts = $r->get_posts();

            foreach ($posts as $key => $val) {
                $posts[$key] = get_object_vars($val);
                $posts[$key]['permalink'] = get_permalink($val->ID);
                $posts[$key]['author_name'] = get_userdata($val->post_author)->user_login;
            }
        }

        return array($posts, (int) $r->found_posts);
    }
}
