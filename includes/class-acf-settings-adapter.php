<?php

namespace Deep_Web_Solutions\Plugins\ACF;
use Deep_Web_Solutions\Admin\DWS_Settings;
use Deep_Web_Solutions\Admin\Settings\DWS_Adapter;
use Deep_Web_Solutions\Admin\Settings\DWS_Adapter_Base;
use Deep_Web_Solutions\Admin\Settings\DWS_Settings_Pages;

if (!defined('ABSPATH')) { exit; }

/**
 * Settings adapter for the ACF Pro plugin.
 *
 * @since   2.0.0
 * @version 2.0.0
 * @author  Fatine Tazi <f.tazi@deep-web-solutions.de>
 *
 * @see     DWS_Functionality_Template
 */
final class DWS_ACFPro_Adapter extends DWS_Adapter_Base implements DWS_Adapter {
    //region FIELDS AND CONSTANTS

    /**
     * @since   2.0.0
     * @version 2.0.0
     *
     * @var     string      GROUP_KEY_PREFIX        All group keys must begin with 'field_'
     */
    private const GROUP_KEY_PREFIX = 'group_';

    /**
     * @since   2.0.0
     * @version 2.0.0
     *
     * @var     string      FIELD_KEY_PREFIX        All fields keys must begin with 'field_'
     */
    private const FIELD_KEY_PREFIX = 'field_';

    //endregion

    //region CLASS INHERITED FUNCTIONS

    /**
     * @since   2.0.0
     * @version 2.0.0
     *
     * @see     DWS_Functionality_Template::define_functionality_hooks()
     *
     * @param   \Deep_Web_Solutions\Core\DWS_WordPress_Loader   $loader
     */
    protected function define_functionality_hooks($loader) {
        parent::define_functionality_hooks($loader);
        $loader->add_action(self::get_hook_name('init'), $this, 'add_floating_update_button', PHP_INT_MAX - 100);
    }

    /**
     * @since   2.0.0
     * @version 2.0.0
     *
     * @see     DWS_Adapter_Base::set_framework_slug()
     */
    public function set_fields() {
        $this->framework_slug = 'acf-pro';
        $this->init_hook = 'acf/init';
    }

    //endregion

    //region INTERFACE INHERITED FUNCTIONS

    /**
     * @since   2.0.0
     * @version 2.0.0
     *
     * @param   string  $page_title
     * @param   string  $menu_title
     * @param   string  $capability
     * @param   string  $menu_slug
     * @param   array   $other
     *
     * @return  false|array     The validated and final page settings.
     */
    public static function register_settings_page($page_title, $menu_title, $capability, $menu_slug, $other = array()) {
        if (!function_exists('acf_add_options_page')) { return false; }

        $args = wp_parse_args($other, array(
            'page_title'        => $page_title,
            'menu_title'        => $menu_title,
            'menu_slug'         => $menu_slug,
            'capability'        => $capability
        ));

        return acf_add_options_page($args);
    }

    /**
     * @since   2.0.0
     * @version 2.0.0
     *
     * @param   string  $parent_slug
     * @param   string  $page_title
     * @param   string  $menu_title
     * @param   string  $capability
     * @param   string  $menu_slug
     * @param   array   $other
     *
     * @return  false|array
     */
    public static function register_settings_subpage($parent_slug, $page_title, $menu_title, $capability, $menu_slug, $other = array()) {
        if (!function_exists('acf_add_options_sub_page')) { return false; }

        $args = wp_parse_args($other, array(
            'page_title'        => $page_title,
            'menu_title'        => $menu_title,
            'menu_slug'         => $menu_slug,
            'capability'        => $capability,
            'parent_slug'       => $parent_slug
        ));

        return acf_add_options_sub_page($args);
    }

    /**
     * @since   2.0.0
     * @version 2.0.0
     *
     * @param   string  $key
     * @param   string  $title
     * @param   string  $location
     * @param   array   $other
     */
    public static function register_options_page_group($key, $title, $location, $other = array()) {
        if (!function_exists('acf_add_local_field_group')) { return; }

        $key = strpos($key, 'group_') === 0 ? $key : self::GROUP_KEY_PREFIX . $key; // Must begin with 'group_'
        $other['key'] = $key;
        $args = wp_parse_args($other, array(
            'key'                   => $key,
            'title'                 => $title,
            'fields'                => array(),
            'location'              => array(
                array(
                    array(
                        'param'     => 'options_page',
                        'operator'  => '==',
                        'value'     => $location
                    ),
                ),
            ),
            'menu_order'            => 0,
            'position'              => 'normal', // Choices of 'acf_after_title', 'normal' or 'side'
            'style'                 => 'default', // Choices of 'default' or 'seamless'
            'label_placement'       => 'top', // Choices of 'top' (Above fields) or 'left' (Beside fields)
            'instruction_placement' => 'label', // Choices of 'label' (Below labels) or 'field' (Below fields)
            'hide_on_screen'        => ''
        ));

        acf_add_local_field_group($args);
    }

    /**
     * @since   2.0.0
     * @version 2.0.0
     *
     * @param   string              $group_id
     * @param   string              $key
     * @param   string              $type
     * @param   array               $parameters
     * @param   string              $location
     */
    public static function register_options_group_field($group_id, $key, $type, $parameters, $location = null){
        if (!function_exists('acf_add_local_field')) { return; }

        $group_id = (strpos($group_id, 'field_') === 0 || strpos($group_id, 'group_') === 0)
            ? $group_id
            : (self::GROUP_KEY_PREFIX . $group_id);

        $parameters['parent'] = $group_id;

        acf_add_local_field(self::formatting_settings_field($key, $type, $group_id, $parameters));
    }

    /**
     * @since   2.0.0
     * @version 2.0.0
     *
     * @param   string  $key
     * @param   string  $type
     * @param   string  $parent_id
     * @param   array   $parameters
     * @param   null    $location
     */
    public static function register_settings_field($key, $type, $parent_id, $parameters, $location = null) {
        if (!function_exists('acf_add_local_field')) { return; }

        acf_add_local_field(self::formatting_settings_field($key, $type, $parent_id, $parameters));
    }

    /**
     * @param   string  $field
     * @param   string  $option_page_slug
     *
     * @return  mixed   Option value.
     *@version 2.0.0
     *
     * @since   2.0.0
     */
    public static function get_options_field_value($field, $option_page_slug = null) {
        if (!function_exists('get_field')) { return null; }
        return get_field($field, 'option');
    }

    //endregion

    //region COMPATIBILITY LOGIC

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
            wp_enqueue_style( DWS_Settings_Pages::get_asset_handle('floating-button-style'), DWS_Settings::get_assets_base_path( true ) . 'style.css', array(), DWS_Settings_Pages::get_plugin_version(), 'all' );
            wp_enqueue_script(DWS_Settings_Pages::get_asset_handle('floating-button'), DWS_Settings::get_assets_base_path( true ) . 'floating-update-button.js', array( 'jquery' ), DWS_Settings_Pages::get_plugin_version(), true);
        }
    }

    //endregion

    //region HELPERS

    /**
     * @since   2.0.0
     * @version 2.0.0
     *
     * @param   string  $location_id
     * @param   string  $key
     * @param   string  $type
     * @param   array   $parameters
     *
     * @return  array   Formatted array for registering generic ACF field
     */
    private static function formatting_settings_field($key, $type, $location_id, $parameters) {
        $key = strpos($key, 'field_') === 0 ? $key : self::FIELD_KEY_PREFIX . $key; // Must begin with 'field_'
        $parameters['key'] = $key;

        return wp_parse_args($parameters, array(
            'type'              => $type,
            'parent'            => $location_id
        ));
    }

    //endregion
}