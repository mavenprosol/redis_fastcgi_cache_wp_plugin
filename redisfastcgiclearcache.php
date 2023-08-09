<?php
/**
 * Plugin Name: RedisFastcgi Clear Cache
 * Description: Wordpress plugin for Clear Redis and FastCGI caches when a post is updated or deleted, and also clears cache for the homepage.
 * Version: 1.0
 * Author: Mavenpro
 */

// Do not allow direct access to this file.
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Clears the cache for the given URL.
 *
 * @param string  $url The URL of the page.
 */
function clear_cache_for_url($url)
{
    // Check if the Nginx Helper plugin is active.
    if (!function_exists('rt_nginx_helper_purge_url')) {
        error_log('The Nginx Helper plugin is not active.');
        return;
    }

    // Clear the FastCGI cache.
    wp_remote_request($url, array('method' => 'PURGE'));

    // Clear the Redis cache.
    if (class_exists('Redis')) {
        try {
            $redis = new Redis();
            $redis->connect('127.0.0.1', 6379);
            $redis->del($url);
        } catch (Exception $e) {
            error_log('Failed to clear Redis cache: ' . $e->getMessage());
        }
    } else {
        error_log('The Redis extension for PHP is not installed or enabled.');
    }
}

/**
 * Clears the cache for the given post and homepage.
 *
 * @param int     $post_id The ID of the post.
 * @param WP_Post $post    The post object.
 */
function clear_cache_for_post_and_home($post_id, $post)
{
    // Clear cache for the post.
    clear_cache_for_url(get_permalink($post));

    // Clear cache for the homepage.
    clear_cache_for_url(home_url('/'));
}

add_action('save_post', 'clear_cache_for_post_and_home', 10, 2);
add_action('delete_post', 'clear_cache_for_post_and_home', 10, 2);

/*

Testing your WordPress plugin is a crucial part of development. Here are a few steps to verify if the cache is being cleared as expected:

1. **Install the plugin:** Go to your WordPress Dashboard, navigate to the Plugins section, and upload the plugin file. Once it's uploaded, activate the plugin.

2. **Monitor logs:** Keep an eye on your PHP error logs to see if there are any error messages being reported by the plugin. You can usually find these logs in your server's file system, or through your hosting provider's control panel.

3. **Manual cache verification:** 

   For **FastCGI**, you can first visit your homepage and a few post pages to ensure they are cached. Then, modify or delete a post in your WordPress admin. Revisit those pages in a new private or incognito browser window (to avoid browser cache). The changes should reflect immediately, indicating that the cache was cleared.
   
   For **Redis**, the process is a bit more complicated. You would need access to the Redis command line to check if the keys (URLs) were properly deleted. You can use the command `keys *` to list all keys stored in the Redis cache. Then, make changes to a post or delete a post and check the keys in Redis again. If the cache is being cleared correctly, you should not find the post URL in the list.

4. **Use Developer Tools:** Most modern browsers have developer tools that let you inspect network traffic. In Chrome, for example, you can use the Network tab in the Developer Tools to inspect the headers of your website's HTTP response. A `X-FastCGI-Cache: HIT` header indicates that the page was served from cache, while a `X-FastCGI-Cache: MISS` header indicates that it wasn't. By checking this header before and after updating a post, you can verify if the FastCGI cache is being cleared.

Please note that the specifics of these steps can vary depending on your environment and setup. Always ensure you have a backup of your site and have thoroughly tested any changes in a staging or development environment before applying them to your live site.
*/
