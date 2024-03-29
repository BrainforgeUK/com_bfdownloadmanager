<?php
/**
 * @package     Joomla.Administrator
 * @subpackage  com_bfdownloadmanager
 *
 * @copyright   Copyright (C) 2018-2021 Jonathan Brain. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

defined('_JEXEC') or die;

use Joomla\Registry\Registry;
use Joomla\Utilities\ArrayHelper;

JLoader::register('BfdownloadmanagerHelper', JPATH_ADMINISTRATOR . '/components/com_bfdownloadmanager/helpers/bfdownloadmanager.php');

/**
 * Item Model for an Download.
 *
 * @since  1.6
 */
class BfdownloadmanagerModelDownload extends JModelAdmin
{
	/**
	 * The type alias for this content type (for example, 'com_bfdownloadmanager.download').
	 *
	 * @var    string
	 * @since  3.2
	 */
	public $typeAlias = 'com_bfdownloadmanager.download';

	/**
	 * The context used for the associations table
	 *
	 * @var    string
	 * @since  3.4.4
	 */
	protected $associationsContext = 'com_bfdownloadmanager.item';

	/**
	 * Batch copy items to a new category or current.
	 *
	 * @param integer $value The new category.
	 * @param array $pks An array of row IDs.
	 * @param array $contexts An array of item contexts.
	 *
	 * @return  mixed  An array of new IDs on success, boolean false on failure.
	 *
	 * @since   11.1
	 */
	protected function batchCopy($value, $pks, $contexts)
	{
		$categoryId = (int)$value;

		$newIds = array();

		if (!$this->checkCategoryId($categoryId))
		{
			return false;
		}

		// Parent exists so we let's proceed
		while (!empty($pks))
		{
			// Pop the first ID off the stack
			$pk = array_shift($pks);

			$this->table->reset();

			// Check that the row actually exists
			if (!$this->table->load($pk))
			{
				if ($error = $this->table->getError())
				{
					// Fatal error
					$this->setError($error);

					return false;
				}
				else
				{
					// Not fatal error
					$this->setError(JText::sprintf('JLIB_APPLICATION_ERROR_BATCH_MOVE_ROW_NOT_FOUND', $pk));
					continue;
				}
			}

			// Alter the title & alias
			$data = $this->generateNewTitle($categoryId, $this->table->alias, $this->table->title);
			$this->table->title = $data['0'];
			$this->table->alias = $data['1'];

			// Reset the ID because we are making a copy
			$this->table->id = 0;

			// Reset hits because we are making a copy
			$this->table->hits = 0;

			// Unpublish because we are making a copy
			$this->table->state = 0;

			// New category ID
			$this->table->catid = $categoryId;

			// TODO: Deal with ordering?
			// $table->ordering	= 1;

			// Get the featured state
			$featured = $this->table->featured;

			// Check the row.
			if (!$this->table->check())
			{
				$this->setError($this->table->getError());

				return false;
			}

			$this->createTagsHelper($this->tagsObserver, $this->type, $pk, $this->typeAlias, $this->table);

			// Store the row.
			if (!$this->table->store())
			{
				$this->setError($this->table->getError());

				return false;
			}

			// Get the new item ID
			$newId = $this->table->get('id');

			// Add the new ID to the array
			$newIds[$pk] = $newId;

			// Check if the download was featured and update the #__bfdownloadmanager_frontpage table
			if ($featured == 1)
			{
				$db = $this->getDbo();
				$query = $db->getQuery(true)
					->insert($db->quoteName('#__bfdownloadmanager_frontpage'))
					->values($newId . ', 0');
				$db->setQuery($query);
				$db->execute();
			}
		}

		// Clean the cache
		$this->cleanCache();

		return $newIds;
	}

	/**
	 * Method to test whether a record can be deleted.
	 *
	 * @param object $record A record object.
	 *
	 * @return  boolean  True if allowed to delete the record. Defaults to the permission set in the component.
	 *
	 * @since   1.6
	 */
	protected function canDelete($record)
	{
		if (!empty($record->id))
		{
			if ($record->state != -2)
			{
				return false;
			}

			return JFactory::getUser()->authorise('core.delete', 'com_bfdownloadmanager.download.' . (int)$record->id);
		}

		return false;
	}

	/**
	 * Method to test whether a record can have its state edited.
	 *
	 * @param object $record A record object.
	 *
	 * @return  boolean  True if allowed to change the state of the record. Defaults to the permission set in the component.
	 *
	 * @since   1.6
	 */
	protected function canEditState($record)
	{
		$user = JFactory::getUser();

		// Check for existing download.
		if (!empty($record->id))
		{
			return $user->authorise('core.edit.state', 'com_bfdownloadmanager.download.' . (int)$record->id);
		}

		// New download, so check against the category.
		if (!empty($record->catid))
		{
			return $user->authorise('core.edit.state', 'com_bfdownloadmanager.category.' . (int)$record->catid);
		}

		// Default to component settings if neither download nor category known.
		return parent::canEditState($record);
	}

	/**
	 * Prepare and sanitise the table data prior to saving.
	 *
	 * @param JTable $table A JTable object.
	 *
	 * @return  void
	 *
	 * @since   1.6
	 */
	protected function prepareTable($table)
	{
		// Set the publish date to now
		if ($table->state == 1 && (int)$table->publish_up == 0)
		{
			$table->publish_up = JFactory::getDate()->toSql();
		}

		if ($table->state == 1 && intval($table->publish_down) == 0)
		{
			$table->publish_down = $this->getDbo()->getNullDate();
		}

		// Increment the content version number.
		$table->version++;

		// Reorder the downloads within the category so the new download is first
		if (empty($table->id))
		{
			$table->reorder('catid = ' . (int)$table->catid . ' AND state >= 0');
		}
	}

	/**
	 * Returns a Table object, always creating it.
	 *
	 * @param string $type The table type to instantiate
	 * @param string $prefix A prefix for the table class name. Optional.
	 * @param array $config Configuration array for model. Optional.
	 *
	 * @return  JTable    A database object
	 */
	public function getTable($type = 'bfdownloadmanager', $prefix = 'JTable', $config = array())
	{
		return JTable::getInstance($type, $prefix, $config);
	}

	/**
	 * Method to get a single record.
	 *
	 * @param integer $pk The id of the primary key.
	 *
	 * @return  mixed  Object on success, false on failure.
	 */
	public function getItem($pk = null)
	{
		if ($item = parent::getItem($pk))
		{
			// Convert the params field to an array.
			$registry = new Registry($item->attribs);
			$item->attribs = $registry->toArray();

			// Convert the metadata field to an array.
			$registry = new Registry($item->metadata);
			$item->metadata = $registry->toArray();

			// Convert the images field to an array.
			$registry = new Registry($item->images);
			$item->images = $registry->toArray();

			// Convert the urls field to an array.
			$registry = new Registry($item->urls);
			$item->urls = $registry->toArray();

			$item->downloadtext = trim($item->fulltext) != '' ? $item->introtext . "<hr id=\"system-readmore\" />" . $item->fulltext : $item->introtext;

			if (!empty($item->id))
			{
				$item->tags = new JHelperTags;
				$item->tags->getTagIds($item->id, 'com_bfdownloadmanager.download');
			}
		}

		// Load associated content items
		$assoc = JLanguageAssociations::isEnabled();

		if ($assoc)
		{
			$item->associations = array();

			if ($item->id != null)
			{
				$associations = JLanguageAssociations::getAssociations('com_bfdownloadmanager', '#__bfdownloadmanager', 'com_bfdownloadmanager.item', $item->id);

				foreach ($associations as $tag => $association)
				{
					$item->associations[$tag] = $association->id;
				}
			}
		}

		return $item;
	}

	/**
	 * Method to get the record form.
	 *
	 * @param array $data Data for the form.
	 * @param boolean $loadData True if the form is to load its own data (default case), false if not.
	 *
	 * @return  JForm|boolean  A JForm object on success, false on failure
	 *
	 * @since   1.6
	 */
	public function getForm($data = array(), $loadData = true)
	{
		// Get the form.
		$form = $this->loadForm('com_bfdownloadmanager.download', 'download', array('control' => 'jform', 'load_data' => $loadData));

		if (empty($form))
		{
			return false;
		}

		$jinput = JFactory::getApplication()->input;

		/*
		 * The front end calls this model and uses a_id to avoid id clashes so we need to check for that first.
		 * The back end uses id so we use that the rest of the time and set it to 0 by default.
		 */
		$id = $jinput->get('a_id', $jinput->get('id', 0));

		// Determine correct permissions to check.
		if ($this->getState('download.id'))
		{
			$id = $this->getState('download.id');

			// Existing record. Can only edit in selected categories.
			$form->setFieldAttribute('catid', 'action', 'core.edit');

			// Existing record. Can only edit own downloads in selected categories.
			$form->setFieldAttribute('catid', 'action', 'core.edit.own');
		}
		else
		{
			// New record. Can only create in selected categories.
			$form->setFieldAttribute('catid', 'action', 'core.create');
		}

		$user = JFactory::getUser();

		// Check for existing download.
		// Modify the form based on Edit State access controls.
		if ($id != 0 && (!$user->authorise('core.edit.state', 'com_bfdownloadmanager.download.' . (int)$id))
			|| ($id == 0 && !$user->authorise('core.edit.state', 'com_bfdownloadmanager')))
		{
			// Disable fields for display.
			$form->setFieldAttribute('featured', 'disabled', 'true');
			$form->setFieldAttribute('ordering', 'disabled', 'true');
			$form->setFieldAttribute('publish_up', 'disabled', 'true');
			$form->setFieldAttribute('publish_down', 'disabled', 'true');
			$form->setFieldAttribute('state', 'disabled', 'true');

			// Disable fields while saving.
			// The controller has already verified this is an download you can edit.
			$form->setFieldAttribute('featured', 'filter', 'unset');
			$form->setFieldAttribute('ordering', 'filter', 'unset');
			$form->setFieldAttribute('publish_up', 'filter', 'unset');
			$form->setFieldAttribute('publish_down', 'filter', 'unset');
			$form->setFieldAttribute('state', 'filter', 'unset');
		}

		// Prevent messing with download language and category when editing existing download with associations
		$app = JFactory::getApplication();
		$assoc = JLanguageAssociations::isEnabled();

		// Check if download is associated
		if ($this->getState('download.id') && $app->isClient('site') && $assoc)
		{
			$associations = JLanguageAssociations::getAssociations('com_bfdownloadmanager', '#__bfdownloadmanager', 'com_bfdownloadmanager.item', $id);

			// Make fields read only
			if (!empty($associations))
			{
				$form->setFieldAttribute('language', 'readonly', 'true');
				$form->setFieldAttribute('catid', 'readonly', 'true');
				$form->setFieldAttribute('language', 'filter', 'unset');
				$form->setFieldAttribute('catid', 'filter', 'unset');
			}
		}

		return $form;
	}

	/**
	 * Method to get the data that should be injected in the form.
	 *
	 * @return  mixed  The data for the form.
	 *
	 * @since   1.6
	 */
	protected function loadFormData()
	{
		// Check the session for previously entered form data.
		$app = JFactory::getApplication();
		$data = $app->getUserState('com_bfdownloadmanager.edit.download.data', array());

		if (empty($data))
		{
			$data = $this->getItem();

			// Pre-select some filters (Status, Category, Language, Access) in edit form if those have been selected in Download Manager: Downloads
			if ($this->getState('download.id') == 0)
			{
				$filters = (array)$app->getUserState('com_bfdownloadmanager.downloads.filter');
				$data->set(
					'state',
					$app->input->getInt(
						'state',
						((isset($filters['published']) && $filters['published'] !== '') ? $filters['published'] : null)
					)
				);
				$data->set('catid', $app->input->getInt('catid', (!empty($filters['category_id']) ? $filters['category_id'] : null)));
				$data->set('language', $app->input->getString('language', (!empty($filters['language']) ? $filters['language'] : null)));
				$data->set('access',
					$app->input->getInt('access', (!empty($filters['access']) ? $filters['access'] : JFactory::getConfig()->get('access')))
				);
			}
		}

		// If there are params fieldsets in the form it will fail with a registry object
		if (isset($data->params) && $data->params instanceof Registry)
		{
			$data->params = $data->params->toArray();
		}

		$this->preprocessData('com_bfdownloadmanager.download', $data);

		return $data;
	}

	/**
	 * Method to validate the form data.
	 *
	 * @param JForm $form The form to validate against.
	 * @param array $data The data to validate.
	 * @param string $group The name of the field group to validate.
	 *
	 * @return  array|boolean  Array of filtered data if valid, false otherwise.
	 *
	 * @see     JFormRule
	 * @see     JFilterInput
	 * @since   3.7.0
	 */
	public function validate($form, $data, $group = null)
	{
		// Don't allow to change the users if not allowed to access com_users.
		if (JFactory::getApplication()->isClient('administrator') && !JFactory::getUser()->authorise('core.manage', 'com_users'))
		{
			if (isset($data['created_by']))
			{
				unset($data['created_by']);
			}

			if (isset($data['modified_by']))
			{
				unset($data['modified_by']);
			}
		}

		if (is_array($_FILES['jform']['name']))
		{
			foreach ($_FILES['jform']['name'] as $id => $fileName)
			{
				switch ($id)
				{
					case 'downloadfile':
						if (!empty($_FILES['jform']['tmp_name']['downloadfile']) && @file_exists($_FILES['jform']['tmp_name']['downloadfile']))
						{
							$data['downloadfile_name'] = $fileName;
							$file = $_FILES['jform']['tmp_name']['downloadfile'];
							$data['downloadfile_size'] = filesize($file);
							$data['downloadfile'] = base64_encode(file_get_contents($file));
						}
						else
						{
							$table = $this->getTable();
							$data['downloadfile'] = $table->getItemFieldValue($data['id'], 'downloadfile');
						}
						break 2;
					case 'com_fields':
						// Array of file fields
						// Use function onUserBeforeDataValidation($form, &$data) of field plugin event
						break;
					default:
						break;
				}
			}
		}

		$suffix_list = BfdownloadmanagerHelper::getCategoryAttr($data['catid'], 'download_suffix_list');
		if (isset($data['downloadfile_name']) &&
			!BfdownloadmanagerHelper::validateFilenameSuffix($data['downloadfile_name'], $suffix_list))
		{
			$data['downloadfile'] = null;
			$data['downloadfile_size'] = null;
			$data['downloadfile_name'] = null;
		}

		// Filter and validate the form data.
		return parent::validate($form, $data, $group);
	}

	/**
	 * Method to save the form data.
	 *
	 * @param array $data The form data.
	 *
	 * @return  boolean  True on success.
	 *
	 * @since   1.6
	 */
	public function save($data)
	{
		$input = JFactory::getApplication()->input;
		$filter = JFilterInput::getInstance();

		if (isset($data['metadata']) && isset($data['metadata']['author']))
		{
			$data['metadata']['author'] = $filter->clean($data['metadata']['author'], 'TRIM');
		}

		if (isset($data['created_by_alias']))
		{
			$data['created_by_alias'] = $filter->clean($data['created_by_alias'], 'TRIM');
		}

		if (isset($data['images']) && is_array($data['images']))
		{
			$registry = new Registry($data['images']);

			$data['images'] = (string)$registry;
		}

		JLoader::register('CategoriesHelper', JPATH_ADMINISTRATOR . '/components/com_categories/helpers/categories.php');

		// Cast catid to integer for comparison
		$catid = (int)$data['catid'];

		// Check if New Category exists
		if ($catid > 0)
		{
			$catid = CategoriesHelper::validateCategoryId($data['catid'], BfdownloadmanagerHelper::$extension);
		}

		// Save New Category
		if ($catid == 0 && $this->canCreateCategory())
		{
			$table = array();
			$table['title'] = $data['catid'];
			$table['parent_id'] = 1;
			$table['extension'] = BfdownloadmanagerHelper::$extension;
			$table['language'] = $data['language'];
			$table['published'] = 1;

			// Create new category and get catid back
			$data['catid'] = CategoriesHelper::createCategory($table);
		}

		if (isset($data['urls']) && is_array($data['urls']))
		{
			$check = $input->post->get('jform', array(), 'array');

			foreach ($data['urls'] as $i => $url)
			{
				if ($url != false && ($i == 'urla' || $i == 'urlb' || $i == 'urlc'))
				{
					if (preg_match('~^#[a-zA-Z]{1}[a-zA-Z0-9-_:.]*$~', $check['urls'][$i]) == 1)
					{
						$data['urls'][$i] = $check['urls'][$i];
					}
					else
					{
						$data['urls'][$i] = JStringPunycode::urlToPunycode($url);
					}
				}
			}

			unset($check);

			$registry = new Registry($data['urls']);

			$data['urls'] = (string)$registry;
		}

		// Alter the title for save as copy
		if ($input->get('task') == 'save2copy')
		{
			$origTable = clone $this->getTable();
			$origTable->load($input->getInt('id'));

			if ($data['title'] == $origTable->title)
			{
				list($title, $alias) = $this->generateNewTitle($data['catid'], $data['alias'], $data['title']);
				$data['title'] = $title;
				$data['alias'] = $alias;
			}
			else
			{
				if ($data['alias'] == $origTable->alias)
				{
					$data['alias'] = '';
				}
			}

			$data['state'] = 0;
		}

		// Automatic handling of alias for empty fields
		if (in_array($input->get('task'), array('apply', 'save', 'save2new')) && (!isset($data['id']) || (int)$data['id'] == 0))
		{
			if ($data['alias'] == null)
			{
				if (JFactory::getConfig()->get('unicodeslugs') == 1)
				{
					$data['alias'] = JFilterOutput::stringURLUnicodeSlug($data['title']);
				}
				else
				{
					$data['alias'] = JFilterOutput::stringURLSafe($data['title']);
				}

				$table = JTable::getInstance('Bfdownloadmanager', 'JTable');

				if ($table->load(array('alias' => $data['alias'], 'catid' => $data['catid'])))
				{
					$msg = JText::_('COM_BFDOWNLOADMANAGER_SAVE_WARNING');
				}

				list($title, $alias) = $this->generateNewTitle($data['catid'], $data['alias'], $data['title']);
				$data['alias'] = $alias;

				if (isset($msg))
				{
					JFactory::getApplication()->enqueueMessage($msg, 'warning');
				}
			}
		}

		if (empty($data['downloadfile']))
		{
			$downloadfiledata = null;
			if (empty($data['id']))
			{
				$data['downloadfile_name'] = '';
			}
		}
		else
		{
			// If stored in database (legacy) move to filesystem
			$downloadfiledata = $data['downloadfile'];
			$data['downloadfile'] = '';
		}

		if (parent::save($data))
		{
			if (empty($data['id']))
			{
				$data['id'] = $this->getState($this->getName() . '.id');
			}

			if (isset($data['featured']))
			{
				$this->featured($data['id'], $data['featured']);
			}

			if (!empty($downloadfiledata))
			{
				if (!file_put_contents(BfdownloadmanagerHelperFile::getFilename($data['id'], $data['downloadfile_name']), $downloadfiledata))
				{
					$msg = JText::_('COM_BFDOWNLOADMANAGER_SAVE_NOWRITE');
					JFactory::getApplication()->enqueueMessage($msg, 'error');
				}
			}
			return true;
		}

		return false;
	}

	/**
	 * Method to toggle the featured setting of downloads.
	 *
	 * @param array $pks The ids of the items to toggle.
	 * @param integer $value The value to toggle to.
	 *
	 * @return  boolean  True on success.
	 */
	public function featured($pks, $value = 0)
	{
		// Sanitize the ids.
		$pks = (array)$pks;
		$pks = ArrayHelper::toInteger($pks);

		if (empty($pks))
		{
			$this->setError(JText::_('COM_BFDOWNLOADMANAGER_NO_ITEM_SELECTED'));

			return false;
		}

		$table = $this->getTable('Featured', 'BfdownloadmanagerTable');

		try
		{
			$db = $this->getDbo();
			$query = $db->getQuery(true)
				->update($db->quoteName('#__bfdownloadmanager'))
				->set('featured = ' . (int)$value)
				->where('id IN (' . implode(',', $pks) . ')');
			$db->setQuery($query);
			$db->execute();

			if ((int)$value == 0)
			{
				// Adjust the mapping table.
				// Clear the existing features settings.
				$query = $db->getQuery(true)
					->delete($db->quoteName('#__bfdownloadmanager_frontpage'))
					->where('bfdownloadmanager_id IN (' . implode(',', $pks) . ')');
				$db->setQuery($query);
				$db->execute();
			}
			else
			{
				// First, we find out which of our new featured downloads are already featured.
				$query = $db->getQuery(true)
					->select('f.bfdownloadmanager_id')
					->from('#__bfdownloadmanager_frontpage AS f')
					->where('bfdownloadmanager_id IN (' . implode(',', $pks) . ')');
				$db->setQuery($query);

				$oldFeatured = $db->loadColumn();

				// We diff the arrays to get a list of the downloads that are newly featured
				$newFeatured = array_diff($pks, $oldFeatured);

				// Featuring.
				$tuples = array();

				foreach ($newFeatured as $pk)
				{
					$tuples[] = $pk . ', 0';
				}

				if (count($tuples))
				{
					$columns = array('bfdownloadmanager_id', 'ordering');
					$query = $db->getQuery(true)
						->insert($db->quoteName('#__bfdownloadmanager_frontpage'))
						->columns($db->quoteName($columns))
						->values($tuples);
					$db->setQuery($query);
					$db->execute();
				}
			}
		} catch (Exception $e)
		{
			$this->setError($e->getMessage());

			return false;
		}

		$table->reorder();

		$this->cleanCache();

		return true;
	}

	/**
	 * A protected method to get a set of ordering conditions.
	 *
	 * @param object $table A record object.
	 *
	 * @return  array  An array of conditions to add to add to ordering queries.
	 *
	 * @since   1.6
	 */
	protected function getReorderConditions($table)
	{
		return array('catid = ' . (int)$table->catid);
	}

	/**
	 * Allows preprocessing of the JForm object.
	 *
	 * @param JForm $form The form object
	 * @param array $data The data to be merged into the form object
	 * @param string $group The plugin group to be executed
	 *
	 * @return  void
	 *
	 * @since   3.0
	 */
	protected function preprocessForm(JForm $form, $data, $group = 'content')
	{
		if ($this->canCreateCategory())
		{
			$form->setFieldAttribute('catid', 'allowAdd', 'true');
		}

		// Association content items
		if (JLanguageAssociations::isEnabled())
		{
			$languages = JLanguageHelper::getBfdownloadmanagerLanguages(false, true, null, 'ordering', 'asc');

			if (count($languages) > 1)
			{
				$addform = new SimpleXMLElement('<form />');
				$fields = $addform->addChild('fields');
				$fields->addAttribute('name', 'associations');
				$fieldset = $fields->addChild('fieldset');
				$fieldset->addAttribute('name', 'item_associations');

				foreach ($languages as $language)
				{
					$field = $fieldset->addChild('field');
					$field->addAttribute('name', $language->lang_code);
					$field->addAttribute('type', 'modal_download');
					$field->addAttribute('language', $language->lang_code);
					$field->addAttribute('label', $language->title);
					$field->addAttribute('translate_label', 'false');
					$field->addAttribute('select', 'true');
					$field->addAttribute('new', 'true');
					$field->addAttribute('edit', 'true');
					$field->addAttribute('clear', 'true');
				}

				$form->load($addform, false);
			}
		}

		parent::preprocessForm($form, $data, $group);
	}

	/**
	 * Custom clean the cache of com_bfdownloadmanager and content modules
	 *
	 * @param string $group The cache group
	 * @param integer $client_id The ID of the client
	 *
	 * @return  void
	 *
	 * @since   1.6
	 */
	protected function cleanCache($group = null, $client_id = 0)
	{
		parent::cleanCache('com_bfdownloadmanager');
		parent::cleanCache('mod_downloads_archive');
		parent::cleanCache('mod_downloads_categories');
		parent::cleanCache('mod_downloads_category');
		parent::cleanCache('mod_downloads_latest');
		parent::cleanCache('mod_downloads_news');
		parent::cleanCache('mod_downloads_popular');
	}

	/**
	 * Void hit function for pagebreak when editing content from frontend
	 *
	 * @return  void
	 *
	 * @since   3.6.0
	 */
	public function hit()
	{
		return;
	}

	/**
	 * Is the user allowed to create an on the fly category?
	 *
	 * @return  boolean
	 *
	 * @since   3.6.1
	 */
	private function canCreateCategory()
	{
		return JFactory::getUser()->authorise('core.create', 'com_bfdownloadmanager');
	}

	/**
	 * Delete #__bfdownloadmanager_frontpage items if the deleted downloads was featured
	 *
	 * @param object  &$pks The primary key related to the contents that was deleted.
	 *
	 * @return  boolean
	 *
	 * @since   3.7.0
	 */
	public function delete(&$pks)
	{
		$db = $this->getDbo();
		$query = 'SELECT id, downloadfile_name FROM ' . $db->quoteName('#__bfdownloadmanager') .
			' WHERE id IN (' . implode(',', $pks) . ") AND downloadfile_name > ''";
		$db->setQuery($query);
		$rows = $db->loadObjectList();

		foreach($rows as $row)
		{
			$filename = BfdownloadmanagerHelperFile::getFilename($row->id, $row->downloadfile_name);
			if (is_file($filename))
			{
				unlink($filename);
			}
		}

		$return = parent::delete($pks);

		if ($return)
		{
			// Now check to see if this downloads was featured if so delete it from the #__bfdownloadmanager_frontpage table
			$query = $db->getQuery(true)
				->delete($db->quoteName('#__bfdownloadmanager_frontpage'))
				->where('bfdownloadmanager_id IN (' . implode(',', $pks) . ')');
			$db->setQuery($query);
			$db->execute();
		}

		return $return;
	}
}
