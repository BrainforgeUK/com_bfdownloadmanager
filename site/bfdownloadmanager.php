<?php
/**
 * @package     Joomla.Site
 * @subpackage  com_bfdownloadmanager
 *
 * @copyright   Copyright (C) 2018 Jonathan Brain. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

defined('_JEXEC') or die;

JLoader::register('BfdownloadmanagerHelperRoute', JPATH_SITE . '/components/com_bfdownloadmanager/helpers/route.php');
JLoader::register('BfdownloadmanagerHelperQuery', JPATH_SITE . '/components/com_bfdownloadmanager/helpers/query.php');
JLoader::register('BfdownloadmanagerHelperAssociation', JPATH_SITE . '/components/com_bfdownloadmanager/helpers/association.php');

$input = JFactory::getApplication()->input;
$user  = JFactory::getUser();

$checkCreateEdit = ($input->get('view') === 'downloads' && $input->get('layout') === 'modal')
	|| ($input->get('view') === 'download' && $input->get('layout') === 'pagebreak');

if ($checkCreateEdit)
{
	// Can create in any category (component permission) or at least in one category
	$canCreateRecords = $user->authorise('core.create', 'com_bfdownloadmanager')
	 || count($user->getAuthorisedCategories('com_bfdownloadmanager', 'core.create')) > 0;

	// Instead of checking edit on all records, we can use **same** check as the form editing view
	$values = (array) JFactory::getApplication()->getUserState('com_bfdownloadmanager.edit.download.id');
	$isEditingRecords = count($values);

	$hasAccess = $canCreateRecords || $isEditingRecords;

	if (!$hasAccess)
	{
		JFactory::getApplication()->enqueueMessage(JText::_('JERROR_ALERTNOAUTHOR'), 'warning');

		return;
	}
}

JFactory::getDocument()->addStyleSheet('media/com_bfdownloadmanager/component.css');
$controller = JControllerLegacy::getInstance('Bfdownloadmanager');

$controller->execute(JFactory::getApplication()->input->get('task'));
$controller->redirect();
