<?php
/**
 * @package     Joomla.Site
 * @subpackage  com_bfdownloadmanager
 *
 * @copyright   Copyright (C) 2018 Jonathan Brain. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

defined('_JEXEC') or die;

use Joomla\Registry\Registry;
use Joomla\Utilities\ArrayHelper;

// Base this model on the backend version.
JLoader::register('BfdownloadmanagerModelDownload', JPATH_ADMINISTRATOR . '/components/com_bfdownloadmanager/models/download.php');

/**
 * Bfdownloadmanager Component Download Model
 *
 * @since  1.5
 */
class BfdownloadmanagerModelForm extends BfdownloadmanagerModelDownload
{
	/**
	 * Model typeAlias string. Used for version history.
	 *
	 * @var        string
	 */
	public $typeAlias = 'com_bfdownloadmanager.download';

	/**
	 * Method to auto-populate the model state.
	 *
	 * Note. Calling getState in this method will result in recursion.
	 *
	 * @return  void
	 *
	 * @since   1.6
	 */
	protected function populateState()
	{
		$app = JFactory::getApplication();

		// Load state from the request.
		$pk = $app->input->getInt('a_id');
		$this->setState('download.id', $pk);

		$this->setState('download.catid', $app->input->getInt('catid'));

		$return = $app->input->get('return', null, 'base64');
		$this->setState('return_page', base64_decode($return));

		// Load the parameters.
		$params = $app->getParams();
		$this->setState('params', $params);

		$this->setState('layout', $app->input->getString('layout'));
	}

	/**
	 * Method to get download data.
	 *
	 * @param   integer  $itemId  The id of the download.
	 *
	 * @return  mixed  Bfdownloadmanager item data object on success, false on failure.
	 */
	public function getItem($itemId = null)
	{
		$itemId = (int) (!empty($itemId)) ? $itemId : $this->getState('download.id');

		// Get a row instance.
		$table = $this->getTable();

		// Attempt to load the row.
		$return = $table->load($itemId);

		// Check for a table object error.
		if ($return === false && $table->getError())
		{
			$this->setError($table->getError());

			return false;
		}

		$properties = $table->getProperties(1);
		$value = ArrayHelper::toObject($properties, 'JObject');

		// Convert attrib field to Registry.
		$value->params = new Registry($value->attribs);

		// Compute selected asset permissions.
		$user   = JFactory::getUser();
		$userId = $user->get('id');
		$asset  = 'com_bfdownloadmanager.download.' . $value->id;

		// Check general edit permission first.
		if ($user->authorise('core.edit', $asset))
		{
			$value->params->set('access-edit', true);
		}

		// Now check if edit.own is available.
		elseif (!empty($userId) && $user->authorise('core.edit.own', $asset))
		{
			// Check for a valid user and that they are the owner.
			if ($userId == $value->created_by)
			{
				$value->params->set('access-edit', true);
			}
		}

		// Check edit state permission.
		if ($itemId)
		{
			// Existing item
			$value->params->set('access-change', $user->authorise('core.edit.state', $asset));
		}
		else
		{
			// New item.
			$catId = (int) $this->getState('download.catid');

			if ($catId)
			{
				$value->params->set('access-change', $user->authorise('core.edit.state', 'com_bfdownloadmanager.category.' . $catId));
				$value->catid = $catId;
			}
			else
			{
				$value->params->set('access-change', $user->authorise('core.edit.state', 'com_bfdownloadmanager'));
			}
		}

		$value->downloadtext = $value->introtext;

		if (!empty($value->fulltext))
		{
			$value->downloadtext .= '<hr id="system-readmore" />' . $value->fulltext;
		}

		// Convert the metadata field to an array.
		$registry = new Registry($value->metadata);
		$value->metadata = $registry->toArray();

		if ($itemId)
		{
			$value->tags = new JHelperTags;
			$value->tags->getTagIds($value->id, 'com_bfdownloadmanager.download');
			$value->metadata['tags'] = $value->tags;
		}

		return $value;
	}

	/**
	 * Get the return URL.
	 *
	 * @return  string	The return URL.
	 *
	 * @since   1.6
	 */
	public function getReturnPage()
	{
		return base64_encode($this->getState('return_page'));
	}

	/**
	 * Method to save the form data.
	 *
	 * @param   array  $data  The form data.
	 *
	 * @return  boolean  True on success.
	 *
	 * @since   3.2
	 */
	public function save($data)
	{
		// Associations are not edited in frontend ATM so we have to inherit them
		if (JLanguageAssociations::isEnabled() && !empty($data['id'])
			&& $associations = JLanguageAssociations::getAssociations('com_bfdownloadmanager', '#__bfdownloadmanager', 'com_bfdownloadmanager.item', $data['id']))
		{
			foreach ($associations as $tag => $associated)
			{
				$associations[$tag] = (int) $associated->id;
			}

			$data['associations'] = $associations;
		}

		return parent::save($data);
	}

	/**
	 * Allows preprocessing of the JForm object.
	 *
	 * @param   JForm   $form   The form object
	 * @param   array   $data   The data to be merged into the form object
	 * @param   string  $group  The plugin group to be executed
	 *
	 * @return  void
	 *
	 * @since   3.7.0
	 */
	protected function preprocessForm(JForm $form, $data, $group = 'bfdownloadmanager')
	{
		$params = $this->getState()->get('params');

		if ($params && $params->get('enable_category') == 1)
		{
			$form->setFieldAttribute('catid', 'default', $params->get('catid', 1));
			$form->setFieldAttribute('catid', 'readonly', 'true');
		}

		return parent::preprocessForm($form, $data, $group);
	}
}