<?php
/**
* @package RSForm! Pro
* @copyright (C) 2007-2019 www.rsjoomla.com
* @license GPL, http://www.gnu.org/copyleft/gpl.html
*/

defined('_JEXEC') or die('Restricted access');
?>
	<ul class="rsform_leftnav" id="rsform_firstleftnav">
		<?php
		$this->triggerEvent('onRsformBeforeShowComponents');

		foreach ($this->fieldGroups as $fieldGroupName => $fieldGroup)
		{
			if (!empty($fieldGroup->fields))
			{
				?>
				<li class="rsform_navtitle"><?php echo $this->escape($fieldGroup->name); ?></li>
				<?php
				foreach ($fieldGroup->fields as $field)
				{
					if (!empty($field->exists))
					{
						$displayTemplate = sprintf('displayTemplate(%d, %d);', $field->id, $field->exists);
					}
					else
					{
						$displayTemplate = sprintf('displayTemplate(%d);', $field->id);
					}
					?>
					<li><a href="javascript: void(0);" onclick="<?php echo $displayTemplate; ?>" id="rsfpc<?php echo $this->escape($field->id); ?>"><span class="<?php echo $this->escape($field->icon); ?>"></span><span class="inner-text"><?php echo $this->escape($field->name); ?></span></a></li>
					<?php
				}
			}

			if ($fieldGroupName === 'standard')
			{
				$this->triggerEvent('onRsformBackendAfterShowStandardComponents');
			}
		}
		?>
		<?php $this->triggerEvent('onRsformBackendAfterShowComponents'); ?>
	</ul>
	
	<input type="hidden" name="componentIdToEdit" id="componentIdToEdit" value="-1" />

<div id="componentscontent">
	<div id="componentsMessages"></div>
    <div class="alert alert-error center text-center" id="rsform_submit_button_msg" <?php if ($this->hasSubmitButton) { ?>style="display: none"<?php } ?>>
        <p><?php echo JHtml::_('image', 'com_rsform/admin/submit-help.jpg', JText::_('RSFP_NO_SUBMIT_BUTTON'), null, true); ?></p>
        <p><?php echo JText::_('RSFP_NO_SUBMIT_BUTTON'); ?></p>
    </div>

	<?php $this->triggerEvent('onRsformBackendBeforeGrid', array($this)); ?>

	<div id="gridlayoutdiv">
		<?php echo $this->loadTemplate('grid'); ?>
	</div><!-- gridlayout -->

	<?php $this->triggerEvent('onRsformBackendEditFormComponents', array($this)); ?>
</div>