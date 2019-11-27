<?php

namespace Deep_Web_Solutions\Plugins;
use Deep_Web_Solutions\Plugins\ACF\ACF_Customization;
use Deep_Web_Solutions\Plugins\ACF\ACF_Custom_Field_Types;
use Deep_Web_Solutions\Core\DWS_Functionality_Template;

if (!defined('ABSPATH')) { exit; }

/**
 * Adapter for the ACF Pro plugin.
 *
 * @since   2.0.0
 * @version 2.0.0
 * @author  Fatine Tazi <f.tazi@deep-web-solutions.de>
 *
 * @wordpress-plugin
 * Plugin Name:         DeepWebSolutions ACF Pro Compatibility
 * Description:         This plugin handles all the core custom extensions to the 'ACF Pro' plugin.
 * Version:             2.0.0
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
     * @since   2.0.0
     * @version 2.0.0
     *
     * @see     DWS_Functionality_Template::load_dependencies()
     */
    protected function load_dependencies() {
        /** @noinspection PhpIncludeInspection */
        /** Handles customizations to the ACF fields and their functionalities. */
        require_once(self::get_includes_base_path() . 'class-acf-customizations.php');
        ACF_Customization::maybe_initialize_singleton('h7843gh834g4g4', true, self::get_root_id());

        /** @noinspection PhpIncludeInspection */
        /** Register new types of ACF fields. */
        require_once(self::get_includes_base_path() . 'custom-field-types/custom-field-types.php');
        ACF_Custom_Field_Types::maybe_initialize_singleton('h478gh8g2113');

        /** @noinspection PhpIncludeInspection */
        /** Provides enhancements to WC payment methods. */
        require_once(self::get_includes_base_path() . 'class-permissions.php');

        /** @noinspection PhpIncludeInspection */
        /** Provides enhancements to WC payment methods. */
        require_once(self::get_includes_base_path() . 'class-acf-settings-adapter.php');
        ACF\DWS_ACFPro_Adapter::maybe_initialize_singleton('rgfjn87uy4578yhbf67', true, self::get_root_id());
    }

    //endregion
} ACF_Pro_Compatibility::maybe_initialize_singleton('dh8gfh38g7hr38gd', true);