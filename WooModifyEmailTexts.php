<?php
/**
 * Plugin Name: Modify Email Texts For WooCommerce
 * Description: Modify Email Texts For WooCommerce that aren't available as part of the email template settings
 * Author:      buttered_toast
 * Version:     1.0.0
 * Text Domain: bt-mwet
 * Domain Path: /languages
 * License:     GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 */

namespace WooModifyEmailTexts;

if (!defined('ABSPATH')) {
    die("Whoa! What are you doing here?<br>You like breaking the rules, don't you?");
}

if (!function_exists('\WooModifyEmailTexts\btmwetLoadTextdomain')) {
    /**
     * Load the localization for the plugin
     *
     * @return void
     */
    function btmwetLoadTextdomain()
    {
        load_plugin_textdomain('bt-mwet', false, dirname( plugin_basename( __FILE__ ) ) . '/languages'); 
    }

    add_action('init', '\WooModifyEmailTexts\btmwetLoadTextdomain');
}

if (!function_exists('\WooModifyEmailTexts\btmwetWoocommerceRequired')) {
    /**
     * Displays a message that explains the plugin
     * cannot work without WooCommerce
     *
     * @return void
     */
    function btmwetWoocommerceRequired() {
        // Don't display the admin notice if WooCommerce is active
        if (class_exists('woocommerce')) {
            return;
        }
        ?>
        <div class="notice notice-warning is-dismissible">
            <p><?php esc_html_e('The plugin "Modify Email Texts For WooCommerce" cannot work without WooCommerce.', 'bt-mwet'); ?></p>
            <p><?php esc_html_e('Please make sure you have it installed and activated.', 'bt-mwet'); ?></p>
        </div>
        <?php
    }

    add_action('admin_notices', '\WooModifyEmailTexts\btmwetWoocommerceRequired');
}

require __DIR__ . DIRECTORY_SEPARATOR . 'Includes' . DIRECTORY_SEPARATOR . 'WooEmailTexts.class.php';

/**
 * Make sure that the WooEmailTexts class exists
 * and has the init method.
 * An additional precaution to prevent fatal error
 * in case class name or bootstrap method changes
 */
if (class_exists('\WooModifyEmailTexts\Includes\WooEmailTexts')) {
    $wooEmailTexts = new Includes\WooEmailTexts();

    if (method_exists($wooEmailTexts, 'init')) {
        $wooEmailTexts->init();
    }
}
