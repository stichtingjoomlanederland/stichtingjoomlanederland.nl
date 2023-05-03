<?php
/**
 * @package     JDiDEAL
 * @subpackage  RSForm!Pro
 *
 * @author      Roland Dalmulder <contact@rolandd.com>
 * @copyright   Copyright (C) 2009 - 2023 RolandD Cyber Produksi. All rights reserved.
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 * @link        https://rolandd.com
 */

namespace Ropayments\Rsformpro\Layouts;

use Joomla\CMS\Language\Text;
use RSFormProFieldTextbox;
use RSFormProHelper;

defined('_JEXEC') or die;

/**
 * Render a multiple products field.
 *
 * @package  JDiDEAL
 * @since    7.0.0
 */
class Textbox extends RSFormProFieldTextbox
{
	/**
	 * Get the preview of field
	 *
	 * @return  string
	 *
	 * @since   7.0.0
	 */
	public function getPreviewInput(): string
	{
		$value       = (string) $this->getProperty('DEFAULTVALUE', '');
		$size        = $this->getProperty('SIZE', 0);
		$placeholder = $this->getProperty('PLACEHOLDER', '');
		$codeIcon    = '<span class="rsficon jdicon-jdideal" style="font-size:24px;margin-right:5px"></span> ';

		if ($this->hasCode($value))
		{
			$value    = Text::_('RSFP_PHP_CODE_PLACEHOLDER');
			$codeIcon .= RSFormProHelper::getIcon('php');
		}

		return $codeIcon . '<input type="text" value="' . $this->escape($value) . '" size="' . (int) $size . '" '
			. (!empty($placeholder) ? 'placeholder="' . $this->escape($placeholder) . '"' : '') . '/>';
	}

	public function getFormInput()
	{
		$form       = RSFormProHelper::getForm($this->formId);
		$layoutName = (string) preg_replace('/[^A-Z0-9]/i', '', $form->FormLayoutName);

		$className = '\Ropayments\Rsformpro\Layouts\\' . ucfirst($layoutName) . '\Textbox';

		$config = [
			'formId'          => $this->formId,
			'layout'          => $layoutName,
			'componentId'     => $this->componentId,
			'data'            => $this->data,
			'value'           => $this->value,
			'invalid'         => $this->invalid,
			'errorClass'      => $this->errorClass,
			'fieldErrorClass' => $this->fieldErrorClass,
		];

		$field = new $className($config);

		$field->setId('jdideal-' . $this->componentId);

		return $field->output;
	}
}
