<?php

namespace Deep_Web_Solutions\Plugins;
use Deep_Web_Solutions\Base\DWS_Functionality_Template;
use Deep_Web_Solutions\Admin\Settings\DWS_Settings_Pages;

if (!defined('ABSPATH')) { exit; }

/**
 * Adapter for the ACF Pro plugin.
 *
 * @since   2.0.0
 * @version 2.0.6
 * @author  Fatine Tazi <f.tazi@deep-web-solutions.de>
 *
 * @wordpress-plugin
 * Plugin Name:         DeepWebSolutions ACF Pro Compatibility
 * Description:         This plugin handles all the core custom extensions to the 'ACF Pro' plugin.
 * Version:             2.0.6
 * Author:              Deep Web Solutions GmbH
 * Author URI:          https://www.deep-web-solutions.de
 * License:             GPL-3.0+
 * License URI:         http://www.gnu.org/licenses/gpl-3.0.txt
 * Text Domain:         dws_custom-extensions_dh8gfh38g7hr38gd
 * Domain Path:         /languages
 */
final class ACF_Pro_Compatibility extends DWS_Functionality_Template {
    //region INHERITED FUNCTIONS

    /**
     * @since   2.0.0
     * @version 2.0.0
     *
     * @see     DWS_Functionality_Template::are_prerequisites_fulfilled()
     *
     * @return  bool
     */
    protected static function are_prerequisites_fulfilled() {
        return is_plugin_active('advanced-custom-fields-pro/acf.php');
    }

    /**
     * @since   2.0.4
     * @version 2.0.6
     *
     * @see     DWS_Functionality_Template::define_functionality_hooks()
     *
     * @param   \Deep_Web_Solutions\Core\DWS_Loader     $loader
     */
    protected function define_functionality_hooks($loader) {
        $loader->add_action('init', $this, 'delay_acf_init', PHP_INT_MIN);
        $loader->add_action('init', $this, 'add_floating_update_button', PHP_INT_MAX - 100);
    }

    /**
     * @since   2.0.0
     * @version 2.0.0
     *
     * @see     DWS_Functionality_Template::load_dependencies()
     */
    protected function load_dependencies() {
        /** @noinspection PhpIncludeInspection */
        /** Force load ACF at this point in time ... */
        require_once(WP_PLUGIN_DIR . '/advanced-custom-fields-pro/acf.php');

        /** @noinspection PhpIncludeInspection */
        /** Handles customizations to the ACF fields and their functionalities. */
        require_once(self::get_includes_base_path() . 'class-acf-customizations.php');
        ACF\ACF_Customization::maybe_initialize_singleton('h7843gh834g4g4', true, self::get_root_id());

        /** @noinspection PhpIncludeInspection */
        /** Register new types of ACF fields. */
        require_once(self::get_includes_base_path() . 'custom-field-types/custom-field-types.php');
        ACF\ACF_Custom_Field_Types::maybe_initialize_singleton('h478gh8g2113');

        /** @noinspection PhpIncludeInspection */
        /** The ACF Pro Adapter. */
        require_once(self::get_includes_base_path() . 'class-acf-settings-adapter.php');
        ACF\DWS_ACFPro_Adapter::maybe_initialize_singleton('rgfjn87uy4578yhbf67', true, self::get_root_id());
    }

    //endregion

    //region COMPATIBILITY LOGIC

    /**
     * Some plugins also init on 5, like WC, which causes their functions not to be usable yet. This causes ACF's
     * init to happen on 8, making sure those plugins are loaded first.
     *
     * @since   2.0.4
     * @version 2.0.4
     */
    public function delay_acf_init() {
        remove_action('init', array(acf(), 'init'), 5);
        add_action('init', array(acf(), 'init'), 8);
    }

    /**
     * If the current page is the General Settings in DWS Custom Extensions then enqueue some scripts.
     *
     * @author  Dushan Terzikj  <d.terzikj@deep-web-solutions.de>
     *
     * @since   1.3.3
     * @version 2.0.0
     */
    public function add_floating_update_button(){
        if (isset($_REQUEST['page']) && strpos($_REQUEST['page'], DWS_Settings_Pages::MENU_PAGES_SLUG_PREFIX) === 0) {
            wp_enqueue_style(DWS_Settings_Pages::get_asset_handle('floating-button-style'), self::get_assets_base_path(true) . 'style.css', array(), self::get_plugin_version(), 'all' );
            wp_enqueue_script(DWS_Settings_Pages::get_asset_handle('floating-button'), self::get_assets_base_path(true) . 'floating-update-button.js', array( 'jquery' ), self::get_plugin_version(), true);
        }
    }

    //endregion
} ACF_Pro_Compatibility::maybe_initialize_singleton('dh8gfh38g7hr38gd', true);