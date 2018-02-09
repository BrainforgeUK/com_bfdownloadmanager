<?php
/**
 * @package     Joomla.Site
 * @subpackage  com_bfdownloadmanager
 *
 * @copyright   Copyright (C) 2018 Jonathan Brain. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

defined('_JEXEC') or die;

JLoader::register('BfdownloadmanagerHelper', JPATH_ADMINISTRATOR . '/components/com_bfdownloadmanager/helpers/bfdownloadmanager.php');
JLoader::register('BfdownloadmanagerHelperRoute', JPATH_SITE . '/components/com_bfdownloadmanager/helpers/route.php');
JLoader::register('CategoryHelperAssociation', JPATH_ADMINISTRATOR . '/components/com_categories/helpers/association.php');

/**
 * Bfdownloadmanager Component Association Helper
 *
 * @since  3.0
 */
abstract class BfdownloadmanagerHelperAssociation extends CategoryHelperAssociation
{
	/**
	 * Method to get the associations for a given item
	 *
	 * @param   integer  $id    Id of the item
	 * @param   string   $view  Name of the view
	 *
	 * @return  array   Array of associations for the item
	 *
	 * @since  3.0
	 */
	public static function getAssociations($id = 0, $view = null)
	{
		$jinput = JFactory::getApplication()->input;
		$view   = $view === null ? $jinput->get('view') : $view;
		$id     = empty($id) ? $jinput->getInt('id') : $id;
		$user   = JFactory::getUser();
		$groups = implode(',', $user->getAuthorisedViewLevels());

		if ($view === 'download')
		{
			if ($id)
			{
				$associations = JLanguageAssociations::getAssociations('com_bfdownloadmanager', '#__bfdownloadmanager', 'com_bfdownloadmanager.item', $id);

				$return = array();

				foreach ($associations as $tag => $item)
				{
					if ($item->language != JFactory::getLanguage()->getTag())
					{
						$arrId   = explode(':', $item->id);
						$assocId = $arrId[0];

						$db    = JFactory::getDbo();
						$query = $db->getQuery(true)
							->select($db->qn('state'))
							->from($db->qn('#__bfdownloadmanager'))
							->where($db->qn('id') . ' = ' . (int) ($assocId))
							->where('access IN (' . $groups . ')');
						$db->setQuery($query);

						$result = (int) $db->loadResult();

						if ($result > 0)
						{
							$return[$tag] = BfdownloadmanagerHelperRoute::getDownloadRoute($item->id, (int) $item->catid, $item->language);
						}
					}
				}

				return $return;
			}
		}

		if ($view === 'category' || $view === 'categories')
		{
			return self::getCategoryAssociations($id, 'com_bfdownloadmanager');
		}

		return array();
	}

	/**
	 * Method to display in frontend the associations for a given download
	 *
	 * @param   integer  $id  Id of the download
	 *
	 * @return  array   An array containing the association URL and the related language object
	 *
	 * @since  3.7.0
	 */
	public static function displayAssociations($id)
	{
		$return = array();

		if ($associations = self::getAssociations($id, 'download'))
		{
			$levels    = JFactory::getUser()->getAuthorisedViewLevels();
			$languages = JLanguageHelper::getLanguages();

			foreach ($languages as $language)
			{
				// Do not display language when no association
				if (empty($associations[$language->lang_code]))
				{
					continue;
				}

				// Do not display language without frontend UI
				if (!array_key_exists($language->lang_code, JLanguageHelper::getInstalledLanguages(0)))
				{
					continue;
				}

				// Do not display language without specific home menu
				if (!array_key_exists($language->lang_code, JLanguageMultilang::getSiteHomePages()))
				{
					continue;
				}

				// Do not display language without authorized access level
				if (isset($language->access) && $language->access && !in_array($language->access, $levels))
				{
					continue;
				}

				$return[$language->lang_code] = array('item' => $associations[$language->lang_code], 'language' => $language);
			}
		}

		return $return;
	}
}
