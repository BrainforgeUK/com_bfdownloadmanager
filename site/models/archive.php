<?php
/**
 * @package     Joomla.Site
 * @subpackage  com_bfdownloadmanager
 *
 * @copyright   Copyright (C) 2018 Jonathan Brain. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

defined('_JEXEC') or die;

use Joomla\Utilities\ArrayHelper;

JLoader::register('BfdownloadmanagerModelDownloads', __DIR__ . '/downloads.php');

/**
 * Bfdownloadmanager Component Archive Model
 *
 * @since  1.5
 */
class BfdownloadmanagerModelArchive extends BfdownloadmanagerModelDownloads
{
	/**
	 * Model context string.
	 *
	 * @var        string
	 */
	public $_context = 'com_bfdownloadmanager.archive';

	/**
	 * Method to auto-populate the model state.
	 *
	 * Note. Calling getState in this method will result in recursion.
	 *
	 * @param string $ordering The field to order on.
	 * @param string $direction The direction to order on.
	 *
	 * @return  void
	 *
	 * @since   1.6
	 */
	protected function populateState($ordering = null, $direction = null)
	{
		parent::populateState();

		$app = JFactory::getApplication();

		// Add archive properties
		$params = $this->state->params;

		// Filter on archived downloads
		$this->setState('filter.published', 2);

		// Filter on month, year
		$this->setState('filter.month', $app->input->getInt('month'));
		$this->setState('filter.year', $app->input->getInt('year'));

		// Optional filter text
		$this->setState('list.filter', $app->input->getString('filter-search'));

		// Get list limit
		$itemid = $app->input->get('Itemid', 0, 'int');
		$limit = $app->getUserStateFromRequest('com_bfdownloadmanager.archive.list' . $itemid . '.limit', 'limit', $params->get('display_num'), 'uint');
		$this->setState('list.limit', $limit);

		// Set the archive ordering
		$downloadOrderby = $params->get('orderby_sec', 'rdate');
		$downloadOrderDate = $params->get('order_date');

		// No category ordering
		$secondary = BfdownloadmanagerHelperQuery::orderbySecondary($downloadOrderby, $downloadOrderDate);

		$this->setState('list.ordering', $secondary . ', a.created DESC');
		$this->setState('list.direction', '');
	}

	/**
	 * Get the master query for retrieving a list of downloads subject to the model state.
	 *
	 * @return  JDatabaseQuery
	 *
	 * @since   1.6
	 */
	protected function getListQuery()
	{
		$params = $this->state->params;
		$app = JFactory::getApplication('site');
		$catids = ArrayHelper::toInteger($app->input->get('catid', array(), 'array'));
		$catids = array_values(array_diff($catids, array(0)));
		$downloadOrderDate = $params->get('order_date');

		// Create a new query object.
		$query = parent::getListQuery();

		// Add routing for archive
		// Sqlsrv changes
		$case_when = ' CASE WHEN ';
		$case_when .= $query->charLength('a.alias', '!=', '0');
		$case_when .= ' THEN ';
		$a_id = $query->castAsChar('a.id');
		$case_when .= $query->concatenate(array($a_id, 'a.alias'), ':');
		$case_when .= ' ELSE ';
		$case_when .= $a_id . ' END as slug';

		$query->select($case_when);

		$case_when = ' CASE WHEN ';
		$case_when .= $query->charLength('c.alias', '!=', '0');
		$case_when .= ' THEN ';
		$c_id = $query->castAsChar('c.id');
		$case_when .= $query->concatenate(array($c_id, 'c.alias'), ':');
		$case_when .= ' ELSE ';
		$case_when .= $c_id . ' END as catslug';
		$query->select($case_when);

		// Filter on month, year
		// First, get the date field
		$queryDate = BfdownloadmanagerHelperQuery::getQueryDate($downloadOrderDate);

		if ($month = $this->getState('filter.month'))
		{
			$query->where($query->month($queryDate) . ' = ' . $month);
		}

		if ($year = $this->getState('filter.year'))
		{
			$query->where($query->year($queryDate) . ' = ' . $year);
		}

		if (count($catids) > 0)
		{
			$query->where('c.id IN (' . implode(', ', $catids) . ')');
		}

		return $query;
	}

	/**
	 * Method to get the archived download list
	 *
	 * @access public
	 * @return array
	 */
	public function getData()
	{
		$app = JFactory::getApplication();

		// Lets load the bfdownloadmanager if it doesn't already exist
		if (empty($this->_data))
		{
			// Get the page/component configuration
			$params = $app->getParams();

			// Get the pagination request variables
			$limit = $app->input->get('limit', $params->get('display_num', 20), 'uint');
			$limitstart = $app->input->get('limitstart', 0, 'uint');

			$query = $this->_buildQuery();

			$this->_data = $this->_getList($query, $limitstart, $limit);
		}

		return $this->_data;
	}

	/**
	 * JModelLegacy override to add alternating value for $odd
	 *
	 * @param string $query The query.
	 * @param integer $limitstart Offset.
	 * @param integer $limit The number of records.
	 *
	 * @return  array  An array of results.
	 *
	 * @throws  RuntimeException
	 * @since   12.2
	 */
	protected function _getList($query, $limitstart = 0, $limit = 0)
	{
		$result = parent::_getList($query, $limitstart, $limit);

		$odd = 1;

		foreach ($result as $k => $row)
		{
			$result[$k]->odd = $odd;
			$odd = 1 - $odd;
		}

		return $result;
	}

	/**
	 * Gets the archived downloads years
	 *
	 * @return   array
	 *
	 * @since    3.6.0
	 */
	public function getYears()
	{
		$db = $this->getDbo();
		$nullDate = $db->quote($db->getNullDate());
		$nowDate = $db->quote(JFactory::getDate()->toSql());

		$query = $db->getQuery(true);
		$years = $query->year($db->qn('created'));
		$query->select('DISTINCT (' . $years . ')')
			->from($db->qn('#__bfdownloadmanager'))
			->where($db->qn('state') . '= 2')
			->where('(publish_up = ' . $nullDate . ' OR publish_up <= ' . $nowDate . ')')
			->where('(publish_down = ' . $nullDate . ' OR publish_down >= ' . $nowDate . ')')
			->order('1 ASC');

		$db->setQuery($query);

		return $db->loadColumn();
	}
}
