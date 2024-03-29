<?php
/**
* @package RSForm! Pro
* @copyright (C) 2007-2019 www.rsjoomla.com
* @license GPL, http://www.gnu.org/copyleft/gpl.html
*/

defined('_JEXEC') or die('Restricted access');

require_once JPATH_ADMINISTRATOR.'/components/com_rsform/helpers/fields/radiogroup.php';

class RSFormProFieldUikit3RadioGroup extends RSFormProFieldRadioGroup
{
	protected function buildLabel($data) {
		// For convenience
		extract($data);
		
		return '<label id="'.$this->escape($id).$i.'-lbl" for="'.$this->escape($id).$i.'">'.$this->buildInput($data) . ' ' . $item->label.'</label> ';
	}
	
	public function buildItem($data) {
		// uikit - <label><input></label>
		return $this->buildLabel($data);
	}
	
	public function setFlow() {
		$flow		= $this->getProperty('FLOW', 'HORIZONTAL');
		if ($flow != 'HORIZONTAL') {
			$this->glue = '<br />';
			$this->blocks = array('1' => 'uk-width-1-1', '2' => 'uk-width-1-2', '3' => 'uk-width-1-3', '4' => 'uk-width-1-4', '6' => 'uk-width-1-6');
			$this->splitterStart = '<div class="{block_size}">';
			$this->splitterEnd = '</div>';
			
			if ($flow != 'VERTICAL') {
				$this->gridStart = '<div class="uk-grid">';
				$this->gridEnd = '</div>';
			}
		}
	}

    // @desc All select lists should have a 'rsform-radio' class for easy styling
    public function getAttributes() {
        $attr = parent::getAttributes();
        if (strlen($attr['class'])) {
            $attr['class'] .= ' ';
        }
        $attr['class'] .= 'uk-radio';

        return $attr;
    }
}