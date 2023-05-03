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

use Joomla\CMS\HTML\HTMLHelper;
use RSFormProFieldDiscount;
use RSFormProHelper;

defined('_JEXEC') or die;

/**
 * Render a multiple products field.
 *
 * @package  JDiDEAL
 * @since    7.0.0
 */
class Discount extends RSFormProFieldDiscount
{
	/**
	 * Render the discount preview.
	 *
	 * @return  string  The HTML output.
	 *
	 * @since   7.0.0
	 */
	public function getPreviewInput(): string
	{
		$value       = (string) $this->getProperty('COUPONS', '');
		$caption     = $this->getProperty('CAPTION', '');
		$size        = $this->getProperty('SIZE', 0);
		$placeholder = $this->getProperty('PLACEHOLDER', '');
		$codeIcon    = $this->hasCode($value) ? RSFormProHelper::getIcon('php') : '';

		$html = '<td>' . $caption . '</td>';
		$html .= '<td>' . $codeIcon
			. ' <span class="rsficon jdicon-jdideal" style="font-size:24px;margin-right:5px"></span>' .
			'<input type="text" value="" size="' . (int) $size . '" ' . (!empty($placeholder) ? 'placeholder="'
				. $this->escape($placeholder) . '"' : '') . '/>' .
			'</td>';

		return $html;
	}

	/**
	 * Render the discount field.
	 *
	 * @return  string  The HTML output.
	 *
	 * @since   7.0.0
	 */
	public function getFormInput(): string
	{
		HTMLHelper::_(
			'script',
			'https://cdnjs.cloudflare.com/ajax/libs/crypto-js/4.1.1/crypto-js.min.js',
			['version' => 'auto', 'relative' => false]
		);

		$value       = isset($this->value[$this->name]) ? $this->value[$this->name] : '';
		$name        = $this->getName();
		$id          = $this->getId();
		$size        = $this->getProperty('SIZE', 0);
		$maxlength   = $this->getProperty('MAXSIZE', 0);
		$placeholder = $this->getProperty('PLACEHOLDER', '');
		$type        = 'text';
		$attr        = $this->getAttributes();
		$additional  = '';

		if ($codes = RSFormProHelper::isCode($this->data['COUPONS']))
		{
			$codes     = RSFormProHelper::explode($codes);
			$discounts = [];

			foreach ($codes as $string)
			{
				if (strpos($string, '|') !== false)
				{
					[$couponValue, $code] = explode('|', $string, 2);
					$discounts[md5($code)] = $couponValue;
				}
			}

			$attr['data-componentid'] = $this->componentId;
			$attr['data-discounts']   = json_encode($discounts);
			$attr['data-formid']      = $this->formId;
		}

		$html = '<input';

		if ($attr)
		{
			foreach ($attr as $key => $values)
			{
				// @new feature - Some HTML attributes (type, size, maxlength) can be overwritten
				// directly from the Additional Attributes area
				if (($key == 'type' || $key == 'size' || $key == 'maxlength') && strlen($values))
				{
					${$key} = $values;
					continue;
				}
				$additional .= $this->attributeToHtml($key, $values);
			}
		}

		$html .= ' type="' . $this->escape($type) . '"' . ' value="' . $this->escape($value) . '"';

		if ($size)
		{
			$html .= ' size="' . (int) $size . '"';
		}

		if ($maxlength)
		{
			$html .= ' maxlength="' . (int) $maxlength . '"';
		}

		if (!empty($placeholder))
		{
			$html .= ' placeholder="' . $this->escape($placeholder) . '"';
		}

		$html .= ' name="' . $this->escape($name) . '"' .
			' id="' . $this->escape($id) . '"';

		$html .= $additional;

		$html .= ' />';

		return $html;
	}
}
