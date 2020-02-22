<?php
/**
 * @package     Joomla.Site
 * @subpackage  com_bfdownloadmanager
 *
 * @copyright   Copyright (C) 2018 Jonathan Brain. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

defined('_JEXEC') or die;

JHtml::addIncludePath(JPATH_COMPONENT . '/helpers/html');

// Create some shortcuts.
$params = &$this->item->params;
$n = count($this->items);
$listOrder = $this->escape($this->state->get('list.ordering'));
$listDirn = $this->escape($this->state->get('list.direction'));
?>

<?php if (empty($this->items)) { ?>
	<?php if ($this->params->get('show_no_downloads', 1)) { ?>
        <p><?php echo JText::_('COM_BFDOWNLOADMANAGER_NO_DOWNLOADS'); ?></p>
	<?php } ?>
<?php } else { ?>
	<?php
	$position = $this->pagination->limitstart;
	if ($this->pagination->limitstart)
	{
		$buttonText = 'COM_BFDOWNLOADMANAGER_DOWNLOAD_BTN_TEXT';
	}
	else
	{
		switch ($this->params->get('orderby_sec'))
		{
			case 'date':
				$buttonText = 'COM_BFDOWNLOADMANAGER_DOWNLOAD_BTN_TEXT_DATE';
				break;
			case 'hits':
				$buttonText = 'COM_BFDOWNLOADMANAGER_DOWNLOAD_BTN_TEXT_HITS';
				break;
			case 'rdate':
				$buttonText = 'COM_BFDOWNLOADMANAGER_DOWNLOAD_BTN_TEXT_RDATE';
				break;
			case 'rhits':
				$buttonText = 'COM_BFDOWNLOADMANAGER_DOWNLOAD_BTN_TEXT_RHITS';
				break;
			default:
				$buttonText = 'COM_BFDOWNLOADMANAGER_DOWNLOAD_BTN_TEXT';
				break;
		}
	}
	?>
	<?php
	$suffix_lists = array();
	foreach ($this->items as $i => $download)
	{
		if (!isset($suffix_lists[$download->catid]))
		{
			$suffix_lists[$download->catid] = BfdownloadmanagerHelper::getCategoryAttr($download->catid, 'download_suffix_list');
		}
		if ($suffix_lists[$download->catid] === false ||
			!BfdownloadmanagerHelper::validateFilenameSuffix($download->downloadfile_name, $suffix_lists[$download->catid]))
		{
			JFactory::getApplication()->enqueueMessage(jText::sprintf('COM_BFDOWNLOADMANAGER_ERROR_FILE_ERROR', $download->title), 'error');
			$suffix_lists[$download->catid] = false;
			continue;
		}

		$inline = true;
		$browserNav = BfdownloadmanagerHelper::getCategoryAttr($download->catid, 'download_browserNav');
		switch ($browserNav)
		{
			case 1:
				break;
			case 2:
			case 3:
				$inline = false;
				break;
			default:
				$ext = strtolower(pathinfo($download->downloadfile_name, PATHINFO_EXTENSION));
				switch ($ext)
				{
					case 'html';
					case 'pdf';
					case 'txt';
						$inline = false;
						$browserNav = 3;
						break;
					default:
						break;
				}
				break;
		}
		$href = JRoute::_(BfdownloadmanagerHelperRoute::getDownloadRoute($download->id, $download->catid, $download->language) . '&layout=fetch');
		?>
        <a <?php echo ($browserNav == 3) ? ' target="_blank"' : ''; ?> href="<?php echo $href; ?>">
            <button class="download-button download-button<?php echo $position; ?>">
                <p class="download-button-text">
					<?php echo jText::sprintf($buttonText, $this->escape($download->title, $download->downloadfile_name, $download->downloadfile_size)); ?>
                </p>
				<?php
				if ($inline) :
					echo '<p class="download-button-details">' .
						jText::sprintf('COM_BFDOWNLOADMANAGER_DOWNLOAD_BTN_DETAIL', $download->title, $download->downloadfile_name, $download->downloadfile_size) .
						'</p>';
				endif;
				?>
            </button>
        </a>
		<?php
		$buttonText = 'COM_BFDOWNLOADMANAGER_DOWNLOAD_BTN_TEXT';
		$position += 1;
		?>
	<?php } ?>
<?php } ?>

<?php // Add pagination links ?>
<?php if (!empty($this->items)) : ?>
	<?php if (($this->params->def('show_pagination', 2) == 1 || ($this->params->get('show_pagination') == 2)) && ($this->pagination->pagesTotal > 1)) : ?>
        <div class="pagination">

			<?php if ($this->params->def('show_pagination_results', 1)) : ?>
                <p class="counter pull-right">
					<?php echo $this->pagination->getPagesCounter(); ?>
                </p>
			<?php endif; ?>

			<?php echo $this->pagination->getPagesLinks(); ?>
        </div>
	<?php endif; ?>
<?php endif; ?>
