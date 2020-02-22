<?php
/**
 * @package     Joomla.Site
 * @subpackage  com_bfdownloadmanager
 *
 * @copyright   Copyright (C) 2018 Jonathan Brain. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

defined('_JEXEC') or die;

JHtml::addIncludePath(JPATH_COMPONENT . '/helpers');

JHtml::_('behavior.caption');
?>
<div class="category-list<?php echo $this->pageclass_sfx; ?>">

	<?php
	switch ($this->category->params->get('show_download_textarea'))
	{
		case '1':
			$this->subtemplatename = 'downloads';
			break;
		default:
			$this->subtemplatename = 'simple_downloads';
			break;
	}

	echo JLayoutHelper::render('joomla.content.category_default', $this);
	?>

</div>
