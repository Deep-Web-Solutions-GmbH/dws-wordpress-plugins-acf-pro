<?php

namespace Deep_Web_Solutions\Plugins\ACF;
use Deep_Web_Solutions\Base\DWS_Functionality_Template;
use Deep_Web_Solutions\Core\DWS_Loader;

if (!defined('ABSPATH')) { exit; }

/**
 * Handles the registering of options in the back-end of the website.
 *
 * @since   2.0.0
 * @version 2.0.0
 * @author  Antonius Cezar Hegyes <a.hegyes@deep-web-solutions.de>
 *
 * @see     DWS_Functionality_Template
 */
final class ACF_Custom_Field_Types extends DWS_Functionality_Template {
	//region INHERITED FUNCTIONS

	/**
	 * @since   2.0.0
	 * @version 2.0.0
	 *
	 * @see     DWS_Functionality_Template::define_functionality_hooks()
	 *
	 * @param   DWS_Loader  $loader
	 */
	protected function define_functionality_hooks($loader) {
        $loader->add_action('acf/include_field_types', $this, 'include_field_types');
	}

	//endregion

	//region COMPATIBILITY LOGIC

	/**
	 * Loads the custom field types.
	 *
	 * @since   2.0.0
	 * @version 2.0.0
	 *
	 * @param   int     $version    The major version of the ACF plugin.
	 */
	function include_field_types($version) {
		// we only support v5 of ACF
		if ($version != 5 && !empty($version)) {
			return;
		}

		/** A custom field type for adding back-end comments to a post. */
		require_once('fields/acf-post_comments-v5.php');
	}

	//endregion
}