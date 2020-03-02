<?php

namespace Deep_Web_Solutions\Plugins\ACF;
use Deep_Web_Solutions\Base\DWS_Functionality_Template;
use Deep_Web_Solutions\Core\DWS_Loader;

if (!defined('ABSPATH')) { exit; }

/**
 * Handles customizations to the ACF fields and their functionalities.
 *
 * @since   2.0.0
 * @version 2.0.9
 * @author  Antonius Cezar Hegyes <a.hegyes@deep-web-solutions.de>
 *
 * @see     DWS_Functionality_Template
 */
final class ACF_Customization extends DWS_Functionality_Template {
	//region INHERITED FUNCTIONS

	/**
	 * @since   1.0.0
	 * @version 2.0.7
	 *
	 * @see     DWS_Functionality_Template::define_functionality_hooks()
	 *
	 * @param   DWS_Loader  $loader
	 */
	protected function define_functionality_hooks($loader) {
		$loader->add_filter('acf/update_value/type=date_time_picker', $this, 'acf_save_datetimepicker_as_unix_timestamp', 10, 3);

		$loader->add_action('acf/render_field/type=select', $this, 'acf_add_dummy_hidden_fields_to_disabled_fields', PHP_INT_MAX);
        $loader->add_action('acf/render_field/type=number', $this, 'acf_add_dummy_hidden_fields_to_disabled_fields', PHP_INT_MAX);
        $loader->add_action('acf/render_field/type=text', $this, 'acf_add_dummy_hidden_fields_to_disabled_fields', PHP_INT_MAX);
		$loader->add_action('acf/render_field/type=textarea', $this, 'acf_add_dummy_hidden_fields_to_disabled_fields', PHP_INT_MAX);
	}

	//endregion

	//region COMPATIBILITY LOGIC

	/**
	 * For best compatibility, it's best if we save the DateTimePicker values as UNIX timestamps.
	 *
     * @since   1.0.0
     * @version 2.0.9
     *
	 * @param   string      $value      The current value of the DateTimePicker.
	 * @param   int         $post_id    The ID of the post on which the DateTimePicker is registered.
	 * @param   array       $field      The DateTimePicker field in ACF format.
	 *
	 * @return  string|int  Either the original value or the UNIX timestamp thereof if conversion succeeded.
	 */
	public function acf_save_datetimepicker_as_unix_timestamp($value, $post_id, $field) {
		if (empty($value) || is_numeric($value)) {
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
     * @since   2.0.0
     * @version 2.0.7
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

	//endregion

}