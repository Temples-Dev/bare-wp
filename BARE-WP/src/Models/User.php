<?php

namespace BareWP\Models;

class User
{
    /**
     * Get the currently authenticated WordPress user.
     *
     * @return array|null The formatted user array or null if not logged in.
     */
    public static function current(): ?array
    {
        if (!function_exists('wp_get_current_user')) {
            throw new \RuntimeException('WordPress Core is not loaded.');
        }

        $current_user = wp_get_current_user();

        if ($current_user->ID === 0) {
            return null; // Guest user
        }

        return self::format($current_user, true); // Include sensitive data for the current logged-in user
    }

    /**
     * Retrieve a specific user by ID.
     *
     * @param int $id The user ID.
     * @param bool $include_sensitive Whether to include sensitive data like email. Defaults to false.
     * @return array|null The formatted user array or null if not found.
     */
    public static function find(int $id, bool $include_sensitive = false): ?array
    {
        if (!function_exists('get_userdata')) {
            throw new \RuntimeException('WordPress Core is not loaded.');
        }

        $user = get_userdata($id);

        if (!$user) {
            return null;
        }

        return self::format($user, $include_sensitive);
    }

    /**
     * Format a WP_User object into a custom array structure.
     *
     * @param \WP_User $user The WP_User object.
     * @param bool $include_sensitive Whether to include sensitive data like email.
     * @return array
     */
    protected static function format(\WP_User $user, bool $include_sensitive = false): array
    {
        $formatted = [
            'id'           => $user->ID,
            'username'     => $user->user_login,
            'display_name' => $user->display_name,
            'roles'        => $user->roles,
            'registered'   => $user->user_registered,
        ];

        if ($include_sensitive) {
            $formatted['email'] = $user->user_email;
        }

        if (function_exists('get_user_meta')) {
            // Get all meta fields for the user
            $raw_meta = get_user_meta($user->ID);
            $meta = [];

            if (is_array($raw_meta)) {
                foreach ($raw_meta as $key => $values) {
                    // Skip hidden/internal meta keys starting with '_' or 'wp_'
                    if (str_starts_with($key, '_') || str_starts_with($key, 'wp_')) {
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
