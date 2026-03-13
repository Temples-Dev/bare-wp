<?php

namespace BareWP\Models;

class Media
{
    /**
     * Retrieve a specific media attachment by ID.
     *
     * @param int $attachment_id The attachment ID.
     * @return array|null The formatted media array or null if not found.
     */
    public static function find(int $attachment_id): ?array
    {
        if (!function_exists('get_post')) {
            throw new \RuntimeException('WordPress Core is not loaded.');
        }

        $post = get_post($attachment_id);

        if (!$post || $post->post_type !== 'attachment') {
            return null;
        }

        return self::format($post);
    }

    /**
     * Get the featured image (thumbnail) for a specific post.
     *
     * @param int $post_id The parent post ID.
     * @return array|null The formatted media array or null if no thumbnail exists.
     */
    public static function featuredImage(int $post_id): ?array
    {
        if (!function_exists('get_post_thumbnail_id')) {
            throw new \RuntimeException('WordPress Core is not loaded.');
        }

        $thumbnail_id = get_post_thumbnail_id($post_id);

        if (!$thumbnail_id) {
            return null;
        }

        return self::find($thumbnail_id);
    }

    /**
     * Format a WP_Post object representing an attachment into a custom array structure.
     *
     * @param \WP_Post $attachment
     * @return array
     */
    protected static function format(\WP_Post $attachment): array
    {
        $formatted = [
            'id'           => $attachment->ID,
            'title'        => get_the_title($attachment),
            'caption'      => $attachment->post_excerpt, // WordPress stores captions in post_excerpt
            'description'  => $attachment->post_content,
            'mime_type'    => $attachment->post_mime_type,
            'url'          => wp_get_attachment_url($attachment->ID),
            'alt_text'     => get_post_meta($attachment->ID, '_wp_attachment_image_alt', true),
            'uploaded_at'  => $attachment->post_date,
            'author_id'    => (int) $attachment->post_author,
        ];

        // Retrieve metadata (dimensions, sizes, etc.)
        if (function_exists('wp_get_attachment_metadata')) {
            $metadata = wp_get_attachment_metadata($attachment->ID);

            if (is_array($metadata)) {
                $formatted['width']  = $metadata['width'] ?? null;
                $formatted['height'] = $metadata['height'] ?? null;
                $formatted['sizes']  = [];

                if (isset($metadata['sizes']) && is_array($metadata['sizes'])) {
                    // Extract available image sizes (thumbnail, medium, large, etc.)
                    foreach ($metadata['sizes'] as $size => $size_data) {
                        $image_src = wp_get_attachment_image_src($attachment->ID, $size);
                        if ($image_src) {
                            $formatted['sizes'][$size] = [
                                'url'    => $image_src[0],
                                'width'  => $image_src[1],
                                'height' => $image_src[2],
                                'is_intermediate' => $image_src[3],
                            ];
                        }
                    }
                }
            }
        }

        return $formatted;
    }
}
