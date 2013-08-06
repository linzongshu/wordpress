<?php
/*
Plugin Name: Pi Engine Hacker
Plugin URI: http://URI_Of_Page_Describing_Plugin_and_Updates
Description: Integrated with Pi Engine.
Version: 0.1
Author: Lijun Dong
Author URI: http://URI_Of_The_Plugin_Author
License: GPL2
*/

/**
 * Version control
 */
$pi_engine_plugin_version = '0.1';
$previous_version = get_option('pi_engine_plugin_version');
if ($previous_version) {
    // Do something about version comparison
} else if (!defined('WP_INSTALLING')) {
    pi_init_plugin();
    update_option('pi_engine_plugin_version', $pi_engine_plugin_version);
}

//add_filter('query_vars', 'accept_pi_site_identifier');
//function accept_pi_site_identifier($vars)
//{
//    $vars[] = 'pi_site_identifier';
//
//    return $vars;
//}

//register_activation_hook(__FILE__, 'pi_init_plugin');
/**
 * Initialize plugin
 */
function pi_init_plugin()
{
    global $wpdb;

    // Add table fields
    $sql =  "ALTER TABLE `{$wpdb->terms}` ADD `term_owner` BIGINT( 20 ) NOT NULL DEFAULT '0'";
    $wpdb->query($sql);

    // Update old data
    $wpdb->update($wpdb->terms, array('term_owner' => 1), array('term_id' => 1));
    update_user_meta(1, 'blogname', get_option('blogname'));
    update_user_meta(1, 'blogdescription', get_option('blogdescription'));

    // Drop old indexes
    $sql = "ALTER TABLE `{$wpdb->terms}` DROP INDEX `slug`";
    $wpdb->query($sql);

    // Add new indexes
    $sql = "ALTER TABLE `{$wpdb->terms}` ADD UNIQUE  `slug_term_owner` (`slug`, `term_owner`)";
    $wpdb->query($sql);

    // Hack author role
    $role = get_role('author');
    // Comments
    $role->add_cap('moderate_comments');
    // Terms
    $role->add_cap('manage_categories');
}

/**
 * Sanitize user name
 *
 * @param $data
 * @return mixed
 */
if (!function_exists('pi_sanitize_user')) {
    function pi_sanitize_user($username)
    {
        $username = strtolower($username);
        $username = preg_replace('/[^a-z0-9]/', '', $username);

        return $username;
    }
}

if (!function_exists('is_wp_admin')) {
    function is_wp_admin()
    {
        $pattern = sprintf('|^/%s/\w+/wp-admin/?.*$|', WP_ROUTE_TOKEN);

        return preg_match($pattern, $_SERVER['REQUEST_URI']);
    }
}

/**
 * The following actions should be fired ASAP
 */
//add_action('muplugins_loaded', 'pi_hack_admin_cookie_path');
add_action('muplugins_loaded', 'pi_hack_request');
add_action('muplugins_loaded', 'pi_hack_upload_constants');

/**
 * Hack admin cookie path to set right cookie
 *
 * wp_plugin_directory_constants() is invoked before muplugins_loaded,
 * so we have to move these codes to wp-config.php
 */
function pi_hack_admin_cookie_path()
{
    define('COOKIEHASH', md5(AUTH_KEY));
    define('COOKIEPATH', '/');
    define('SITECOOKIEPATH', '/');
    define('ADMIN_COOKIE_PATH', '/');
    define('PLUGINS_COOKIE_PATH', '/');
}

/**
 * Hack request to parse author and support to Pi custom route
 */
function pi_hack_request()
{
    global $pi_site_identifier, $pi_site_user;

    $pattern = sprintf('|^/%s/(\w+)(/?.*)$|', WP_ROUTE_TOKEN);
    if (isset($_SERVER['REQUEST_URI']) && preg_match($pattern, $_SERVER['REQUEST_URI'], $matches)) {
        $pi_site_identifier = pi_sanitize_user($matches[1]);
//        $_SERVER['REQUEST_URI'] = $matches[2] ?: '/';
    } else if (defined('PI_DEFAULT_USER')) {
        $pi_site_identifier = pi_sanitize_user(PI_DEFAULT_USER);
    }

    if ($pi_site_identifier) {
        $_GET['author_name'] = $pi_site_identifier;
        if (!function_exists('get_user_by')) {
            require_once(ABSPATH . WPINC . '/pluggable.php');
        }
        $pi_site_user = get_user_by('login', $pi_site_identifier);
    }
}

/**
 * Define upload constants depending on Pi custom route
 */
function pi_hack_upload_constants()
{
    global $pi_site_identifier;

    if ($pi_site_identifier) {
        // Base uploads dir relative to ABSPATH
        if ( !defined( 'UPLOADBLOGSDIR' ) )
            define( 'UPLOADBLOGSDIR', 'wp-content/blogs.dir' );

        // Note, the main site in a post-MU network uses wp-content/uploads.
        // This is handled in wp_upload_dir() by ignoring UPLOADS for this case.
//        if ( ! defined( 'UPLOADS' ) ) {
        define( 'UPLOADS', UPLOADBLOGSDIR . "/{$pi_site_identifier}/files" );

        // Uploads dir relative to ABSPATH
        if ( 'wp-content/blogs.dir' == UPLOADBLOGSDIR && ! defined( 'BLOGUPLOADDIR' ) )
            define( 'BLOGUPLOADDIR', WP_CONTENT_DIR . "/blogs.dir/{$pi_site_identifier}/files" );
//        }
    }
}

/**
 * Handle global url
 */
//add_filter('wp_redirect', 'pi_hack_site_url');
add_filter('site_url', 'pi_hack_site_url');
add_filter('home_url', 'pi_hack_site_url');
//add_filter('pre_option_siteurl', 'pi_hack_site_url');
add_filter('content_url', 'pi_hack_site_url');
add_filter('plugins_url', 'pi_hack_site_url');
function pi_hack_site_url($url)
{
    global $pi_site_identifier;

//    $pi_site_identifier = isset($_GET['author_name']) ? $_GET['author_name'] : '';
    return pi_hack_url($url, $pi_site_identifier);
}

/**
 * Inject author name into site url
 *
 * @param $url
 * @param $author
 * @return string
 */
function pi_hack_url($url, $author)
{
    if ($author) {
        $fragments = parse_url($url);
        if (!isset($fragments['path'])) {
            $fragments['path'] = '/';
        }

        $pattern = sprintf('|^/%s/\w+|', WP_ROUTE_TOKEN);
        if (!preg_match($pattern, $fragments['path'])) {
            $url = '';
            if (isset($fragments['scheme'])) {
                $url .= $fragments['scheme'] . '://';
            }
            if (isset($fragments['host'])) {
                $url .= $fragments['host'];
            }
            $url .= sprintf('/%s/%s', WP_ROUTE_TOKEN, $author);
            $url .= (isset($fragments['path']) && $fragments['path']) ?  '/' . ltrim($fragments['path'], '/') : '/';
            if (isset($fragments['query'])) {
                $url .= '?' . $fragments['query'];
            }
            if (isset($fragments['fragment'])) {
                $url .= '#' . $fragments['fragment'];
            }
        }
    }

    return $url;
}

/**
 * Handle post link by author information
 */
add_filter('post_link', 'pi_hack_post_link', null, 3);
add_filter('post_type_link', 'pi_hack_post_link', null, 3);
function pi_hack_post_link($url, $post, $leavename)
{
    $authordata = get_userdata($post->post_author);
    $author = $authordata->user_login;

    $pattern = sprintf('|^(\w+://)?([^/]*/%s/)(\w+)|', WP_ROUTE_TOKEN);
    if (!preg_match($pattern, $url)) {
        $url = pi_hack_url($url, $author);
    } else {
        $url = pi_hack_user_url($url, $author);
    }

    return $url;
}

function pi_hack_user_url($url, $user)
{
    $pattern = sprintf('|^(\w+://)?([^/]*/%s/)(\w+)(/?.*)$|', WP_ROUTE_TOKEN);
    $url = preg_replace($pattern, "$1$2{$user}$4", $url);

    return $url;
}

add_filter('attachment_link', 'pi_hack_attachment_link', null, 2);
function pi_hack_attachment_link($link, $postId)
{
    $post = get_post($postId);

    return pi_hack_post_link($link, $post, null);
}

add_filter('get_attached_file', 'pi_hack_get_attached_file', null, 2);
function pi_hack_get_attached_file($file, $attachment_id)
{
    $post = get_post($attachment_id);

    if ($post) {
        $user = get_userdata($post->post_author);

        if ($user) {
            $file = pi_hack_upload_url($file, $user->user_login);
        }
    }

    return $file;
}

/**
 * Use this filter to get real relative path of image, instead of filter _wp_relative_upload_path
 */
add_filter('update_attached_file', 'pi_hack_update_attached_file', null, 2);
function pi_hack_update_attached_file($file, $attachment_id)
{
    $post = get_post($attachment_id);

    if ($post) {
        $user = get_userdata($post->post_author);

        if ($user) {
            $uploads = wp_upload_dir();
            $basedir = pi_hack_upload_url($uploads['basedir'], $user->user_login);

            if ( 0 === strpos($file, $basedir)) {
                $file = str_replace($basedir, '', $file);
                $file = ltrim($file, '/');
            }
        }
    }

    return $file;
}

add_filter('page_link', 'pi_hack_page_link', null, 2);
function pi_hack_page_link($link, $postId)
{
    $post = get_post($postId);

    if ($post) {
        $user = get_userdata($post->post_author);

        if ($user) {
            $link = pi_hack_user_url($link, $user->user_login);
        }
    }

    return $link;
}

/**
 * Take over login/logout of WordPress
 */
//add_filter('loginout', 'pi_hack_loginout');
//function pi_hack_loginout($link)
//{
//    $pattern = '|^(.*<a\s+href=")([^"<>]+)(".*)$|';
//    $replacement = is_user_logged_in() ? '$1/user/logout$3' : '$1/user/login$3';
//    $link = preg_replace($pattern, $replacement, $link);
//
//    return $link;
//}

add_action('wp_logout', 'pi_hack_wp_logout');
function pi_hack_wp_logout()
{
    $target = is_user_logged_in() ? '/user/logout' : home_url();
    header("Location: {$target}");
    exit();
}

add_action('wp_authenticate', 'pi_hack_wp_authenticate');
function pi_hack_wp_authenticate($credentials)
{
    header('Location: /user/login');
    exit();
}

add_filter('logout_url', 'pi_hack_logout_url', null, 2);
function pi_hack_logout_url($url, $redirect)
{
    $url = '/user/logout';

    return $url;
}

add_filter('login_url', 'pi_hack_login_url', null, 2);
function pi_hack_login_url($url, $redirect)
{
    $url = '/user/login';

    return $url;
}

/**
 * Take over registration of WordPress
 */
add_filter('register', 'pi_hack_register');
function pi_hack_register($link)
{
    if (!is_user_logged_in()) {
        $pattern = '|^(.*<a\s+href=")([^"<>]+)(".*)$|';
        $link = preg_replace($pattern, '$1/user/register$3', $link);
    }

    return $link;
}

add_filter('admin_url', 'pi_hack_admin_url');
function pi_hack_admin_url($url)
{
    if (is_user_logged_in()) {
        $user = wp_get_current_user();
        $url = pi_hack_user_url($url, $user->user_login);
    }

    return $url;
}

/**
 * Administrator and Editor can use all data
 */
add_action('parse_query', 'pi_allow_administrator');
function pi_allow_administrator($query)
{
    global $pi_site_identifier;

    if (is_blog_admin()) {
        $user = wp_get_current_user();
        if ($user && strcmp($user->user_login, $pi_site_identifier) !== 0) {
            $target = pi_hack_user_url($_SERVER['REQUEST_URI'], $user->user_login);
            wp_redirect($target);
            exit();
        }

        if (current_user_can('level_7')) {
            if (isset($_GET['author_name'])) {
                unset($_GET['author_name']);
            }

            if (isset($query->query_vars['author_name'])) {
                $query->query_vars['author_name'] = '';
            }
        }
    }
}

/**
 * Author can access only own posts
 */
add_filter('pre_get_posts', 'pi_hack_pre_get_posts');
function pi_hack_pre_get_posts($query)
{
    global $pi_site_identifier;

    if (is_wp_admin()) {
        if (!current_user_can('level_7')) {
            $query->query_vars['author_name'] = $pi_site_identifier;
        }
    }
}

/**
 * Author can moderate the comments of their posts
 */
add_action('pre_get_comments', 'pi_author_get_comments');
function pi_author_get_comments($query)
{
    if (is_blog_admin() && current_user_can('moderate_comments') && !current_user_can('level_7')) {
        $user = wp_get_current_user();

        $query->query_vars['post_author'] = $user->ID;
    }
}

/**
 * Get comment stats by author
 */
add_filter('wp_count_comments', 'pi_hack_wp_count_comments', null, 2);
function pi_hack_wp_count_comments($data, $post_id)
{
    global $wpdb;

    $post_id = (int) $post_id;
    $stats = array();

    $key = $post_id;
    if (!current_user_can('level_7')) {
        $user = wp_get_current_user();

        if ($user) {
            $key .= '-' . $user->ID;
        }
    }
    $count = wp_cache_get("comments-{$key}", 'counts');

    if ( false !== $count )
        return $count;

    $where = $join = '';
    if ($post_id > 0)
        $where = $wpdb->prepare("WHERE $wpdb->comments.comment_post_ID = %d", $post_id);

    if ($user) {
        $join = "JOIN $wpdb->posts ON $wpdb->posts.ID = $wpdb->comments.comment_post_ID";
        $where .= $where ? $wpdb->prepare(" AND $wpdb->posts.post_author = %d", $user->ID) : $wpdb->prepare("WHERE $wpdb->posts.post_author = %d", $user->ID);
    }

    $count = $wpdb->get_results("SELECT $wpdb->comments.comment_approved, COUNT( * ) AS num_comments FROM {$wpdb->comments} {$join} {$where} GROUP BY $wpdb->comments.comment_approved", ARRAY_A);

    $total = 0;
    $approved = array('0' => 'moderated', '1' => 'approved', 'spam' => 'spam', 'trash' => 'trash', 'post-trashed' => 'post-trashed');
    foreach ( (array) $count as $row ) {
        // Don't count post-trashed toward totals
        if ( 'post-trashed' != $row['comment_approved'] && 'trash' != $row['comment_approved'] )
            $total += $row['num_comments'];
        if ( isset( $approved[$row['comment_approved']] ) )
            $stats[$approved[$row['comment_approved']]] = $row['num_comments'];
    }

    $stats['total_comments'] = $total;
    foreach ( $approved as $key ) {
        if ( empty($stats[$key]) )
            $stats[$key] = 0;
    }

    $stats = (object) $stats;
    wp_cache_set("comments-{$key}", $stats, 'counts');

    return $stats;
}

/**
 * Handle attachment link by author information
 */
add_filter('wp_get_attachment_url', 'pi_hack_attachment_url', null, 2);
add_filter('wp_get_attachment_thumb_url', 'pi_hack_attachment_url', null, 2);
function pi_hack_attachment_url($url, $post_id)
{
    $post_id = (int) $post_id;
    if ($post = get_post($post_id)) {
        $authordata = get_userdata($post->post_author);
        $author = $authordata->user_login;

        $url = pi_hack_upload_url($url, $author);
    }

    return $url;
}

/**
 * Handle upload dir by current user
 */
add_filter('upload_dir', 'pi_hack_upload_dir');
function pi_hack_upload_dir($params)
{
    $user = wp_get_current_user();
    if ($user) {
        $author = $user->user_login;

        foreach ($params as $key => $val) {
            if (is_string($val)) {
                $params[$key] = pi_hack_upload_url($val, $author);
            }
        }
    }

    return $params;
}

function pi_hack_upload_url($url, $user)
{
    // Process url
    $pattern_url = '|^(\w+://)([^/]+)(/wp-content)(/?.*)$|';
    $url = preg_replace($pattern_url, '$1$2/' . WP_DIR_IN_PI_WWW . '/wp-content$4', $url);

    // Process dir
    $pattern = '|^(.+/blogs.dir/)(\w+)(/?.+)$|';
    $url = preg_replace($pattern, "$1{$user}$3", $url);

    return $url;
}

/**
 * Handle previous/next post
 */
add_filter('get_previous_post_where', 'pi_hack_adjacent_post_where');
add_filter('get_next_post_where', 'pi_hack_adjacent_post_where');
function pi_hack_adjacent_post_where($where)
{
    global $pi_site_user;

    if ($pi_site_user) {
        $where .= " AND p.post_author = '{$pi_site_user->ID}'";
    }

    return $where;
}

/**
 * Handle widgets
 */
add_filter('widget_posts_args', 'pi_hack_widget_posts_args');
function pi_hack_widget_posts_args($args)
{
    global $pi_site_identifier;

    if ($pi_site_identifier) {
//        $user = get_user_by('login', $pi_site_identifier);
//        $args['author'] = $user->ID;
        $args['author_name'] = $pi_site_identifier;
    }

    return $args;
}

add_filter('widget_comments_args', 'pi_hack_widget_comments_args');
function pi_hack_widget_comments_args($args)
{
    global $pi_site_user;

    if ($pi_site_user) {
        $args['post_author'] = $pi_site_user->ID;
    }

    return $args;
}

add_filter('widget_categories_args', 'pi_hack_widget_categories_args');
function pi_hack_widget_categories_args($args)
{
    global $pi_site_user;

    if ($pi_site_user) {
        $args['term_owner'] = $pi_site_user->ID;
    }

    return $args;
}

add_filter('getarchives_where', 'pi_hack_getarchives_where', null, 2);
function pi_hack_getarchives_where($where, $args)
{
    global $pi_site_user;

    if ($pi_site_user) {
        $where .= ' AND post_author = ' . $pi_site_user->ID;
    }

    return $where;
}

add_filter('widget_tag_cloud_args', 'pi_hack_widget_tag_cloud_args');
function pi_hack_widget_tag_cloud_args($args)
{
    global $pi_site_user;

    if ($pi_site_user) {
        $args['term_owner'] = $pi_site_user->ID;
    }

    return $args;
}

add_filter('widget_pages_args', 'pi_hack_widget_pages_args');
add_filter('wp_page_menu_args', 'pi_hack_widget_pages_args');
function pi_hack_widget_pages_args($args)
{
    global $pi_site_user;

    if ($pi_site_user) {
        $args['authors'] = $pi_site_user->ID;
    }

    return $args;
}

add_filter('widget_links_args', 'pi_hack_widget_links_args');
function pi_hack_widget_links_args($args)
{
    global $pi_site_user;

    if ($pi_site_user) {
        $args['owner'] = $pi_site_user->ID;
    }

    return $args;
}

/**
 * Customize user profile
 */
add_filter('show_password_fields', 'pi_show_password_fields');
add_filter('user_contactmethods', 'pi_hack_user_contactmethods');

add_action('show_user_profile', 'pi_show_site_info');
add_action('edit_user_profile', 'pi_show_site_info');
add_action('personal_options_update', 'pi_save_site_info');
add_action('edit_user_profile_update', 'pi_save_site_info');

/**
 * Don't show password fields on user profile page.
 *
 * @param $show_password_fields
 * @return bool
 */
function pi_show_password_fields($show_password_fields)
{
    return false;
}

/**
 * Customize contact methods
 *
 * @param array $contactmethods
 * @return array
 */

function pi_hack_user_contactmethods($contactmethods)
{
    unset($contactmethods['aim']);
    unset($contactmethods['yim']);
    unset($contactmethods['jabber']);

    return $contactmethods;
}

/**
 * Manage site name and description in profile page
 */

function pi_show_site_info($user)
{
?>
    <h3>Site Info</h3>
    <table class="form-table">
        <tbody>
        <tr>
            <th>
                <label for="blogname">Site Title</label>
            </th>
            <td>
                <input type="text" name="blogname" id="blogname" value="<?php echo esc_attr(get_the_author_meta('blogname', $user->ID)); ?>" class="regular-text" />
            </td>
        </tr>
        <tr>
            <th>
                <label for="blogdescription">Tagline</label>
            </th>
            <td>
                <input type="text" name="blogdescription" id="blogdescription" value="<?php echo esc_attr(get_the_author_meta('blogdescription', $user->ID)); ?>" class="regular-text" />
                <p class="description">In a few words, explain what this site is about.</p>
            </td>
        </tr>
        </tbody>
    </table>
<?php }

function pi_save_site_info($user_id)
{
    if (current_user_can('edit_user', $user_id)) {
        update_user_meta($user_id, 'blogname', $_POST['blogname']);
        update_user_meta($user_id, 'blogdescription', $_POST['blogdescription']);
    }
}

/**
 * Hack blog info to get values from user meta
 */
add_filter('bloginfo', 'pi_hack_bloginfo', null, 2);
add_filter('bloginfo_url', 'pi_hack_bloginfo', null, 2);
function pi_hack_bloginfo($output, $show)
{
    global $pi_site_user;

    $user = null;

    if (is_blog_admin()) {
        $user = wp_get_current_user();
    } else if ($pi_site_user) {
        $user = $pi_site_user;
    }

    if ($user) {
        switch ($show) {
            case 'description':
                $output = get_the_author_meta('blogdescription', $user->ID) ?: $output;
                break;
            case 'name':
                $output = get_the_author_meta('blogname', $user->ID) ?: $output;
                break;
        }
    }

    return $output;
}

add_filter('get_bloginfo_rss', 'pi_hack_get_bloginfo_rss', null, 2);
function pi_hack_get_bloginfo_rss($output, $show)
{
    $output = pi_hack_bloginfo($output, $show);

    return convert_chars($output);
}

/**
 * Some functions should be disabled
 */
add_action('lost_password', 'pi_disable_function');
add_action('retrieve_password', 'pi_disable_function');
add_action('password_reset', 'pi_disable_function');

add_filter('pre_update_option_permalink_structure', 'pi_disable_function');
add_filter('pre_update_option_category_base', 'pi_disable_function');
add_filter('pre_update_option_tag_base', 'pi_disable_function');

function pi_disable_function()
{
    die('Disabled');
}

/**
 * Admin cannot create user in WordPress
 */
add_action('check_admin_referer', 'pi_hack_check_admin_referer', null, 2);
function pi_hack_check_admin_referer($action, $result)
{
    $disabled_actions = array(
        'add-user',
        'create-user',
    );

    if (array_search($action, $disabled_actions, true)) {
        pi_disable_function();
    }
}

/**
 * Some menu should be hidden
 */
add_action('admin_menu', 'pi_remove_admin_menus');

/**
 * Don't show some admin menus
 */
function pi_remove_admin_menus()
{
    // Permalink submenu
    remove_submenu_page('options-general.php', 'options-permalink.php');

    // Add new user submenu
    remove_submenu_page('users.php', 'user-new.php');
}

add_action('init', 'pi_debug');
function pi_debug()
{
    global $wp_query, $wp_rewrite, $pi_site_identifier;

//    echo "<p>hit " . $wp_query->get('pi_site_identifier') . "</p>";
//    var_dump($_GET);
//    var_dump($_SERVER);
//    var_dump(wp_upload_dir());
//    var_dump(wp_get_current_user());
//    var_dump($pi_site_identifier);
//    $user = wp_get_current_user();
//    var_dump(get_user_meta($user->ID));
//    var_dump(get_the_author_meta('email', $user->ID));
//    var_dump($_COOKIE);
//    var_dump(get_site_option('siteurl'));
//    var_dump(md5(get_site_option('siteurl')));
}
