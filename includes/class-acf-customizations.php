<?php

namespace Deep_Web_Solutions\Plugins\ACF;
use Deep_Web_Solutions\Core\DWS_Functionality_Template;
use Deep_Web_Solutions\Core\DWS_Permissions;

if (!defined('ABSPATH')) { exit; }

/**
 * Handles customizations to the ACF fields and their functionalities.
 *
 * @since   1.0.0
 * @version 1.5.0
 * @author  Antonius Cezar Hegyes <a.hegyes@deep-web-solutions.de>
 *
 * @see     DWS_Functionality_Template
 */
final class ACF_Customization extends DWS_Functionality_Template {
	//region INHERITED FUNCTIONS

	/**
	 * @since   1.0.0
	 * @version 1.0.0
	 *
	 * @see     DWS_Functionality_Template::define_functionality_hooks()
	 *
	 * @param   \Deep_Web_Solutions\Core\DWS_WordPress_Loader 	$loader
	 */
	protected function define_functionality_hooks($loader) {
		$loader->add_filter('acf/update_value/type=date_time_picker', $this, 'acf_save_datetimepicker_as_unix_timestamp', 10, 3);

		$loader->add_action('acf/render_field/type=select', $this, 'acf_add_dummy_hidden_fields_to_disabled_fields', PHP_INT_MAX);
		$loader->add_action('acf/render_field/type=text', $this, 'acf_add_dummy_hidden_fields_to_disabled_fields', PHP_INT_MAX);
		$loader->add_action('acf/render_field/type=textarea', $this, 'acf_add_dummy_hidden_fields_to_disabled_fields', PHP_INT_MAX);

		$loader->add_filter('attachment_fields_to_edit', $this, 'maybe_remove_attachment_edit_fields', PHP_INT_MAX, 2);
	}

	//endregion

	//region COMPATIBILITY LOGIC

	/**
	 * For best compatibility, it's best if we save the DateTimePicker values as UNIX timestamps.
	 *
     * @since   1.0.0
     * @version 1.2.1
     *
	 * @param   string      $value      The current value of the DateTimePicker.
	 * @param   int         $post_id    The ID of the post on which the DateTimePicker is registered.
	 * @param   array       $field      The DateTimePicker field in ACF format.
	 *
	 * @return  string|int  Either the original value or the UNIX timestamp thereof if conversion succeeded.
	 */
	public function acf_save_datetimepicker_as_unix_timestamp($value, $post_id, $field) {
		if (empty($value)) {
			return $value;
		}

		$timestamp = strtotime($value);
		if (empty($timestamp)) { // when strtotime failed and when it's 0
			$datetime = \DateTime::createFromFormat($field['return_format'], $value);
			if ($datetime === false) {
				error_log("Could not convert string $value to time for post $post_id. Current user ID: " . get_current_user_id() . " Field: " . json_encode($field));
				return $value;
			}
			$timestamp = $datetime->getTimestamp();
		}

		return $timestamp;
	}

	/**
	 * If the field has been marked as disabled by the helpers in this class,
	 * then output a hidden field such that the value does not get lost
	 * when the page is updated.
	 *
     * @since   1.0.0
     * @version 1.0.0
     *
	 * @param   array   $field  ACF field in ACF format.
	 */
	public function acf_add_dummy_hidden_fields_to_disabled_fields($field) {
		if (!isset($field['disabled']) || !$field['disabled'] || get_current_screen()->id === 'acf-field-group') {
			return;
		}

		if (is_array($field['value'])) {
			$hidden_field = '';
			foreach ($field['value'] as $index => $value) {
				$hidden_field .= "<input id='{$field['id']}-input' name='{$field['name']}[{$index}]' value='{$value}' type='hidden' readonly='readonly'/>";
			}
		} else {
			$hidden_field = "<input id='{$field['id']}-input' name='{$field['name']}' value='{$field['value']}' type='hidden' readonly='readonly'/>";
		}
		echo $hidden_field;

		// compatibility with ACF and SELECT2 ... ugh!
		echo '<script type="text/javascript">'; ?>
			var replaced_<?php echo md5($field['key']); ?> = false;
			acf.add_action('select2_init', function($select, select2_args, args, $field) {
				if ($select.attr('id') !== '<?php echo $field['id']; ?>') { return; }
				if (replaced_<?php echo md5($field['key']); ?> === false) {
					replaced_<?php echo md5($field['key']); ?> = true;
					jQuery('input#<?php echo $field['id']; ?>-input').replaceWith("<?php echo $hidden_field; ?>");
				}
			});
		<?php echo '</script>';
	}

	/**
	 * Not everyone should be able to perform edits on the gallery fields.
	 *
     * @since   1.0.0
     * @version 1.0.0
     *
	 * @param   array       $form_fields    The fields that can be used to edit the current image.
	 * @param   \WP_Post    $post           The post of the media file.
	 *
	 * @return  array       The fields for the actions that the current user is entitled to carry out.
	 */
	public function maybe_remove_attachment_edit_fields($form_fields, $post) {
		if (strpos(wp_get_raw_referer(), 'upload.php') !== false) {
		    error_log("what");
			return $form_fields;
		}
		$log = (DWS_Permissions::has(Permissions::CAN_EDIT_GALLERY_FIELD)) ? 'true' : 'false';

		error_log($log);

		return DWS_Permissions::has(Permissions::CAN_EDIT_GALLERY_FIELD) ? $form_fields : array();
	}

	//endregion

	//region HELPERS

	/**
	 * Outputs some CSS to completely hide a certain field from the screen, only for humans.
	 *
     * @since   1.0.0
     * @version 1.5.0
     *
     * @see     ACF_Fields::make_field_uneditable()
     *
	 * @param   array   $field          ACF field in ACF format.
	 * @param   bool    $do_on_ajax     True if the action should also be performed on AJAX requests, otherwise false.
	 *
	 * @return  array   The ACF field given as input but with modified values such that it becomes uneditable.
	 */
	public static function css_hide_field($field, $do_on_ajax = false) {
		/**
		 * @since   1.0.0
         * @version 1.0.0
         *
         * @param   bool    $should_skip_css_hiding_field   Whether the current ACF field should not be CSS hidden.
         * @param   array   $field                          The current ACF field.
		 */
		if (apply_filters(self::get_hook_name('skip-css-hiding-field'), false, $field)) {
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
     * @since   1.0.0
     * @version 1.5.0
     *
	 * @param   array   $field          ACF field in ACF format.
	 * @param   bool    $do_on_ajax     True if the action should also be performed on AJAX requests, otherwise false.
     *
	 * @return  array   The ACF field given as input but with modified values such that it becomes uneditable.
	 */
	public static function make_field_uneditable($field, $do_on_ajax = false) {
		/**
		 * @since   1.0.0
         * @version 1.0.0
         *
         * @param   bool    $should_skip_making_field_uneditable    Whether the current ACF field should be kept editable or not.
         * @param   array   $field                                  The current ACF field.
		 */
		if (apply_filters(self::get_hook_name('skip-making-field-uneditable'), false, $field)) {
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

	/**
	 * Make certain fields uneditable if the user does not have the required permissions.
	 *
	 * @param   array   $field          ACF field in ACF format.
	 * @param   string  $permission     The required WP capability to leave the field editable.
	 *
	 * @return  array   The ACF field given as input but with modified values such that it becomes uneditable if the
	 *                  current user does not have appropriate editing permissions.
	 */
	public static function maybe_make_field_uneditable($field, $permission) {
		return !DWS_Permissions::has(array($permission, 'administrator'), null, 'or')
			? self::make_field_uneditable($field) : $field;
	}

	//endregion

}