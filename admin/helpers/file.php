<?php
/**
 * @package     Joomla.Administrator
 * @subpackage  com_bfdownloadmanager
 *
 * @copyright   Copyright (C) 2020 Jonathan Brain. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

defined('_JEXEC') or die;

/**
 * Bfdownloadmanager component helper.
 *
 * @since  1.6
 */
class BfdownloadmanagerHelperFile
{
	/**
	 */
	public static function getFilename($id, $downloadfile_name)
	{
		return JPATH_ROOT . '/media/com_bfdownloadmanager/downloads/' . $id . '.' . $downloadfile_name;
	}
}
