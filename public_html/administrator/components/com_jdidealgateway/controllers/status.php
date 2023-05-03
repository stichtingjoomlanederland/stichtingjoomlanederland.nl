<?php
/**
 * @package    JDiDEAL
 *
 * @author     Roland Dalmulder <contact@rolandd.com>
 * @copyright  Copyright (C) 2009 - 2023 RolandD Cyber Produksi. All rights reserved.
 * @license    GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 * @link       https://rolandd.com
 */

use Joomla\CMS\MVC\Controller\FormController;

defined('_JEXEC') or die;

/**
 * Status controller.
 *
 * @package  JDiDEAL
 * @since    4.9.0
 */
class JdidealgatewayControllerStatus extends FormController
{
	/**
	 * The URL view list variable.
	 *
	 * @var    string
	 * @since  4.9.0
	 */
	protected $view_list = 'statuses';
}
