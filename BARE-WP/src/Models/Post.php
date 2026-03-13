<?php

namespace BareWP\Models;

class Post
{
    /**
     * Retrieve a list of posts or custom post types.
     *
     * @param array $args Arguments compatible with WP_Query / get_posts.
     * @param bool $include_meta Whether to include custom fields.
     * @return array Array of formatted post objects.
     */
    public static function all(array $args = [], bool $include_meta = true): array
    {
        if (!function_exists('get_posts')) {
            throw new \RuntimeException('WordPress Core is not loaded.');
        }

        $default_args = [
            'post_type'      => 'post',
            'post_status'    => 'publish',
            'posts_per_page' => 10,
        ];

        $query_args = array_merge($default_args, $args);
        $wp_posts = get_posts($query_args);

        return array_map(function ($post) use ($include_meta) {
            return self::format($post, $include_meta);
        }, $wp_posts);
    }

    /**
     * Retrieve a single post by ID.
     *
     * @param int $id The post ID.
     * @param bool $include_meta Whether to include custom fields.
     * @return array|null The formatted post array or null if not found.
     */
    public static function find(int $id, bool $include_meta = true): ?array
    {
        if (!function_exists('get_post')) {
            throw new \RuntimeException('WordPress Core is not loaded.');
        }

        $post = get_post($id);

        if (!$post) {
            return null;
        }

        return self::format($post, $include_meta);
    }

    /**
     * Format a WP_Post object into a custom array structure.
     *
     * @param \WP_Post $post
     * @param bool $include_meta
     * @return array
     */
    protected static function format(\WP_Post $post, bool $include_meta): array
    {
        $formatted = [
            'id'           => $post->ID,
            'title'        => get_the_title($post),
            'content'      => apply_filters('the_content', $post->post_content),
            'excerpt'      => get_the_excerpt($post),
            'slug'         => $post->post_name,
            'type'         => $post->post_type,
            'status'       => $post->post_status,
            'author_id'    => (int) $post->post_author,
            'published_at' => $post->post_date,
            'modified_at'  => $post->post_modified,
            'url'          => get_permalink($post),
        ];

        if ($include_meta && function_exists('get_post_meta')) {
            // Get all meta fields for the post.
            // Note: get_post_meta($id, '', true) returns an associative array where values are arrays.
            $raw_meta = get_post_meta($post->ID);
            $meta = [];

            if (is_array($raw_meta)) {
                foreach ($raw_meta as $key => $values) {
                    // Skip hidden/internal meta keys starting with '_'
                    if (str_starts_with($key, '_')) {
                        continue;
                    }
                    // Flatten single-value arrays
                    $meta[$key] = count($values) === 1 ? $values[0] : $values;
                }
            }
            $formatted['meta'] = $meta;
        }

        return $formatted;
    }
}
