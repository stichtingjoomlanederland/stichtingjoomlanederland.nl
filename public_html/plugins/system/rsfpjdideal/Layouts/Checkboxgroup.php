<?php
/**
 * @package     JDiDEAL
 * @subpackage  RSForm!Pro
 *
 * @author      Roland Dalmulder <contact@rolandd.com>
 * @copyright   Copyright (C) 2009 - 2022 RolandD Cyber Produksi. All rights reserved.
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 * @link        https://rolandd.com
 */

namespace Ropayments\Rsformpro\Layouts;

defined('_JEXEC') or die;

/**
 * Render a default checkbox.
 *
 * @package  JDiDEAL
 * @since    7.0.0
 */
class CheckboxGroup extends \RSFormProFieldCheckboxGroup
{
	use CheckboxTrait;

	/**
	 * Build the preview input field.
	 *
	 * @return  string  The rendered field.
	 *
	 * @since   7.0.0
	 */
	public function buildItem($data): string
	{
		return $this->buildInputField($data) . $this->buildLabel($data);
	}
}
