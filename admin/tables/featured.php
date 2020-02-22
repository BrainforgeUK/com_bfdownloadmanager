<?php
/**
 * @package     Joomla.Administrator
 * @subpackage  com_bfdownloadmanager
 *
 * @copyright   Copyright (C) 2018 Jonathan Brain. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

defined('_JEXEC') or die;

/**
 * Featured Table class.
 *
 * @since  1.6
 */
class BfdownloadmanagerTableFeatured extends JTable
{
	/**
	 * Constructor
	 *
	 * @param JDatabaseDriver  &$db Database connector object
	 *
	 * @since   1.6
	 */
	public function __construct(&$db)
	{
		parent::__construct('#__bfdownloadmanager_frontpage', 'bfdownloadmanager_id', $db);
	}
}
