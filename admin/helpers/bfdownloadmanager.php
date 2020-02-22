<?php
/**
 * @package     Joomla.Administrator
 * @subpackage  com_bfdownloadmanager
 *
 * @copyright   Copyright (C) 2018 Jonathan Brain. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

defined('_JEXEC') or die;

use Joomla\CMS\Component\ComponentHelper;
use Joomla\Registry\Registry;

/**
 * Bfdownloadmanager component helper.
 *
 * @since  1.6
 */
class BfdownloadmanagerHelper extends JHelperContent
{
	public static $extension = 'com_bfdownloadmanager';
	protected static $categories = null;
	protected static $category = null;

	/**
	 * Configure the Linkbar.
	 *
	 * @param string $vName The name of the active view.
	 *
	 * @return  void
	 *
	 * @since   1.6
	 */
	public static function addSubmenu($vName)
	{
		JHtmlSidebar::addEntry(
			JText::_('COM_BFDOWNLOADMANAGER_DOWNLOADS_TITLE'),
			'index.php?option=' . self::$extension . '&view=downloads',
			$vName == 'downloads'
		);
		JHtmlSidebar::addEntry(
			JText::_('COM_BFDOWNLOADMANAGER_SUBMENU_CATEGORIES'),
			'index.php?option=com_categories&extension=' . self::$extension,
			$vName == 'categories'
		);

		JHtmlSidebar::addEntry(
			JText::_('COM_BFDOWNLOADMANAGER_SUBMENU_FEATURED'),
			'index.php?option=' . self::$extension . '&view=featured',
			$vName == 'featured'
		);

		if (JComponentHelper::isEnabled('com_fields') && self::getParam('custom_fields_enable', '1'))
		{
			$user = JFactory::getUser('core.fields');
			if ($user->authorise('core.manage', self::$extension))
			{
				JHtmlSidebar::addEntry(
					JText::_('JGLOBAL_FIELDS'),
					'index.php?option=com_fields&context=' . self::$extension . '.download',
					$vName == 'fields.fields'
				);
			}
			if ($user->authorise('core.field.groups', self::$extension))
			{
				JHtmlSidebar::addEntry(
					JText::_('JGLOBAL_FIELD_GROUPS'),
					'index.php?option=com_fields&view=groups&context=' . self::$extension . '.download',
					$vName == 'fields.groups'
				);
			}
		}
	}

	/**
	 * Applies the content tag filters to arbitrary text as per settings for current user group
	 *
	 * @param text $text The string to filter
	 *
	 * @return  string  The filtered string
	 *
	 * @deprecated  4.0  Use JComponentHelper::filterText() instead.
	 */
	public static function filterText($text)
	{
		try
		{
			JLog::add(
				sprintf('%s() is deprecated. Use JComponentHelper::filterText() instead', __METHOD__),
				JLog::WARNING,
				'deprecated'
			);
		} catch (RuntimeException $exception)
		{
			// Informational log only
		}

		return JComponentHelper::filterText($text);
	}

	/**
	 * Adds Count Items for Category Manager.
	 *
	 * @param stdClass[]  &$items The banner category objects
	 *
	 * @return  stdClass[]
	 *
	 * @since   3.5
	 */
	public static function countItems(&$items)
	{
		$db = JFactory::getDbo();

		foreach ($items as $item)
		{
			$item->count_trashed = 0;
			$item->count_archived = 0;
			$item->count_unpublished = 0;
			$item->count_published = 0;
			$query = $db->getQuery(true);
			$query->select('state, count(*) AS count')
				->from($db->qn('#__bfdownloadmanager'))
				->where('catid = ' . (int)$item->id)
				->group('state');
			$db->setQuery($query);
			$downloads = $db->loadObjectList();

			foreach ($downloads as $download)
			{
				if ($download->state == 1)
				{
					$item->count_published = $download->count;
				}

				if ($download->state == 0)
				{
					$item->count_unpublished = $download->count;
				}

				if ($download->state == 2)
				{
					$item->count_archived = $download->count;
				}

				if ($download->state == -2)
				{
					$item->count_trashed = $download->count;
				}
			}
		}

		return $items;
	}

	/**
	 * Adds Count Items for Tag Manager.
	 *
	 * @param stdClass[]  &$items The content objects
	 * @param string $extension The name of the active view.
	 *
	 * @return  stdClass[]
	 *
	 * @since   3.6
	 */
	public static function countTagItems(&$items, $extension)
	{
		$db = JFactory::getDbo();
		$parts = explode('.', $extension);
		$section = null;

		if (count($parts) > 1)
		{
			$section = $parts[1];
		}

		$join = $db->qn('#__bfdownloadmanager') . ' AS c ON ct.bfdownloadmanager_item_id=c.id';
		$state = 'state';

		if ($section === 'category')
		{
			$join = $db->qn('#__categories') . ' AS c ON ct.bfdownloadmanager_item_id=c.id';
			$state = 'published as state';
		}

		foreach ($items as $item)
		{
			$item->count_trashed = 0;
			$item->count_archived = 0;
			$item->count_unpublished = 0;
			$item->count_published = 0;
			$query = $db->getQuery(true);
			$query->select($state . ', count(*) AS count')
				->from($db->qn('#__bfdownloadmanageritem_tag_map') . 'AS ct ')
				->where('ct.tag_id = ' . (int)$item->id)
				->where('ct.type_alias =' . $db->q($extension))
				->join('LEFT', $join)
				->group('state');
			$db->setQuery($query);
			$contents = $db->loadObjectList();

			foreach ($contents as $content)
			{
				if ($content->state == 1)
				{
					$item->count_published = $content->count;
				}

				if ($content->state == 0)
				{
					$item->count_unpublished = $content->count;
				}

				if ($content->state == 2)
				{
					$item->count_archived = $content->count;
				}

				if ($content->state == -2)
				{
					$item->count_trashed = $content->count;
				}
			}
		}

		return $items;
	}

	/**
	 * Returns a valid section for downloads. If it is not valid then null
	 * is returned.
	 *
	 * @param string $section The section to get the mapping for
	 *
	 * @return  string|null  The new section
	 *
	 * @since   3.7.0
	 */
	public static function validateSection($section)
	{
		if (JFactory::getApplication()->isClient('site'))
		{
			// On the front end we need to map some sections
			switch ($section)
			{
				// Editing an download
				case 'form':

					// Category list view
				case 'featured':
				case 'category':
					$section = 'download';
			}
		}

		if ($section != 'download')
		{
			// We don't know other sections
			return null;
		}

		return $section;
	}

	/**
	 * Returns valid contexts
	 *
	 * @return  array
	 *
	 * @since   3.7.0
	 */
	public static function getContexts()
	{
		JFactory::getLanguage()->load(self::$extension, JPATH_ADMINISTRATOR);

		$contexts = array(
			self::$extension . '.download' => JText::_('COM_BFDOWNLOADMANAGER'),
			self::$extension . '.categories' => JText::_('JCATEGORY')
		);

		return $contexts;
	}

	/**
	 * Method to get the parameter value.
	 *
	 * @since   3.4
	 */
	public static function getParam($name, $default = null)
	{
		$params = ComponentHelper::getParams(self::$extension);
		return trim($params->get($name, $default));
	}

	/**
	 */
	public static function validateFilenameSuffix($filename, $suffix_list, $form = null)
	{
		if (!empty($filename))
		{
			if (!self::filenameInSuffixArray($filename, $suffix_list))
			{
				if (!empty($form))
				{
					JFactory::getApplication()->enqueueMessage(jText::sprintf('COM_BFDOWNLOADMANAGER_SUFFIX_UNSUPPORTED', jText::_($form->getField('downloadfile')->getAttribute('label')), $filename), 'error');
				}
				return false;
			}
		}
		return true;
	}

	/**
	 */
	public static function suffixList2Array($suffix_list)
	{
		$suffix_list = preg_replace('/^[^a-z0-9]+/', '', strtolower($suffix_list));
		$suffix_list = preg_replace('/[^a-z0-9]+$/', '', $suffix_list);
		if (!empty($suffix_list))
		{
			$suffix_list = preg_split('/[^a-z0-9]+/', $suffix_list);
		}
		return $suffix_list;
	}

	/**
	 */
	public static function filenameInSuffixArray($filename, $suffix_list)
	{
		if (!is_array($suffix_list))
		{
			$suffix_list = self::suffixList2Array($suffix_list);
		}
		if (empty($suffix_list))
		{
			return true;
		}
		return in_array(strtolower(pathinfo($filename, PATHINFO_EXTENSION)), $suffix_list);
	}

	/**
	 */
	public static function getCategoryAttr($catid, $name)
	{
		if (empty($catid))
		{
			return '';
		}

		if (empty(self::$categories))
		{
			self::$categories = JCategories::getInstance('Bfdownloadmanager');
			self::$category = array();
		}
		if (empty(self::$category[$catid]))
		{
			$category = self::$categories->get($catid);
			$category->attr = new Registry(json_decode($category->params));
			self::$category[$catid] = $category;
		}

		$value = trim(self::$category[$catid]->attr->get($name));
		if ($value === null || $value === '')
		{
			return self::getParam($name);
		}
		return trim(self::$category[$catid]->attr->get($name));
	}
}
