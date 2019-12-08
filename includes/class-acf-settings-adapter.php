<?php

namespace Deep_Web_Solutions\Plugins\ACF;
use Deep_Web_Solutions\Admin\Settings\DWS_Adapter;
use Deep_Web_Solutions\Admin\Settings\DWS_Adapter_Base;
use Deep_Web_Solutions\Admin\Settings\DWS_Settings_Pages;
use Deep_Web_Solutions\Core\DWS_Loader;
use Deep_Web_Solutions\Plugins\ACF_Pro_Compatibility;

if (!defined('ABSPATH')) { exit; }

/**
 * Settings adapter for the ACF Pro plugin.
 *
 * @since   2.0.0
 * @version 2.0.4
 * @author  Fatine Tazi <f.tazi@deep-web-solutions.de>
 *
 * @see     DWS_Adapter_Base
 * @see     DWS_Adapter
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
     * @param   DWS_Loader  $loader
     */
    protected function define_functionality_hooks($loader) {
        parent::define_functionality_hooks($loader);
        $loader->add_action('init', $this, 'add_floating_update_button', PHP_INT_MAX - 100);
    }

    /**
     * @since   2.0.0
     * @version 2.0.4
     *
     * @see     DWS_Adapter_Base::set_framework_slug()
     */
    public function set_fields() {
        $this->framework_slug = 'acf-pro';
        $this->init_hook = 'acf/include_fields';
        $this->update_field_hook = 'acf/update_value';
    }

    //endregion

    //region INTERFACE INHERITED FUNCTIONS

    /**
     * @since   2.0.0
     * @version 2.0.1
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

        $other['page_title'] = $page_title;
        $other['menu_title'] = $menu_title;
        $other['menu_slug'] = $menu_slug;
        $other['capability'] = $capability;

        return acf_add_options_page($other);
    }

    /**
     * @since   2.0.0
     * @version 2.0.1
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

        $other['page_title'] = $page_title;
        $other['menu_title'] = $menu_title;
        $other['menu_slug'] = $menu_slug;
        $other['capability'] = $capability;
        $other['parent_slug'] = $parent_slug;

        return acf_add_options_sub_page($other);
    }

    /**
     * @since   2.0.0
     * @version 2.0.1
     *
     * @param   string  $key
     * @param   string  $title
     * @param   string  $location
     * @param   array   $fields,
     * @param   array   $other
     */
    public static function register_settings_page_group($key, $title, $location, $fields, $other = array()) {
        if (!function_exists('acf_add_local_field_group')) { return; }

        $key = (strpos($key, 'group_') === 0) ? $key : (self::GROUP_KEY_PREFIX . $key); // Must begin with 'group_'

        $other['key'] = $key;
        $other['title'] = $title;
        $other['fields'] = $fields;
        $other['location'] = array(
            array(
                array(
                    'param'     => 'options_page',
                    'operator'  => '==',
                    'value'     => $location
                )
            )
        );

        acf_add_local_field_group($other);
    }

    /**
     * @since   2.0.1
     * @version 2.0.1
     *
     * @param   string  $key
     * @param   string  $title
     * @param   array   $location
     * @param   array   $fields
     * @param   array   $other
     */
    public static function register_generic_group($key, $title, $location, $fields, $other = array()) {
        if (!function_exists('acf_add_local_field_group')) { return; }

        $key = (strpos($key, 'group_') === 0) ? $key : (self::GROUP_KEY_PREFIX . $key); // Must begin with 'group_'

        $other['key'] = $key;
        $other['title'] = $title;
        $other['fields'] = $fields;
        $other['location'] = $location;

        acf_add_local_field_group($other);
    }

    /**
     * @since   2.0.0
     * @version 2.0.3
     *
     * @param   string              $group_id
     * @param   string              $key
     * @param   string              $type
     * @param   array               $parameters
     * @param   string              $location
     */
    public static function register_field_to_group($group_id, $key, $type, $parameters, $location = null) {
        if (!function_exists('acf_add_local_field')) { return; }

        $group_id = (strpos($group_id, self::FIELD_KEY_PREFIX) === 0 || strpos($group_id, self::GROUP_KEY_PREFIX) === 0)
            ? $group_id
            : (self::GROUP_KEY_PREFIX . $group_id);
        $parameters['parent'] = $group_id;

        acf_add_local_field(self::format_field($key, $type, $group_id, $parameters));
    }

    /**
     * @since   2.0.0
     * @version 2.0.3
     *
     * @param   string  $key
     * @param   string  $type
     * @param   string  $parent_id
     * @param   array   $parameters
     * @param   null    $location
     */
    public static function register_field($key, $type, $parent_id, $parameters, $location = null) {
        if (!function_exists('acf_add_local_field')) { return; }

        $parent_id = (strpos($parent_id, self::GROUP_KEY_PREFIX) === 0)
            ? $parent_id
            : (self::GROUP_KEY_PREFIX . $parent_id);
        $parameters['parent'] = $parent_id;

        acf_add_local_field(self::format_field($key, $type, $parent_id, $parameters));
    }

    /**
     * @since   2.0.2
     * @version 2.0.2
     *
     * @param   string  $key
     * @param   null    $location
     */
    public static function remove_field($key, $location = null) {
        if (!function_exists('acf_remove_local_field')) { return; }
        acf_remove_local_field($key);
    }

    /**
     * @since   2.0.0
     * @version 2.0.0
     *
     * @param   string  $field
     * @param   string  $option_page_slug
     *
     * @return  mixed   Option value.
     */
    public static function get_settings_field_value($field, $option_page_slug = null) {
        if (function_exists('get_field') && did_action('acf/init')) {
            return get_field($field, 'option');
        } else {
            global $wpdb;
            $row = $wpdb->get_row( $wpdb->prepare( "SELECT option_name FROM $wpdb->options WHERE option_value = %s LIMIT 1", $field ) );

            return  get_option(substr($row->option_name, 1), '');
        }
    }

    /**
     * @since   2.0.2
     * @version 2.0.2
     *
     * @param   string      $field
     * @param   mixed       $new_value
     * @param   string      $option_page_slug
     *
     * @return  bool        True on successful update, false on failure.
     */
    public static function update_settings_field_value($field, $new_value, $option_page_slug = null){
        if (function_exists('update_field') && did_action('acf/init')) {
            return update_field($field, $new_value, 'option');
        } else {
            global $wpdb;
            $row = $wpdb->get_row( $wpdb->prepare( "SELECT option_name FROM $wpdb->options WHERE option_value = %s LIMIT 1", $field ) );

            return  update_option(substr($row->option_name, 1), $new_value);
        }
    }

    /**
     * @since   2.0.0
     * @version 2.0.0
     *
     * @param   string      $field
     * @param   false|int   $post_id
     *
     * @return  mixed   Option value.
     */
    public static function get_field_value($field, $post_id = false) {
        if (!function_exists('get_field')) { return null; }
        return get_field($field, $post_id);
    }

    /**
     * @since   2.0.2
     * @version 2.0.2
     *
     * @param   string      $field
     * @param   mixed       $new_value
     * @param   int|false   $post_id
     *
     * @return  bool        True on successful update, false on failure.
     */
    public static function update_field_value($field, $new_value, $post_id = false){
        if (!function_exists('update_field')) { return false; }
        return update_field($field, $new_value, $post_id);
    }

    /**
     * Outputs some CSS to completely hide a certain field from the screen, only for humans.
     *
     * @since   2.0.2
     * @version 2.0.2
     *
     * @see     DWS_ACFPro_Adapter::make_field_uneditable()
     *
     * @param   array   $field          ACF field in ACF format.
     * @param   bool    $do_on_ajax     True if the action should also be performed on AJAX requests, otherwise false.
     *
     * @return  array   The ACF field given as input but with modified values such that it becomes uneditable.
     */
    public static function css_hide_field($field, $do_on_ajax = false) {
        /**
         * @since   2.0.2
         * @version 2.0.2
         *
         * @param   bool    $should_skip_css_hiding_field   Whether the current ACF field should not be CSS hidden.
         * @param   array   $field                          The current ACF field.
         */
        if (apply_filters(parent::get_hook_name('skip-css-hiding-field'), false, $field)) {
            return $field;
        }
        if ((wp_doing_ajax() && !$do_on_ajax) || !is_admin()) {
            return $field;
        }

        if (wp_doing_ajax()) {
            echo '<style type="text/css">'; ?>
            [data-name='<?php echo $field['name']; ?>'] {
            display: none !important;
            }
            <?php echo '</style>';
        } else {
            add_action('admin_head', function() use ($field) {
                echo '<style type="text/css">'; ?>
                [data-name='<?php echo $field['name']; ?>'] {
                display: none !important;
                }
                <?php echo '</style>';
            }, 100);
        }

        if (isset($field['wrapper'])) {
            $field['wrapper']['width'] = '0%';
            if (isset($field['class'])) {
                $field['wrapper']['class'] .= ' hidden';
            } else {
                $field['wrapper']['class'] = 'hidden';
            }
        } else {
            $field['wrapper'] = array('width' => '0%', 'class' => 'hidden');
        }

        return self::make_field_uneditable($field);
    }

    /**
     * Make a field uneditable.
     *
     * @since   2.0.2
     * @version 2.0.2
     *
     * @param   array   $field          ACF field in ACF format.
     * @param   bool    $do_on_ajax     True if the action should also be performed on AJAX requests, otherwise false.
     *
     * @return  array   The ACF field given as input but with modified values such that it becomes uneditable.
     */
    public static function make_field_uneditable($field, $do_on_ajax = false) {
        /**
         * @since   2.0.2
         * @version 2.0.2
         *
         * @param   bool    $should_skip_making_field_uneditable    Whether the current ACF field should be kept editable or not.
         * @param   array   $field                                  The current ACF field.
         */
        if (apply_filters(parent::get_hook_name('skip-making-field-uneditable'), false, $field)) {
            return $field;
        }
        if ((wp_doing_ajax() && !$do_on_ajax) || !is_admin()) {
            return $field;
        }

        $field['class'] = isset($field['class']) ? $field['class'] . ' acf-disabled' : 'acf-disabled';
        switch ($field['type']) {
            case 'group':
                foreach ($field['sub_fields'] as &$sub_field) {
                    $sub_field = self::make_field_uneditable($sub_field);
                }
                break;
            case 'repeater':
                if (wp_doing_ajax()) {
                    echo '<style type="text/css">'; ?>
                    [data-key="<?php echo $field['key']; ?>"] .acf-actions,
                    [data-key="<?php echo $field['key']; ?>"] .acf-row-handle {
                    display: none;
                    }
                    <?php echo '</style>';
                } else {
                    add_action('admin_head', function() use ($field) {
                        echo '<style type="text/css">'; ?>
                        [data-key="<?php echo $field['key']; ?>"] .acf-actions,
                        [data-key="<?php echo $field['key']; ?>"] .acf-row-handle {
                        display: none;
                        }
                        <?php echo '</style>';
                    }, 100);
                }
                break;
            case 'true_false':
                if (wp_doing_ajax()) {
                    echo '<script type="text/javascript">'; ?>
                    function dws_defer_<?php echo $field['key']; ?>() {
                    jQuery('[data-key="<?php echo $field['key']; ?>"] input').on('click', function(e) { e.preventDefault(); });
                    }

                    dws_defer_until_jquery(dws_defer_<?php echo $field['key']; ?>);
                    <?php echo '</script>';
                } else {
                    add_action('admin_head', function() use ($field) {
                        echo '<script type="text/javascript">'; ?>
                        function dws_defer_<?php echo $field['key']; ?>() {
                        jQuery('[data-key="<?php echo $field['key']; ?>"] input').on('click', function(e) { e.preventDefault(); });
                        }

                        dws_defer_until_jquery(dws_defer_<?php echo $field['key']; ?>);
                        <?php echo '</script>';
                    }, 100);
                }
                break;
            case 'date_time_picker':
                if (wp_doing_ajax()) {
                    echo '<script type="text/javascript">'; ?>
                    function dws_defer_<?php echo $field['key']; ?>() {
                    function disable_<?php echo $field['key']; ?>() {
                    jQuery('div[data-key="<?php echo $field['key']; ?>"] input.input').attr('disabled', 'disabled');
                    }

                    disable_<?php echo $field['key'];?>();
                    jQuery(document).on('change', function() { disable_<?php echo $field['key'];?>(); });
                    }

                    dws_defer_until_jquery(dws_defer_<?php echo $field['key']; ?>);
                    <?php echo '</script>';
                } else {
                    add_action('admin_head', function() use ($field) {
                        echo '<script type="text/javascript">'; ?>
                        function dws_defer_<?php echo $field['key']; ?>() {
                        function disable_<?php echo $field['key']; ?>() {
                        jQuery('div[data-key="<?php echo $field['key']; ?>"] input.input').attr('disabled', 'disabled');
                        }

                        disable_<?php echo $field['key'];?>();
                        jQuery(document).on('change', function() { disable_<?php echo $field['key'];?>(); });
                        }

                        dws_defer_until_jquery(dws_defer_<?php echo $field['key']; ?>);
                        <?php echo '</script>';
                    }, 100);
                }
                break;
            case 'gallery':
                if (wp_doing_ajax()) {
                    echo '<style type="text/css">'; ?>
                    [data-key="<?php echo $field['key']; ?>"] .acf-gallery-main .acf-gallery-toolbar,
                    [data-key="<?php echo $field['key']; ?>"] .actions {
                    display: none !important;
                    }
                    <?php echo '</style>';
                } else {
                    add_action('admin_head', function() use ($field) {
                        echo '<style type="text/css">'; ?>
                        [data-key="<?php echo $field['key']; ?>"] .acf-gallery-main .acf-gallery-toolbar,
                        [data-key="<?php echo $field['key']; ?>"] .actions {
                        display: none !important;
                        }
                        <?php echo '</style>';
                    }, 100);
                }
                break;
            case 'text':
            case 'textarea':
                $field['readonly'] = 1;
                break;
            default:
                $field['disabled'] = $field['readonly'] = 1;
        }

        return $field;
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
            wp_enqueue_style( DWS_Settings_Pages::get_asset_handle('floating-button-style'), ACF_Pro_Compatibility::get_assets_base_path(true) . 'style.css', array(), ACF_Pro_Compatibility::get_plugin_version(), 'all' );
            wp_enqueue_script(DWS_Settings_Pages::get_asset_handle('floating-button'), ACF_Pro_Compatibility::get_assets_base_path(true) . 'floating-update-button.js', array( 'jquery' ), ACF_Pro_Compatibility::get_plugin_version(), true);
        }
    }

    //endregion

    //region HELPERS

    /**
     * @since   2.0.0
     * @version 2.0.3
     *
     * @param   string  $location_id
     * @param   string  $key
     * @param   string  $type
     * @param   array   $parameters
     *
     * @return  array   Formatted array for registering generic ACF field
     */
    private static function format_field($key, $type, $location_id, $parameters) {
        $key = strpos($key, self::FIELD_KEY_PREFIX) === 0 ? $key : (self::FIELD_KEY_PREFIX . $key); // must begin with 'field_'
        $parameters['key'] = $key;

        return wp_parse_args($parameters, array(
            'type'              => $type,
            'parent'            => $location_id
        ));
    }

    //endregion
}