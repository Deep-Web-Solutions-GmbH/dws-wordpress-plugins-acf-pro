<?php

namespace Deep_Web_Solutions\Plugins\ACF\Field_Types;
use Deep_Web_Solutions\Plugins\ACF\ACF_Custom_Field_Types;

if (!defined('ABSPATH')) { exit; }

/**
 * A custom field type for adding back-end comments to a post.
 *
 * @since   1.0.0
 * @version 1.0.0
 * @author  Antonius Cezar Hegyes <a.hegyes@deep-web-solutions.de>
 *
 * @see     \acf_field
 */
final class ACF_Field_Post_Comments extends \acf_field {
	//region MAGIC FUNCTIONS

	/**
	 * ACF_Field_Post_Comments constructor.
	 *
	 * @since   1.0.0
	 * @version 1.0.0
	 *
	 * @see     \acf_field::__construct()
	 */
	public function __construct() {
		$this->name     = 'post_comments';
		$this->label    = __('Post comments', DWS_CUSTOM_EXTENSIONS_LANG_DOMAIN);
		$this->category = 'content';

		$this->defaults = array(
			'default_value' => '',
			'new_lines'     => '',
			'maxlength'     => '',
			'placeholder'   => '',
			'readonly'      => 0,
			'disabled'      => 0,
			'rows'          => ''
		);

		// for AJAX saving
		add_action('wp_ajax_save_internal_comment_data', array($this, 'save_ajax'));

		// do not delete!
		parent::__construct();
	}

	//endregion

	//region INHERITED FUNCTIONS

	/*
    *  render_field_settings()
    *
    *  Create extra settings for your field. These are visible when editing a field
    *
    *  @type	action
    *  @since	3.6
    *  @date	23/01/13
    *
    *  @param	$field (array) the $field being edited
    *  @return	n/a
    */
	function render_field_settings($field) {

		// default_value
		acf_render_field_setting(
			$field, array(
			'label'        => __('Default Value', 'acf'),
			'instructions' => __('Appears when creating a new post', 'acf'),
			'type'         => 'textarea',
			'name'         => 'default_value',
		)
		);


		// placeholder
		acf_render_field_setting(
			$field, array(
			'label'        => __('Placeholder Text', 'acf'),
			'instructions' => __('Appears within the input', 'acf'),
			'type'         => 'text',
			'name'         => 'placeholder',
		)
		);


		// maxlength
		acf_render_field_setting(
			$field, array(
			'label'        => __('Character Limit', 'acf'),
			'instructions' => __('Leave blank for no limit', 'acf'),
			'type'         => 'number',
			'name'         => 'maxlength',
		)
		);


		// rows
		acf_render_field_setting(
			$field, array(
			'label'        => __('Rows', 'acf'),
			'instructions' => __('Sets the textarea height', 'acf'),
			'type'         => 'number',
			'name'         => 'rows',
			'placeholder'  => 8
		)
		);


		// formatting
		acf_render_field_setting(
			$field, array(
			'label'        => __('New Lines', 'acf'),
			'instructions' => __('Controls how new lines are rendered', 'acf'),
			'type'         => 'select',
			'name'         => 'new_lines',
			'choices'      => array(
				'wpautop' => __("Automatically add paragraphs", 'acf'),
				'br'      => __("Automatically add &lt;br&gt;", 'acf'),
				''        => __("No Formatting", 'acf')
			)
		)
		);

	}

	/*
	*  render_field()
	*
	*  Create the HTML interface for your field
	*
	*  @param	$field (array) the $field being rendered
	*
	*  @type	action
	*  @since	3.6
	*  @date	23/01/13
	*
	*  @param	$field (array) the $field being edited
	*  @return	n/a
	*/
	function render_field($field) {
		// old comments
		echo '<div class="comments">';
		if (is_array($field['value'])) {
			echo $this->get_formatted_comments($field['value'], $field);
		}
		echo '</div>';

		// new input area

		// vars
		$o = array('id', 'class', 'name', 'placeholder', 'rows');
		$s = array('readonly', 'disabled');

		// maxlength
		if ($field['maxlength'] !== '') {
			$o[] = 'maxlength';
		}

		// rows
		if (empty($field['rows'])) {
			$field['rows'] = 8;
		}

		// populate atts
		$atts = array();
		foreach ($o as $k) {
			$atts[$k] = $field[$k];
		}

		// special atts
		foreach ($s as $k) {
			if ($field[$k]) {
				$atts[$k] = $k;
			}
		}

		echo '<textarea ' . acf_esc_attr($atts) . ' ></textarea>';
		echo '<a href="#" class="save_internal_comment button" style="float:right;">' . __('Add comment', DWS_CUSTOM_EXTENSIONS_LANG_DOMAIN) . '</a>';
		echo '<div class="clear"></div>';
	}

	/*
	*  input_admin_enqueue_scripts()
	*
	*  This action is called in the admin_enqueue_scripts action on the edit screen where your field is created.
	*  Use this action to add CSS + JavaScript to assist your render_field() action.
	*
	*  @type	action (admin_enqueue_scripts)
	*  @since	3.6
	*  @date	23/01/13
	*
	*  @param	n/a
	*  @return	n/a
	*/
	function input_admin_enqueue_scripts() {
		wp_enqueue_script('acf-input-order_comments', ACF_Custom_Field_Types::get_assets_base_path(true) . "js/post-comments.js", array('acf-input', 'jquery'));
		wp_enqueue_style('acf-input-order_comments', ACF_Custom_Field_Types::get_assets_base_path(true) . "css/post-comments.css", array('acf-input'));
	}

	function save_ajax() {
		if (!current_user_can('edit_posts')) {
			die;
		}

		$content   = filter_input(INPUT_POST, 'content', FILTER_SANITIZE_STRING);
		$post_id   = filter_input(INPUT_POST, 'post_id', FILTER_SANITIZE_NUMBER_INT);
		$field_key = filter_input(INPUT_POST, 'field_key', FILTER_SANITIZE_STRING);

		update_field($field_key, $content, $post_id);
		wp_send_json($this->get_formatted_comments(get_field($field_key, $post_id)));
	}

	/*
	*  update_value()
	*
	*  This filter is applied to the $value before it is saved in the db
	*
	*  @type	filter
	*  @since	3.6
	*  @date	23/01/13
	*
	*  @param	$value (mixed) the value found in the database
	*  @param	$post_id (mixed) the $post_id from which the value was loaded
	*  @param	$field (array) the field array holding all the field options
	*  @return	$value
	*/
	function update_value($value, $post_id, $field) {
		if ($value == null || (!is_array($value) && strlen($value) == 0)) {
			// if the field is empty, don't store any new comments, duh
			return get_field($field['key'], $post_id);
		}

		$new_value = array(
			'created_by' => get_current_user_id(),
			'timestamp'  => current_time('mysql'),
			'value'      => $value
		);

		$db_value = get_field($field['key'], $post_id);
		if ($db_value == null) {
			$db_value = array();
		}
		array_unshift($db_value, $new_value);

		return $db_value;
	}

	/*
	*  format_value()
	*
	*  This filter is appied to the $value after it is loaded from the db and before it is returned to the template
	*
	*  @type	filter
	*  @since	3.6
	*  @date	23/01/13
	*
	*  @param	$value (mixed) the value which was loaded from the database
	*  @param	$post_id (mixed) the $post_id from which the value was loaded
	*  @param	$field (array) the field array holding all the field options
	*
	*  @return	$value (mixed) the modified value
	*/
	function format_value($value, $post_id, $field) {
		// bail early if no value or not for template
		if (empty($value) || !is_string($value)) {
			return $value;
		}

		// new lines
		$new_lines = isset($field['new_lines']) ? $field['new_lines'] : 'br';
		if ($new_lines == 'wpautop') {
			$value = wpautop($value);
		} else if ($new_lines == 'br') {
			$value = nl2br($value);
		}

		// return
		return $value;
	}

	//endregion

	//region HELPERS

	/**
	 * @since   1.0.0
	 * @version 1.0.0
	 *
	 * @param   array $comments
	 * @param   array $field
	 *
	 * @return  string
	 */
	private function get_formatted_comments($comments, $field = array()) {
		$html = '';

		foreach ($comments as $comment) {
			if (empty($comment['value'])) {
				continue;
			}

			$created_by = get_user_by('id', $comment['created_by']);
			$html       .= '<p class="comment">
							<strong>' . $this->format_value($comment['value'], null, $field) . '</strong>'
				. ' ' . sprintf(__('- added on %s by', DWS_CUSTOM_EXTENSIONS_LANG_DOMAIN), $comment['timestamp']) . ' ' .
				'<a href="' . get_edit_user_link($created_by->ID) . '" target="_blank">' . $created_by->display_name . '</a>
						  </p>';
		}

		return $html;
	}

	//endregion
}

new ACF_Field_Post_Comments(); // VERY IMPORTANT!!!