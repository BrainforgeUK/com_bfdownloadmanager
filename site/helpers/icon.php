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

/**
 * Bfdownloadmanager Component HTML Helper
 *
 * @since  1.5
 */
abstract class JHtmlIcon
{
	/**
	 * Method to generate a link to the create item page for the given category
	 *
	 * @param object $category The category information
	 * @param Registry $params The item parameters
	 * @param array $attribs Optional attributes for the link
	 * @param boolean $legacy True to use legacy images, false to use icomoon based graphic
	 *
	 * @return  string  The HTML markup for the create item link
	 */
	public static function create($category, $params, $attribs = array(), $legacy = false)
	{
		$uri = JUri::getInstance();

		$url = 'index.php?option=com_bfdownloadmanager&task=download.add&return=' . base64_encode($uri) . '&a_id=0&catid=' . $category->id;

		$text = JLayoutHelper::render('joomla.content.icons.create', array('params' => $params, 'legacy' => $legacy));

		// Add the button classes to the attribs array
		if (isset($attribs['class']))
		{
			$attribs['class'] .= ' btn btn-primary';
		}
		else
		{
			$attribs['class'] = 'btn btn-primary';
		}

		$button = JHtml::_('link', JRoute::_($url), $text, $attribs);

		$output = '<span class="hasTooltip" title="' . JHtml::_('tooltipText', 'COM_BFDOWNLOADMANAGER_CREATE_DOWNLOAD') . '">' . $button . '</span>';

		return $output;
	}

	/**
	 * Method to generate a link to the email item page for the given download
	 *
	 * @param object $download The download information
	 * @param Registry $params The item parameters
	 * @param array $attribs Optional attributes for the link
	 * @param boolean $legacy True to use legacy images, false to use icomoon based graphic
	 *
	 * @return  string  The HTML markup for the email item link
	 */
	public static function email($download, $params, $attribs = array(), $legacy = false)
	{
		JLoader::register('MailtoHelper', JPATH_SITE . '/components/com_mailto/helpers/mailto.php');

		$uri = JUri::getInstance();
		$base = $uri->toString(array('scheme', 'host', 'port'));
		$template = JFactory::getApplication()->getTemplate();
		$link = $base . JRoute::_(BfdownloadmanagerHelperRoute::getDownloadRoute($download->slug, $download->catid, $download->language), false);
		$url = 'index.php?option=com_mailto&tmpl=component&template=' . $template . '&link=' . MailtoHelper::addLink($link);

		$status = 'width=400,height=350,menubar=yes,resizable=yes';

		$text = JLayoutHelper::render('joomla.content.icons.email', array('params' => $params, 'legacy' => $legacy));

		$attribs['title'] = JText::_('JGLOBAL_EMAIL_TITLE');
		$attribs['onclick'] = "window.open(this.href,'win2','" . $status . "'); return false;";
		$attribs['rel'] = 'nofollow';

		return JHtml::_('link', JRoute::_($url), $text, $attribs);
	}

	/**
	 * Display an edit icon for the download.
	 *
	 * This icon will not display in a popup window, nor if the download is trashed.
	 * Edit access checks must be performed in the calling code.
	 *
	 * @param object $download The download information
	 * @param Registry $params The item parameters
	 * @param array $attribs Optional attributes for the link
	 * @param boolean $legacy True to use legacy images, false to use icomoon based graphic
	 *
	 * @return  string    The HTML for the download edit icon.
	 *
	 * @since   1.6
	 */
	public static function edit($download, $params, $attribs = array(), $legacy = false)
	{
		$user = JFactory::getUser();
		$uri = JUri::getInstance();

		// Ignore if in a popup window.
		if ($params && $params->get('popup'))
		{
			return;
		}

		// Ignore if the state is negative (trashed).
		if ($download->state < 0)
		{
			return;
		}

		// Show checked_out icon if the download is checked out by a different user
		if (property_exists($download, 'checked_out')
			&& property_exists($download, 'checked_out_time')
			&& $download->checked_out > 0
			&& $download->checked_out != $user->get('id'))
		{
			$checkoutUser = JFactory::getUser($download->checked_out);
			$date = JHtml::_('date', $download->checked_out_time);
			$tooltip = JText::_('JLIB_HTML_CHECKED_OUT') . ' :: ' . JText::sprintf('COM_BFDOWNLOADMANAGER_CHECKED_OUT_BY', $checkoutUser->name)
				. ' <br /> ' . $date;

			$text = JLayoutHelper::render('joomla.content.icons.edit_lock', array('tooltip' => $tooltip, 'legacy' => $legacy));

			$output = JHtml::_('link', '#', $text, $attribs);

			return $output;
		}

		$bfdownloadmanagerUrl = BfdownloadmanagerHelperRoute::getDownloadRoute($download->slug, $download->catid, $download->language);
		$url = $bfdownloadmanagerUrl . '&task=download.edit&a_id=' . $download->id . '&return=' . base64_encode($uri);

		if ($download->state == 0)
		{
			$overlib = JText::_('JUNPUBLISHED');
		}
		else
		{
			$overlib = JText::_('JPUBLISHED');
		}

		$date = JHtml::_('date', $download->created);
		$author = $download->created_by_alias ?: $download->author;

		$overlib .= '&lt;br /&gt;';
		$overlib .= $date;
		$overlib .= '&lt;br /&gt;';
		$overlib .= JText::sprintf('COM_BFDOWNLOADMANAGER_WRITTEN_BY', htmlspecialchars($author, ENT_COMPAT, 'UTF-8'));

		$text = JLayoutHelper::render('joomla.content.icons.edit', array('download' => $download, 'overlib' => $overlib, 'legacy' => $legacy));

		$attribs['title'] = JText::_('JGLOBAL_EDIT_TITLE');
		$output = JHtml::_('link', JRoute::_($url), $text, $attribs);

		return $output;
	}

	/**
	 * Method to generate a popup link to print an download
	 *
	 * @param object $download The download information
	 * @param Registry $params The item parameters
	 * @param array $attribs Optional attributes for the link
	 * @param boolean $legacy True to use legacy images, false to use icomoon based graphic
	 *
	 * @return  string  The HTML markup for the popup link
	 */
	public static function print_popup($download, $params, $attribs = array(), $legacy = false)
	{
		$url = BfdownloadmanagerHelperRoute::getDownloadRoute($download->slug, $download->catid, $download->language);
		$url .= '&tmpl=component&print=1&layout=default';

		$status = 'status=no,toolbar=no,scrollbars=yes,titlebar=no,menubar=no,resizable=yes,width=640,height=480,directories=no,location=no';

		$text = JLayoutHelper::render('joomla.content.icons.print_popup', array('params' => $params, 'legacy' => $legacy));

		$attribs['title'] = JText::sprintf('JGLOBAL_PRINT_TITLE', htmlspecialchars($download->title, ENT_QUOTES, 'UTF-8'));
		$attribs['onclick'] = "window.open(this.href,'win2','" . $status . "'); return false;";
		$attribs['rel'] = 'nofollow';

		return JHtml::_('link', JRoute::_($url), $text, $attribs);
	}

	/**
	 * Method to generate a link to print an download
	 *
	 * @param object $download Not used, @deprecated for 4.0
	 * @param Registry $params The item parameters
	 * @param array $attribs Not used, @deprecated for 4.0
	 * @param boolean $legacy True to use legacy images, false to use icomoon based graphic
	 *
	 * @return  string  The HTML markup for the popup link
	 */
	public static function print_screen($download, $params, $attribs = array(), $legacy = false)
	{
		$text = JLayoutHelper::render('joomla.content.icons.print_screen', array('params' => $params, 'legacy' => $legacy));

		return '<a href="#" onclick="window.print();return false;">' . $text . '</a>';
	}
}
