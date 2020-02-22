<?php
/**
 * @package     Joomla.Administrator
 * @subpackage  com_bfdownloadmanager
 *
 * @copyright   Copyright (C) 2018 Jonathan Brain. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

defined('_JEXEC') or die;

use Joomla\Registry\Registry;

// Include the component HTML helpers.
JHtml::addIncludePath(JPATH_COMPONENT . '/helpers/html');

JHtml::_('behavior.formvalidator');
JHtml::_('behavior.keepalive');
JHtml::_('formbehavior.chosen', '#jform_catid', null, array('disable_search_threshold' => 0));
JHtml::_('formbehavior.chosen', 'select');

$this->configFieldsets = array('editorConfig');
$this->hiddenFieldsets = array('basic-limited');
$this->ignore_fieldsets = array('jmetadata', 'item_associations');

// Create shortcut to parameters.
$params = clone $this->state->get('params');
$params->merge(new Registry($this->item->attribs));

$show_download_textarea = BfdownloadmanagerHelper::getCategoryAttr($this->item->catid, 'show_download_textarea');
$download_suffix_list = BfdownloadmanagerHelper::getCategoryAttr($this->item->catid, 'download_suffix_list');

$app = JFactory::getApplication();
$input = $app->input;

$assoc = JLanguageAssociations::isEnabled();

JFactory::getDocument()->addScriptDeclaration('
	Joomla.submitbutton = function(task)
	{
		if (task == "download.cancel" || document.formvalidator.isValid(document.getElementById("item-form")))
		{
			jQuery("#permissions-sliders select").attr("disabled", "disabled");
			' . $this->form->getField('downloadtext')->save() . '
			Joomla.submitform(task, document.getElementById("item-form"));

			// @deprecated 4.0  The following js is not needed since 3.7.0.
			if (task !== "download.apply")
			{
				window.parent.jQuery("#downloadEdit' . (int)$this->item->id . 'Modal").modal("hide");
			}
		}
	};
');

// In case of modal
$isModal = $input->get('layout') == 'modal' ? true : false;
$layout = $isModal ? 'modal' : 'edit';
$tmpl = $isModal || $input->get('tmpl', '', 'cmd') === 'component' ? '&tmpl=component' : '';
?>

<form enctype="multipart/form-data"
      action="<?php echo JRoute::_('index.php?option=com_bfdownloadmanager&layout=' . $layout . $tmpl . '&id=' . (int)$this->item->id); ?>"
      method="post" name="adminForm" id="item-form" class="form-validate">

	<?php echo JLayoutHelper::render('joomla.edit.title_alias', $this); ?>

    <div class="form-horizontal">
		<?php echo JHtml::_('bootstrap.startTabSet', 'myTab', array('active' => 'general')); ?>

		<?php echo JHtml::_('bootstrap.addTab', 'myTab', 'general', JText::_('COM_BFDOWNLOADMANAGER_DOWNLOAD_CONTENT')); ?>
        <div class="row-fluid">
            <div class="span9">
                <fieldset class="adminform">
					<?php
					if (empty($this->item->downloadfile_name))
					{
						$buttonmsg = null;
					}
					else if (!BfdownloadmanagerHelper::validateFilenameSuffix($this->item->downloadfile_name, $download_suffix_list, $this->form))
					{
						$buttonmsg = jText::sprintf('COM_BFDOWNLOADMANAGER_DOWNLOAD_BUTTON', $this->item->downloadfile_name, $this->item->downloadfile_size);
						$this->item->downloadfile = null;
						$this->item->downloadfile_name = null;
						$this->item->downloadfile_size = null;
					}
					else
					{
						$buttonmsg = jText::sprintf('COM_BFDOWNLOADMANAGER_DOWNLOAD_BUTTON', $this->item->downloadfile_name, $this->item->downloadfile_size);
					}
					echo $this->form->renderField('downloadfile');
					?>
                    <input type="hidden" name="jform[downloadfile_name]"
                           value="<?php echo $this->item->downloadfile_name; ?>"/>
                    <input type="hidden" name="jform[downloadfile_size]"
                           value="<?php echo $this->item->downloadfile_size; ?>"/>
					<?php if (!empty($buttonmsg))
					{
						if (empty($this->item->downloadfile_name))
						{
							echo $buttonmsg;
						}
						else
						{ ?>
                            <a href="<?php echo dirname(JUri::base()); ?>/component/bfdownloadmanager/download?layout=fetch&id=<?php echo $this->item->id; ?>">
                <span id="downloadfilelink"
                      title="<?php echo jText::_('COM_BFDOWNLOADMANAGER_DOWNLOAD_BUTTON_DESC'); ?>">
                <?php echo $buttonmsg; ?>
                </span>
                            </a>
						<?php }
					} ?>
                    <hr/>
					<?php if ($show_download_textarea) { ?>
						<?php echo $this->form->getInput('downloadtext'); ?>
					<?php } else { ?>
                        <input type="hidden" id="jform_downloadtext" name="jform[downloadtext]"/>
						<?php echo jText::_('COM_BFDOWNLOADMANAGER_SHOW_DOWNLOADTEXTAREA_DISABLED'); ?>
					<?php } ?>
                </fieldset>
            </div>
            <div class="span3">
				<?php echo JLayoutHelper::render('joomla.edit.global', $this); ?>
            </div>
        </div>
		<?php echo JHtml::_('bootstrap.endTab'); ?>

		<?php // Do not show the images and links options if the edit form is configured not to. ?>
		<?php if ($params->get('show_urls_images_backend') == 1) : ?>
			<?php echo JHtml::_('bootstrap.addTab', 'myTab', 'images', JText::_('COM_BFDOWNLOADMANAGER_FIELDSET_URLS_AND_IMAGES')); ?>
            <div class="row-fluid form-horizontal-desktop">
                <div class="span6">
					<?php echo $this->form->renderField('images'); ?>
					<?php foreach ($this->form->getGroup('images') as $field) : ?>
						<?php echo $field->renderField(); ?>
					<?php endforeach; ?>
                </div>
                <div class="span6">
					<?php foreach ($this->form->getGroup('urls') as $field) : ?>
						<?php echo $field->renderField(); ?>
					<?php endforeach; ?>
                </div>
            </div>
			<?php echo JHtml::_('bootstrap.endTab'); ?>
		<?php endif; ?>

		<?php $this->show_options = $params->get('show_download_options', 1); ?>
		<?php echo JLayoutHelper::render('joomla.edit.params', $this); ?>

		<?php // Do not show the publishing options if the edit form is configured not to. ?>
		<?php if ($params->get('show_publishing_options', 1) == 1) : ?>
			<?php echo JHtml::_('bootstrap.addTab', 'myTab', 'publishing', JText::_('COM_BFDOWNLOADMANAGER_FIELDSET_PUBLISHING')); ?>
            <div class="row-fluid form-horizontal-desktop">
                <div class="span6">
					<?php echo JLayoutHelper::render('joomla.edit.publishingdata', $this); ?>
                </div>
                <div class="span6">
					<?php echo JLayoutHelper::render('joomla.edit.metadata', $this); ?>
                </div>
            </div>
			<?php echo JHtml::_('bootstrap.endTab'); ?>
		<?php endif; ?>


		<?php if (!$isModal && $assoc) : ?>
			<?php echo JHtml::_('bootstrap.addTab', 'myTab', 'associations', JText::_('JGLOBAL_FIELDSET_ASSOCIATIONS')); ?>
			<?php echo $this->loadTemplate('associations'); ?>
			<?php echo JHtml::_('bootstrap.endTab'); ?>
		<?php elseif ($isModal && $assoc) : ?>
            <div class="hidden"><?php echo $this->loadTemplate('associations'); ?></div>
		<?php endif; ?>

		<?php if ($this->canDo->get('core.admin')) : ?>
			<?php echo JHtml::_('bootstrap.addTab', 'myTab', 'editor', JText::_('COM_BFDOWNLOADMANAGER_SLIDER_EDITOR_CONFIG')); ?>
			<?php echo $this->form->renderFieldset('editorConfig'); ?>
			<?php echo JHtml::_('bootstrap.endTab'); ?>
		<?php endif; ?>

		<?php if ($this->canDo->get('core.admin')) : ?>
			<?php echo JHtml::_('bootstrap.addTab', 'myTab', 'permissions', JText::_('COM_BFDOWNLOADMANAGER_FIELDSET_RULES')); ?>
			<?php echo $this->form->getInput('rules'); ?>
			<?php echo JHtml::_('bootstrap.endTab'); ?>
		<?php endif; ?>

		<?php echo JHtml::_('bootstrap.endTabSet'); ?>

        <input type="hidden" name="task" value=""/>
        <input type="hidden" name="return" value="<?php echo $input->getCmd('return'); ?>"/>
        <input type="hidden" name="forcedLanguage" value="<?php echo $input->get('forcedLanguage', '', 'cmd'); ?>"/>
		<?php echo JHtml::_('form.token'); ?>
    </div>
</form>
