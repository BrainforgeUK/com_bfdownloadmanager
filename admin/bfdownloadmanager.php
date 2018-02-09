<?php
/**
 * @package     Joomla.Administrator
 * @subpackage  com_bfdloadmanager
 *
 * @copyright   Copyright (C) 2018 Jonathan Brain. Inc. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

defined('_JEXEC') or die;
JHtml::_('behavior.tabstate');

if (!JFactory::getUser()->authorise('core.manage', 'com_bfdownloadmanager'))
{
	throw new JAccessExceptionNotallowed(JText::_('JERROR_ALERTNOAUTHOR'), 403);
}

JLoader::register('BfdownloadmanagerHelper', __DIR__ . '/helpers/bfdownloadmanager.php');

$controller = JControllerLegacy::getInstance('Bfdownloadmanager');
$controller->execute(JFactory::getApplication()->input->get('task'));
$controller->redirect();
