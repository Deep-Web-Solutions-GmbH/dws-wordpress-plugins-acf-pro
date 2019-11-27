<?php

namespace Deep_Web_Solutions\Plugins\ACF;
use Deep_Web_Solutions\Core\DWS_Permissions;
use Deep_Web_Solutions\Core\Permissions_Base;

if (!defined('ABSPATH')) { exit; }

/**
 * The custom DWS permissions needed to enhance the custom field's plugin library.
 *
 * @since   1.0.0
 * @version 1.2.0
 * @author  Antonius Cezar Hegyes <a.hegyes@deep-web-solutions.de>
 *
 * @see     Permissions_Base
 * @see     DWS_Permissions
 */
final class Permissions extends Permissions_Base {

	/**
	 * @since   1.0.0
	 * @version 1.0.0
	 *
	 * @var     string  CAN_EDIT_GALLERY_FILED  Determines whether the current user has access to actions on images
	 *                                          or not.
	 */
	const CAN_EDIT_GALLERY_FIELD = DWS_Permissions::CAPABILITY_PREFIX . 'edit_acf_gallery_field';
} Permissions::maybe_initialize_singleton('agdsgrhgehiue');