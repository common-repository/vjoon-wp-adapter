<?php

/**
 * Class Authorization
 *
 * override WP Function which prevents get wp_get_session_token() after wp_set_auth_cookie() without reload
 *
 * @author Christian Storm
 * @package vjoon\Adapter
 * @copyright 2019 vjoon GmbH
 *
 */
namespace vjoon\Adapter;

if ( ! defined ( 'ABSPATH') ) exit; //exit if accessed directly

final class Authorization {

    /**
     * override original WP Function, add parameter Token
     *
     * @param integer $action
     * @param [type] $token
     * @return void
     */
    public static function wp_create_nonce( $action = -1 , $token) {
        $user = wp_get_current_user();
        $uid  = (int) $user->ID;
        if ( ! $uid ) {
            /** This filter is documented in wp-includes/pluggable.php */
            $uid = apply_filters( 'nonce_user_logged_out', $uid, $action );
        }

        //$token = wp_get_session_token(); //original WP Code
        $i     = wp_nonce_tick();

        return substr( wp_hash( $i . '|' . $action . '|' . $uid . '|' . $token, 'nonce' ), -12, 10 );
    }

    /**
     * override original WP Function, add Token return
     *
     * @param [type] $user_id
     * @param boolean $remember
     * @param string $secure
     * @param string $token
     * @return void
     */
    public static function wp_set_auth_cookie( $user_id, $remember = false, $secure = '', $token = '' ) {
        if ( $remember ) {
            /**
             * Filters the duration of the authentication cookie expiration period.
             *
             * @since 2.8.0
             *
             * @param int  $length   Duration of the expiration period in seconds.
             * @param int  $user_id  User ID.
             * @param bool $remember Whether to remember the user login. Default false.
             */
            $expiration = time() + apply_filters( 'auth_cookie_expiration', 14 * DAY_IN_SECONDS, $user_id, $remember );

            /*
             * Ensure the browser will continue to send the cookie after the expiration time is reached.
             * Needed for the login grace period in wp_validate_auth_cookie().
             */
            $expire = $expiration + ( 12 * HOUR_IN_SECONDS );
        } else {
            /** This filter is documented in wp-includes/pluggable.php */
            $expiration = time() + apply_filters( 'auth_cookie_expiration', 2 * DAY_IN_SECONDS, $user_id, $remember );
            $expire     = 0;
        }

        if ( '' === $secure ) {
            $secure = is_ssl();
        }

        // Front-end cookie is secure when the auth cookie is secure and the site's home URL is forced HTTPS.
        $secure_logged_in_cookie = $secure && 'https' === parse_url( get_option( 'home' ), PHP_URL_SCHEME );

        /**
         * Filters whether the connection is secure.
         *
         * @since 3.1.0
         *
         * @param bool $secure  Whether the connection is secure.
         * @param int  $user_id User ID.
         */
        $secure = apply_filters( 'secure_auth_cookie', $secure, $user_id );

        /**
         * Filters whether to use a secure cookie when logged-in.
         *
         * @since 3.1.0
         *
         * @param bool $secure_logged_in_cookie Whether to use a secure cookie when logged-in.
         * @param int  $user_id                 User ID.
         * @param bool $secure                  Whether the connection is secure.
         */
        $secure_logged_in_cookie = apply_filters( 'secure_logged_in_cookie', $secure_logged_in_cookie, $user_id, $secure );

        if ( $secure ) {
            $auth_cookie_name = SECURE_AUTH_COOKIE;
            $scheme           = 'secure_auth';
        } else {
            $auth_cookie_name = AUTH_COOKIE;
            $scheme           = 'auth';
        }

        if ( '' === $token ) {
            $manager = \WP_Session_Tokens::get_instance( $user_id );
            $token   = $manager->create( $expiration );
        }

        $auth_cookie      = wp_generate_auth_cookie( $user_id, $expiration, $scheme, $token );
        $logged_in_cookie = wp_generate_auth_cookie( $user_id, $expiration, 'logged_in', $token );

        /**
         * Fires immediately before the authentication cookie is set.
         *
         * @since 2.5.0
         * @since 4.9.0 The `$token` parameter was added.
         *
         * @param string $auth_cookie Authentication cookie value.
         * @param int    $expire      The time the login grace period expires as a UNIX timestamp.
         *                            Default is 12 hours past the cookie's expiration time.
         * @param int    $expiration  The time when the authentication cookie expires as a UNIX timestamp.
         *                            Default is 14 days from now.
         * @param int    $user_id     User ID.
         * @param string $scheme      Authentication scheme. Values include 'auth' or 'secure_auth'.
         * @param string $token       User's session token to use for this cookie.
         */
        do_action( 'set_auth_cookie', $auth_cookie, $expire, $expiration, $user_id, $scheme, $token );

        /**
         * Fires immediately before the logged-in authentication cookie is set.
         *
         * @since 2.6.0
         * @since 4.9.0 The `$token` parameter was added.
         *
         * @param string $logged_in_cookie The logged-in cookie value.
         * @param int    $expire           The time the login grace period expires as a UNIX timestamp.
         *                                 Default is 12 hours past the cookie's expiration time.
         * @param int    $expiration       The time when the logged-in authentication cookie expires as a UNIX timestamp.
         *                                 Default is 14 days from now.
         * @param int    $user_id          User ID.
         * @param string $scheme           Authentication scheme. Default 'logged_in'.
         * @param string $token            User's session token to use for this cookie.
         */
        do_action( 'set_logged_in_cookie', $logged_in_cookie, $expire, $expiration, $user_id, 'logged_in', $token );

        /**
         * Allows preventing auth cookies from actually being sent to the client.
         *
         * @since 4.7.4
         *
         * @param bool $send Whether to send auth cookies to the client.
         */
        if ( ! apply_filters( 'send_auth_cookies', true ) ) {
            return $token;
        }

        setcookie( $auth_cookie_name, $auth_cookie, $expire, PLUGINS_COOKIE_PATH, COOKIE_DOMAIN, $secure, true );
        setcookie( $auth_cookie_name, $auth_cookie, $expire, ADMIN_COOKIE_PATH, COOKIE_DOMAIN, $secure, true );
        setcookie( LOGGED_IN_COOKIE, $logged_in_cookie, $expire, COOKIEPATH, COOKIE_DOMAIN, $secure_logged_in_cookie, true );
        if ( COOKIEPATH != SITECOOKIEPATH ) {
            setcookie( LOGGED_IN_COOKIE, $logged_in_cookie, $expire, SITECOOKIEPATH, COOKIE_DOMAIN, $secure_logged_in_cookie, true );
        }

        return $token;
    }
}
