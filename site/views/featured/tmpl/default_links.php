<?php
/**
 * @package     Joomla.Site
 * @subpackage  com_bfdownloadmanager
 *
 * @copyright   Copyright (C) 2018 Jonathan Brain. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

defined('_JEXEC') or die;
?>
<ol class="nav nav-tabs nav-stacked">
	<?php foreach ($this->link_items as &$item) : ?>
        <li>
            <a href="<?php echo JRoute::_(BfdownloadmanagerHelperRoute::getDownloadRoute($item->slug, $item->catid, $item->language)); ?>">
				<?php echo $item->title; ?></a>
        </li>
	<?php endforeach; ?>
</ol>
