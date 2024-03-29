<?php
/**
* @package RSForm! Pro
* @copyright (C) 2007-2019 www.rsjoomla.com
* @license GPL, http://www.gnu.org/copyleft/gpl.html
*/

// no direct access
defined( '_JEXEC' ) or die( 'Restricted access' );
$mainframe = JFactory::getApplication();
$out = '<div class="rsform-table" id="rsform-table2">'."\n";

foreach ($fields as $field) {
	if ($field->indetails) {
		$placeholders = array();

		if ($field->componentId < 0 && isset($headers[$field->componentId])) {
			$placeholders['caption'] = JText::_('RSFP_'.$headers[$field->componentId]);
			$placeholders['value']	 = $this->getStaticPlaceholder($headers[$field->componentId]);
		} else {
			$placeholders['caption'] = '{'.$field->FieldName.':caption}';
			$placeholders['value'] 	 = '{'.$field->FieldName.':value}';

			if ($showGoogleMap && $field->FieldType == RSFORM_FIELD_GMAPS)
			{
				$placeholders['value'] = '{' . $field->FieldName . ':map}';
			}
		}

		$mainframe->triggerEvent('onRsformBackendManageDirectoriesAfterCreatedPlaceholders', array($field, & $placeholders));

		if ($hideEmptyValues)
		{
			$out .= "\t" . '{if ' . $placeholders['value'] . '}' . "\n";
		}

		$out .= "\t".'<div class="rsform-table-item">'."\n";
		$out .= "\t\t".'<div class="rsform-field-title">'.$placeholders['caption'].'</div>'."\n";
		$out .= "\t\t".'<div class="rsform-field-value">'.$placeholders['value'].'</div>'."\n";
		$out .= "\t".'</div>'."\n";

		if ($hideEmptyValues)
		{
			$out .= "\t" . '{/if}' . "\n";
		}
	}
}

$out .= '</div>'."\n";
	
return $out;