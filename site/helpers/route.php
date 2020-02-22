<?php
/**
 * @package     Joomla.Site
 * @subpackage  com_bfdownloadmanager
 *
 * @copyright   Copyright (C) 2018 Jonathan Brain. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

defined('_JEXEC') or die;

/**
 * Bfdownloadmanager Component Route Helper.
 *
 * @since  1.5
 */
abstract class BfdownloadmanagerHelperRoute
{
	/**
	 * Get the download route.
	 *
	 * @param integer $id The route of the bfdownloadmanager item.
	 * @param integer $catid The category ID.
	 * @param integer $language The language code.
	 *
	 * @return  string  The download route.
	 *
	 * @since   1.5
	 */
	public static function getDownloadRoute($id, $catid = 0, $language = 0)
	{
		// Create the link
		$link = 'index.php?option=com_bfdownloadmanager&view=download&id=' . $id;

		if ((int)$catid > 1)
		{
			$link .= '&catid=' . $catid;
		}

		if ($language && $language !== '*' && JLanguageMultilang::isEnabled())
		{
			$link .= '&lang=' . $language;
		}

		return $link;
	}

	/**
	 * Get the category route.
	 *
	 * @param integer $catid The category ID.
	 * @param integer $language The language code.
	 *
	 * @return  string  The download route.
	 *
	 * @since   1.5
	 */
	public static function getCategoryRoute($catid, $language = 0)
	{
		if ($catid instanceof JCategoryNode)
		{
			$id = $catid->id;
		}
		else
		{
			$id = (int)$catid;
		}

		if ($id < 1)
		{
			$link = '';
		}
		else
		{
			$link = 'index.php?option=com_bfdownloadmanager&view=category&id=' . $id;

			if ($language && $language !== '*' && JLanguageMultilang::isEnabled())
			{
				$link .= '&lang=' . $language;
			}
		}

		return $link;
	}

	/**
	 * Get the form route.
	 *
	 * @param integer $id The form ID.
	 *
	 * @return  string  The download route.
	 *
	 * @since   1.5
	 */
	public static function getFormRoute($id)
	{
		return 'index.php?option=com_bfdownloadmanager&task=download.edit&a_id=' . (int)$id;
	}
}
