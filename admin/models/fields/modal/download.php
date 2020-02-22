<?php
/**
 * @package     Joomla.Administrator
 * @subpackage  com_bfdownloadmanager
 *
 * @copyright   Copyright (C) 2018-2020 Jonathan Brain. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

defined('JPATH_BASE') or die;

/**
 * Supports a modal download picker.
 *
 * @since  1.6
 */
class JFormFieldModal_Download extends JFormField
{
	/**
	 * The form field type.
	 *
	 * @var    string
	 * @since  1.6
	 */
	protected $type = 'Modal_Download';

	/**
	 * Method to get the field input markup.
	 *
	 * @return  string  The field input markup.
	 *
	 * @since   1.6
	 */
	protected function getInput()
	{
		$allowNew = ((string)$this->element['new'] == 'true');
		$allowEdit = ((string)$this->element['edit'] == 'true');
		$allowClear = ((string)$this->element['clear'] != 'false');
		$allowSelect = ((string)$this->element['select'] != 'false');

		// Load language
		JFactory::getLanguage()->load('com_bfdownloadmanager', JPATH_ADMINISTRATOR);

		// The active download id field.
		$value = (int)$this->value > 0 ? (int)$this->value : '';

		// Create the modal id.
		$modalId = 'Download_' . $this->id;

		// Add the modal field script to the document head.
		JHtml::_('jquery.framework');
		JHtml::_('script', 'system/modal-fields.js', array('version' => 'auto', 'relative' => true));

		// Script to proxy the select modal function to the modal-fields.js file.
		if ($allowSelect)
		{
			static $scriptSelect = null;

			if (is_null($scriptSelect))
			{
				$scriptSelect = array();
			}

			if (!isset($scriptSelect[$this->id]))
			{
				JFactory::getDocument()->addScriptDeclaration("
				function jSelectDownload_" . $this->id . "(id, title, catid, object, url, language) {
					window.processModalSelect('Download', '" . $this->id . "', id, title, catid, object, url, language);
				}
				");

				$scriptSelect[$this->id] = true;
			}
		}

		// Setup variables for display.
		$linkDownloads = 'index.php?option=com_bfdownloadmanager&amp;view=downloads&amp;layout=modal&amp;tmpl=component&amp;' . JSession::getFormToken() . '=1';
		$linkDownload = 'index.php?option=com_bfdownloadmanager&amp;view=download&amp;layout=modal&amp;tmpl=component&amp;' . JSession::getFormToken() . '=1';

		if (isset($this->element['language']))
		{
			$linkDownloads .= '&amp;forcedLanguage=' . $this->element['language'];
			$linkDownload .= '&amp;forcedLanguage=' . $this->element['language'];
			$modalTitle = JText::_('COM_BFDOWNLOADMANAGER_CHANGE_DOWNLOAD') . ' &#8212; ' . $this->element['label'];
		}
		else
		{
			$modalTitle = JText::_('COM_BFDOWNLOADMANAGER_CHANGE_DOWNLOAD');
		}

		$urlSelect = $linkDownloads . '&amp;function=jSelectDownload_' . $this->id;
		$urlEdit = $linkDownload . '&amp;task=download.edit&amp;id=\' + document.getElementById("' . $this->id . '_id").value + \'';
		$urlNew = $linkDownload . '&amp;task=download.add';

		if ($value)
		{
			$db = JFactory::getDbo();
			$query = $db->getQuery(true)
				->select($db->quoteName('title'))
				->from($db->quoteName('#__bfdownloadmanager'))
				->where($db->quoteName('id') . ' = ' . (int)$value);
			$db->setQuery($query);

			try
			{
				$title = $db->loadResult();
			} catch (RuntimeException $e)
			{
				JError::raiseWarning(500, $e->getMessage());
			}
		}

		$title = empty($title) ? JText::_('COM_BFDOWNLOADMANAGER_SELECT_A_DOWNLOAD') : htmlspecialchars($title, ENT_QUOTES, 'UTF-8');

		// The current download display field.
		$html = '<span class="input-append">';
		$html .= '<input class="input-medium" id="' . $this->id . '_name" type="text" value="' . $title . '" disabled="disabled" size="35" />';

		// Select download button
		if ($allowSelect)
		{
			$html .= '<a'
				. ' class="btn hasTooltip' . ($value ? ' hidden' : '') . '"'
				. ' id="' . $this->id . '_select"'
				. ' data-toggle="modal"'
				. ' role="button"'
				. ' href="#ModalSelect' . $modalId . '"'
				. ' title="' . JHtml::tooltipText('COM_BFDOWNLOADMANAGER_CHANGE_DOWNLOAD') . '">'
				. '<span class="icon-file" aria-hidden="true"></span> ' . JText::_('JSELECT')
				. '</a>';
		}

		// New download button
		if ($allowNew)
		{
			$html .= '<a'
				. ' class="btn hasTooltip' . ($value ? ' hidden' : '') . '"'
				. ' id="' . $this->id . '_new"'
				. ' data-toggle="modal"'
				. ' role="button"'
				. ' href="#ModalNew' . $modalId . '"'
				. ' title="' . JHtml::tooltipText('COM_BFDOWNLOADMANAGER_NEW_DOWNLOAD') . '">'
				. '<span class="icon-new" aria-hidden="true"></span> ' . JText::_('JACTION_CREATE')
				. '</a>';
		}

		// Edit download button
		if ($allowEdit)
		{
			$html .= '<a'
				. ' class="btn hasTooltip' . ($value ? '' : ' hidden') . '"'
				. ' id="' . $this->id . '_edit"'
				. ' data-toggle="modal"'
				. ' role="button"'
				. ' href="#ModalEdit' . $modalId . '"'
				. ' title="' . JHtml::tooltipText('COM_BFDOWNLOADMANAGER_EDIT_DOWNLOAD') . '">'
				. '<span class="icon-edit" aria-hidden="true"></span> ' . JText::_('JACTION_EDIT')
				. '</a>';
		}

		// Clear download button
		if ($allowClear)
		{
			$html .= '<a'
				. ' class="btn' . ($value ? '' : ' hidden') . '"'
				. ' id="' . $this->id . '_clear"'
				. ' href="#"'
				. ' onclick="window.processModalParent(\'' . $this->id . '\'); return false;">'
				. '<span class="icon-remove" aria-hidden="true"></span>' . JText::_('JCLEAR')
				. '</a>';
		}

		$html .= '</span>';

		// Select download modal
		if ($allowSelect)
		{
			$html .= JHtml::_(
				'bootstrap.renderModal',
				'ModalSelect' . $modalId,
				array(
					'title' => $modalTitle,
					'url' => $urlSelect,
					'height' => '400px',
					'width' => '800px',
					'bodyHeight' => '70',
					'modalWidth' => '80',
					'footer' => '<a role="button" class="btn" data-dismiss="modal" aria-hidden="true">' . JText::_('JLIB_HTML_BEHAVIOR_CLOSE') . '</a>',
				)
			);
		}

		// New download modal
		if ($allowNew)
		{
			$html .= JHtml::_(
				'bootstrap.renderModal',
				'ModalNew' . $modalId,
				array(
					'title' => JText::_('COM_BFDOWNLOADMANAGER_NEW_DOWNLOAD'),
					'backdrop' => 'static',
					'keyboard' => false,
					'closeButton' => false,
					'url' => $urlNew,
					'height' => '400px',
					'width' => '800px',
					'bodyHeight' => '70',
					'modalWidth' => '80',
					'footer' => '<a role="button" class="btn" aria-hidden="true"'
						. ' onclick="window.processModalEdit(this, \'' . $this->id . '\', \'add\', \'download\', \'cancel\', \'item-form\'); return false;">'
						. JText::_('JLIB_HTML_BEHAVIOR_CLOSE') . '</a>'
						. '<a role="button" class="btn btn-primary" aria-hidden="true"'
						. ' onclick="window.processModalEdit(this, \'' . $this->id . '\', \'add\', \'download\', \'save\', \'item-form\'); return false;">'
						. JText::_('JSAVE') . '</a>'
						. '<a role="button" class="btn btn-success" aria-hidden="true"'
						. ' onclick="window.processModalEdit(this, \'' . $this->id . '\', \'add\', \'download\', \'apply\', \'item-form\'); return false;">'
						. JText::_('JAPPLY') . '</a>',
				)
			);
		}

		// Edit download modal
		if ($allowEdit)
		{
			$html .= JHtml::_(
				'bootstrap.renderModal',
				'ModalEdit' . $modalId,
				array(
					'title' => JText::_('COM_BFDOWNLOADMANAGER_EDIT_DOWNLOAD'),
					'backdrop' => 'static',
					'keyboard' => false,
					'closeButton' => false,
					'url' => $urlEdit,
					'height' => '400px',
					'width' => '800px',
					'bodyHeight' => '70',
					'modalWidth' => '80',
					'footer' => '<a role="button" class="btn" aria-hidden="true"'
						. ' onclick="window.processModalEdit(this, \'' . $this->id . '\', \'edit\', \'download\', \'cancel\', \'item-form\'); return false;">'
						. JText::_('JLIB_HTML_BEHAVIOR_CLOSE') . '</a>'
						. '<a role="button" class="btn btn-primary" aria-hidden="true"'
						. ' onclick="window.processModalEdit(this, \'' . $this->id . '\', \'edit\', \'download\', \'save\', \'item-form\'); return false;">'
						. JText::_('JSAVE') . '</a>'
						. '<a role="button" class="btn btn-success" aria-hidden="true"'
						. ' onclick="window.processModalEdit(this, \'' . $this->id . '\', \'edit\', \'download\', \'apply\', \'item-form\'); return false;">'
						. JText::_('JAPPLY') . '</a>',
				)
			);
		}

		// Note: class='required' for client side validation.
		$class = $this->required ? ' class="required modal-value"' : '';

		$html .= '<input type="hidden" id="' . $this->id . '_id" ' . $class . ' data-required="' . (int)$this->required . '" name="' . $this->name
			. '" data-text="' . htmlspecialchars(JText::_('COM_BFDOWNLOADMANAGER_SELECT_A_DOWNLOAD', true), ENT_COMPAT, 'UTF-8') . '" value="' . $value . '" />';

		return $html;
	}

	/**
	 * Method to get the field label markup.
	 *
	 * @return  string  The field label markup.
	 *
	 * @since   3.4
	 */
	protected function getLabel()
	{
		return str_replace($this->id, $this->id . '_id', parent::getLabel());
	}
}
