<?php
function wp_insert_user_id( $userdata ) {
    global $wpdb;

    if ( $userdata instanceof stdClass ) {
        $userdata = get_object_vars( $userdata );
    } elseif ( $userdata instanceof WP_User ) {
        $userdata = $userdata->to_array();
    }

    // Are we updating or creating?
    /*
    if ( ! empty( $userdata['ID'] ) ) {
        $ID            = (int) $userdata['ID'];
        $update        = true;
        $old_user_data = get_userdata( $ID );

        if ( ! $old_user_data ) {
            return new WP_Error( 'invalid_user_id', __( 'Invalid user ID.' ) );
        }

        // Hashed in wp_update_user(), plaintext if called directly.
        $user_pass = ! empty( $userdata['user_pass'] ) ? $userdata['user_pass'] : $old_user_data->user_pass;
    } else {
    */
    $update = false;
        // Hash the password.
    $user_pass = wp_hash_password( $userdata['user_pass'] );


    $sanitized_user_login = sanitize_user( $userdata['user_login'], true );

    /**
     * Filters a username after it has been sanitized.
     *
     * This filter is called before the user is created or updated.
     *
     * @since 2.0.3
     *
     * @param string $sanitized_user_login Username after it has been sanitized.
     */
    $pre_user_login = apply_filters( 'pre_user_login', $sanitized_user_login );

    // Remove any non-printable chars from the login string to see if we have ended up with an empty username.
    $user_login = trim( $pre_user_login );

    $ID = $userdata['ID'];

    // user_login must be between 0 and 60 characters.
    if ( empty( $user_login ) ) {
        return new WP_Error( 'empty_user_login', __( 'Cannot create a user with an empty login name.' ) );
    } elseif ( mb_strlen( $user_login ) > 60 ) {
        return new WP_Error( 'user_login_too_long', __( 'Username may not be longer than 60 characters.' ) );
    }

    if ( ! $update && username_exists( $user_login ) ) {
        return new WP_Error( 'existing_user_login', __( 'Sorry, that username already exists!' ) );
    }

    /**
     * Filters the list of disallowed usernames.
     *
     * @since 4.4.0
     *
     * @param array $usernames Array of disallowed usernames.
     */
    $illegal_logins = (array) apply_filters( 'illegal_user_logins', array() );

    if ( in_array( strtolower( $user_login ), array_map( 'strtolower', $illegal_logins ), true ) ) {
        return new WP_Error( 'invalid_username', __( 'Sorry, that username is not allowed.' ) );
    }

    /*
     * If a nicename is provided, remove unsafe user characters before using it.
     * Otherwise build a nicename from the user_login.
     */
    if ( ! empty( $userdata['user_nicename'] ) ) {
        $user_nicename = sanitize_user( $userdata['user_nicename'], true );
        if ( mb_strlen( $user_nicename ) > 50 ) {
            return new WP_Error( 'user_nicename_too_long', __( 'Nicename may not be longer than 50 characters.' ) );
        }
    } else {
        $user_nicename = mb_substr( $user_login, 0, 50 );
    }

    $user_nicename = sanitize_title( $user_nicename );

    /**
     * Filters a user's nicename before the user is created or updated.
     *
     * @since 2.0.3
     *
     * @param string $user_nicename The user's nicename.
     */
    $user_nicename = apply_filters( 'pre_user_nicename', $user_nicename );

    $user_nicename_check = $wpdb->get_var( $wpdb->prepare( "SELECT ID FROM $wpdb->users WHERE user_nicename = %s AND user_login != %s LIMIT 1", $user_nicename, $user_login ) );

    if ( $user_nicename_check ) {
        $suffix = 2;
        while ( $user_nicename_check ) {
            // user_nicename allows 50 chars. Subtract one for a hyphen, plus the length of the suffix.
            $base_length         = 49 - mb_strlen( $suffix );
            $alt_user_nicename   = mb_substr( $user_nicename, 0, $base_length ) . "-$suffix";
            $user_nicename_check = $wpdb->get_var( $wpdb->prepare( "SELECT ID FROM $wpdb->users WHERE user_nicename = %s AND user_login != %s LIMIT 1", $alt_user_nicename, $user_login ) );
            $suffix++;
        }
        $user_nicename = $alt_user_nicename;
    }

    $raw_user_email = empty( $userdata['user_email'] ) ? '' : $userdata['user_email'];

    /**
     * Filters a user's email before the user is created or updated.
     *
     * @since 2.0.3
     *
     * @param string $raw_user_email The user's email.
     */
    $user_email = apply_filters( 'pre_user_email', $raw_user_email );

    /*
     * If there is no update, just check for `email_exists`. If there is an update,
     * check if current email and new email are the same, and check `email_exists`
     * accordingly.
     */
    if ( ( ! $update || ( ! empty( $old_user_data ) && 0 !== strcasecmp( $user_email, $old_user_data->user_email ) ) )
         && ! defined( 'WP_IMPORTING' )
         && email_exists( $user_email )
    ) {
        return new WP_Error( 'existing_user_email', __( 'Sorry, that email address is already used!' ) );
    }

    $raw_user_url = empty( $userdata['user_url'] ) ? '' : $userdata['user_url'];

    /**
     * Filters a user's URL before the user is created or updated.
     *
     * @since 2.0.3
     *
     * @param string $raw_user_url The user's URL.
     */
    $user_url = apply_filters( 'pre_user_url', $raw_user_url );

    $user_registered = empty( $userdata['user_registered'] ) ? gmdate( 'Y-m-d H:i:s' ) : $userdata['user_registered'];

    $user_activation_key = empty( $userdata['user_activation_key'] ) ? '' : $userdata['user_activation_key'];

    if ( ! empty( $userdata['spam'] ) && ! is_multisite() ) {
        return new WP_Error( 'no_spam', __( 'Sorry, marking a user as spam is only supported on Multisite.' ) );
    }

    $spam = empty( $userdata['spam'] ) ? 0 : (bool) $userdata['spam'];

    // Store values to save in user meta.
    $meta = array();

    $nickname = empty( $userdata['nickname'] ) ? $user_login : $userdata['nickname'];

    /**
     * Filters a user's nickname before the user is created or updated.
     *
     * @since 2.0.3
     *
     * @param string $nickname The user's nickname.
     */
    $meta['nickname'] = apply_filters( 'pre_user_nickname', $nickname );

    $first_name = empty( $userdata['first_name'] ) ? '' : $userdata['first_name'];

    /**
     * Filters a user's first name before the user is created or updated.
     *
     * @since 2.0.3
     *
     * @param string $first_name The user's first name.
     */
    $meta['first_name'] = apply_filters( 'pre_user_first_name', $first_name );

    $last_name = empty( $userdata['last_name'] ) ? '' : $userdata['last_name'];

    /**
     * Filters a user's last name before the user is created or updated.
     *
     * @since 2.0.3
     *
     * @param string $last_name The user's last name.
     */
    $meta['last_name'] = apply_filters( 'pre_user_last_name', $last_name );

    if ( empty( $userdata['display_name'] ) ) {
        if ( $update ) {
            $display_name = $user_login;
        } elseif ( $meta['first_name'] && $meta['last_name'] ) {
            /* translators: 1: User's first name, 2: Last name. */
            $display_name = sprintf( _x( '%1$s %2$s', 'Display name based on first name and last name' ), $meta['first_name'], $meta['last_name'] );
        } elseif ( $meta['first_name'] ) {
            $display_name = $meta['first_name'];
        } elseif ( $meta['last_name'] ) {
            $display_name = $meta['last_name'];
        } else {
            $display_name = $user_login;
        }
    } else {
        $display_name = $userdata['display_name'];
    }

    /**
     * Filters a user's display name before the user is created or updated.
     *
     * @since 2.0.3
     *
     * @param string $display_name The user's display name.
     */
    $display_name = apply_filters( 'pre_user_display_name', $display_name );

    $description = empty( $userdata['description'] ) ? '' : $userdata['description'];

    /**
     * Filters a user's description before the user is created or updated.
     *
     * @since 2.0.3
     *
     * @param string $description The user's description.
     */
    $meta['description'] = apply_filters( 'pre_user_description', $description );

    $meta['rich_editing'] = empty( $userdata['rich_editing'] ) ? 'true' : $userdata['rich_editing'];

    $meta['syntax_highlighting'] = empty( $userdata['syntax_highlighting'] ) ? 'true' : $userdata['syntax_highlighting'];

    $meta['comment_shortcuts'] = empty( $userdata['comment_shortcuts'] ) || 'false' === $userdata['comment_shortcuts'] ? 'false' : 'true';

    $admin_color         = empty( $userdata['admin_color'] ) ? 'fresh' : $userdata['admin_color'];
    $meta['admin_color'] = preg_replace( '|[^a-z0-9 _.\-@]|i', '', $admin_color );

    $meta['use_ssl'] = empty( $userdata['use_ssl'] ) ? 0 : (bool) $userdata['use_ssl'];

    $meta['show_admin_bar_front'] = empty( $userdata['show_admin_bar_front'] ) ? 'true' : $userdata['show_admin_bar_front'];

    $meta['locale'] = isset( $userdata['locale'] ) ? $userdata['locale'] : '';

    $compacted = compact('ID', 'user_pass', 'user_nicename', 'user_email', 'user_url', 'user_registered', 'user_activation_key', 'display_name' );

    $data      = wp_unslash( $compacted );

    if ( ! $update ) {
        $data = $data + compact( 'user_login' );
    }

    if ( is_multisite() ) {
        $data = $data + compact( 'spam' );
    }

    /**
     * Filters user data before the record is created or updated.
     *
     * It only includes data in the users table, not any user metadata.
     *
     * @since 4.9.0
     *
     * @param array    $data {
     *     Values and keys for the user.
     *
     *     @type string $user_login      The user's login. Only included if $update == false
     *     @type string $user_pass       The user's password.
     *     @type string $user_email      The user's email.
     *     @type string $user_url        The user's url.
     *     @type string $user_nicename   The user's nice name. Defaults to a URL-safe version of user's login
     *     @type string $display_name    The user's display name.
     *     @type string $user_registered MySQL timestamp describing the moment when the user registered. Defaults to
     *                                   the current UTC timestamp.
     * }
     * @param bool     $update Whether the user is being updated rather than created.
     * @param int|null $id     ID of the user to be updated, or NULL if the user is being created.
     */
    $data = apply_filters( 'wp_pre_insert_user_data', $data, $update, $update ? (int) $ID : null );

    if ( empty( $data ) || ! is_array( $data ) ) {
        return new WP_Error( 'empty_data', __( 'Not enough data to create this user.' ) );
    }

    if ( $update ) {
        if ( $user_email !== $old_user_data->user_email || $user_pass !== $old_user_data->user_pass ) {
            $data['user_activation_key'] = '';
        }
        $wpdb->update( $wpdb->users, $data, compact( 'ID' ) );
        $user_id = (int) $ID;
    } else {
        $wpdb->insert( $wpdb->users, $data );
        $user_id = (int) $wpdb->insert_id;
    }

    $user = new WP_User( $user_id );

    /**
     * Filters a user's meta values and keys immediately after the user is created or updated
     * and before any user meta is inserted or updated.
     *
     * Does not include contact methods. These are added using `wp_get_user_contact_methods( $user )`.
     *
     * @since 4.4.0
     *
     * @param array $meta {
     *     Default meta values and keys for the user.
     *
     *     @type string   $nickname             The user's nickname. Default is the user's username.
     *     @type string   $first_name           The user's first name.
     *     @type string   $last_name            The user's last name.
     *     @type string   $description          The user's description.
     *     @type string   $rich_editing         Whether to enable the rich-editor for the user. Default 'true'.
     *     @type string   $syntax_highlighting  Whether to enable the rich code editor for the user. Default 'true'.
     *     @type string   $comment_shortcuts    Whether to enable keyboard shortcuts for the user. Default 'false'.
     *     @type string   $admin_color          The color scheme for a user's admin screen. Default 'fresh'.
     *     @type int|bool $use_ssl              Whether to force SSL on the user's admin area. 0|false if SSL
     *                                          is not forced.
     *     @type string   $show_admin_bar_front Whether to show the admin bar on the front end for the user.
     *                                          Default 'true'.
     *     @type string   $locale               User's locale. Default empty.
     * }
     * @param WP_User $user   User object.
     * @param bool    $update Whether the user is being updated rather than created.
     */
    $meta = apply_filters( 'insert_user_meta', $meta, $user, $update );

    // Update user meta.
    foreach ( $meta as $key => $value ) {
        update_user_meta( $user_id, $key, $value );
    }

    foreach ( wp_get_user_contact_methods( $user ) as $key => $value ) {
        if ( isset( $userdata[ $key ] ) ) {
            update_user_meta( $user_id, $key, $userdata[ $key ] );
        }
    }

    if ( isset( $userdata['role'] ) ) {
        $user->set_role( $userdata['role'] );
    } elseif ( ! $update ) {
        $user->set_role( get_option( 'default_role' ) );
    }

    clean_user_cache( $user_id );

    if ( $update ) {
        /**
         * Fires immediately after an existing user is updated.
         *
         * @since 2.0.0
         *
         * @param int     $user_id       User ID.
         * @param WP_User $old_user_data Object containing user's data prior to update.
         */
        do_action( 'profile_update', $user_id, $old_user_data );

        if ( isset( $userdata['spam'] ) && $userdata['spam'] != $old_user_data->spam ) {
            if ( 1 == $userdata['spam'] ) {
                /**
                 * Fires after the user is marked as a SPAM user.
                 *
                 * @since 3.0.0
                 *
                 * @param int $user_id ID of the user marked as SPAM.
                 */
                do_action( 'make_spam_user', $user_id );
            } else {
                /**
                 * Fires after the user is marked as a HAM user. Opposite of SPAM.
                 *
                 * @since 3.0.0
                 *
                 * @param int $user_id ID of the user marked as HAM.
                 */
                do_action( 'make_ham_user', $user_id );
            }
        }
    } else {
        /**
         * Fires immediately after a new user is registered.
         *
         * @since 1.5.0
         *
         * @param int $user_id User ID.
         */
        do_action( 'user_register', $user_id );
    }

    return $user_id;
}
