<?php
/**
 * @package     Joomla.Platform
 * @subpackage  Form
 *
 * @copyright   Copyright (C) 2005 - 2018 Open Source Matters, Inc. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE
 */

defined('JPATH_PLATFORM') or die;

use Joomla\Registry\Registry;

JFormHelper::loadFieldClass('file');

/**
 * Form Field class for the Joomla Platform.
 * Provides an input field for files
 *
 * @link   http://www.w3.org/TR/html-markup/input.file.html#input.file
 * @since  11.1
 */
class JFormFieldDlmgrfile extends JFormFieldFile
{
	/**
	 * The form field type.
	 *
	 * @var    string
	 * @since  11.1
	 */
	protected $type = 'Dlmgrfile';

	/**
	 * Method to get the field input markup.
	 *
	 * @return  string  The field input markup.
	 *
	 * @since   11.1
	 */
	protected function getLabel()
	{
    $required = $this->required;
    $this->required = true;
    $html = parent::getLabel();
    $this->required = $required;
    return $html;
	}

	/**
	 * Method to get the field input markup.
	 *
	 * @return  string  The field input markup.
	 *
	 * @since   11.1
	 */
	protected function getInput()
	{
    $catid = $this->form->getData()->get('catid');
    $suffix_list = BfdownloadmanagerHelper::getCategoryAttr($catid, 'download_suffix_list');
    $suffix_list = BfdownloadmanagerHelper::suffixList2Array($suffix_list);

    if (!empty($suffix_list)) {
      $this->accept = '.' . implode(',.', $suffix_list);
    }
    return parent::getInput();
	}
}
