<?php
/**
 * Plugin Name: Complianz Privacy Suite (GDPR/CaCPA)
 * Plugin URI: https://www.complianz.io/complianz-gdpr
 * Description: Complianz Privacy Suite (GDPR/CaCPA) with a conditional cookie warning and customized cookie policy
 * Version: 2.0.7
 * Text Domain: complianz
 * Domain Path: /config/languages
 * Author: RogierLankhorst, Complianz team
 * Author URI: https://www.complianz.io
 */

/*
    Copyright 2018  Complianz BV  (email : info@complianz.io)

    This program is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License, version 2, as
    published by the Free Software Foundation.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program; if not, write to the Free Software
    Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA

*/

defined('ABSPATH') or die("you do not have access to this page!");

define('cmplz_free', true);
require_once(plugin_dir_path(__FILE__) . 'functions.php');
if (!class_exists('COMPLIANZ')) {
    class COMPLIANZ
    {

        private static $instance;
        public $cmplz_front_end;
        public $cmplz_admin;

        private function __construct()
        {
        }

        public static function instance()
        {

            if (!isset(self::$instance) && !(self::$instance instanceof COMPLIANZ)) {
                self::$instance = new COMPLIANZ;
                if (self::$instance->is_compatible()) {

                    self::$instance->setup_constants();
                    self::$instance->includes();

                    self::$instance->config = new cmplz_config();
                    self::$instance->integrations = new cmplz_integrations();
                    self::$instance->company = new cmplz_company();
                    if (cmplz_has_region('us')) self::$instance->DNSMPD = new cmplz_DNSMPD();

                    if (is_admin()) {
                        self::$instance->review = new cmplz_review();
                        self::$instance->admin = new cmplz_admin();
                        self::$instance->field = new cmplz_field();
                        self::$instance->wizard = new cmplz_wizard();
                    }

                    self::$instance->geoip = '';
                    self::$instance->cookie = new cmplz_cookie();

                    self::$instance->document = new cmplz_document();


                    if (cmplz_third_party_cookies_active()) {
                        self::$instance->cookie_blocker = new cmplz_cookie_blocker();
                    }

                    self::$instance->hooks();
                }

            }

            return self::$instance;
        }

        /*
         * Compatiblity checks
         *
         * */

        private function is_compatible()
        {
            return true;
        }

        private function setup_constants()
        {
            require_once(ABSPATH . 'wp-admin/includes/plugin.php');
            $plugin_data = get_plugin_data(__FILE__);

            define('cmplz_url', plugin_dir_url(__FILE__));
            define('cmplz_path', plugin_dir_path(__FILE__));
            define('cmplz_plugin', plugin_basename(__FILE__));
            $debug = (defined('WP_DEBUG') && WP_DEBUG) ? time() : '';
            define('cmplz_version', $plugin_data['Version'] . $debug);
            define('cmplz_plugin_file', __FILE__);
        }

        private function includes()
        {
            require_once(cmplz_path . 'core/php/class-document-core.php');
            require_once(cmplz_path . 'class-document.php');
            require_once(cmplz_path . 'class-form.php');

            if (is_admin()) {
                require_once(cmplz_path . 'class-admin.php');
                require_once(cmplz_path . 'class-review.php');
                require_once(cmplz_path . 'class-field.php');
                require_once(cmplz_path . 'class-wizard.php');
                require_once(cmplz_path . 'callback-notices.php');
            }

            require_once(cmplz_path . 'cron/cron.php');
            require_once(cmplz_path . 'class-cookie.php');
            require_once(cmplz_path . 'integrations.php');
            require_once(cmplz_path . 'class-company.php');
            require_once(cmplz_path . 'DNSMPD/class-DNSMPD.php');
            require_once(cmplz_path . 'integrations.php');

            require_once(cmplz_path . 'config/class-config.php');
            require_once(cmplz_path . 'core/php/class-cookie-blocker.php');

        }

        private function hooks()
        {
            add_action('init', 'cmplz_init_cookie_blocker');
            add_action('wp_ajax_nopriv_cmplz_user_settings', 'cmplz_ajax_user_settings');
            add_action('wp_ajax_cmplz_user_settings', 'cmplz_ajax_user_settings');
        }
    }
}

if (!function_exists('COMPLIANZ')) {
    function COMPLIANZ() {
        return COMPLIANZ::instance();
    }

    add_action( 'plugins_loaded', 'COMPLIANZ', 9 );
}

register_activation_hook( __FILE__, 'cmplz_set_activation_time_stamp');
if (!function_exists('cmplz_set_activation_time_stamp')) {
    function cmplz_set_activation_time_stamp($networkwide)
    {
        update_option('cmplz_activation_time', time());
    }
}