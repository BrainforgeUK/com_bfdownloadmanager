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
$params    = &$this->item->params;
$n         = count($this->items);
$listOrder = $this->escape($this->state->get('list.ordering'));
$listDirn  = $this->escape($this->state->get('list.direction'));
?>

<?php if (empty($this->items)) { ?>
	<?php if ($this->params->get('show_no_downloads', 1)) { ?>
		<p><?php echo JText::_('COM_BFDOWNLOADMANAGER_NO_DOWNLOADS'); ?></p>
	<?php } ?>
<?php } else { ?>
    <?php
      $position = $this->pagination->limitstart;
      if ($this->pagination->limitstart) {
        $buttonText = 'COM_BFDOWNLOADMANAGER_DOWNLOAD_BTN_TEXT';
      } else {    
        switch($this->params->get('orderby_sec')) {
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
		<?php foreach ($this->items as $i => $download) { ?>
        <?php
        $ext = pathinfo($download->downloadfile_name, PATHINFO_EXTENSION);
        switch($ext) {
          case 'pdf':
            $target = 'target=pdfdownload';
            $inline = 0;
            break;
          default:
            $target = '';
            $inline = 1;
            break;
        }
        ?>
        <a <?php echo $target; ?> href="<?php echo JUri::base(); ?>component/bfdownloadmanager/download?layout=fetch&inline=<?php echo $inline; ?>&id=<?php echo $download->id; ?>"><button class="download-button download-button<?php echo $position; ?>">
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
          </button></a>
        <?php
          $buttonText = 'COM_BFDOWNLOADMANAGER_DOWNLOAD_BTN_TEXT';
          $position += 1;
        ?>
    <?php } ?>
<?php } ?>

<?php // Add pagination links ?>
<?php if (!empty($this->items)) : ?>
	<?php if (($this->params->def('show_pagination', 2) == 1  || ($this->params->get('show_pagination') == 2)) && ($this->pagination->pagesTotal > 1)) : ?>
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
