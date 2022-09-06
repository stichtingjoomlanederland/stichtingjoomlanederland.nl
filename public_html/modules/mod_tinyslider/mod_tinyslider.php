<?php
/**
 * @copyright	@copyright	Copyright (c) 2022 R2H (https://www.r2h.nl). All rights reserved.
 * @license		http://www.gnu.org/licenses/gpl-2.0.html GNU/GPL
 */

// no direct access
defined('_JEXEC') or die;

use Joomla\CMS\Helper\ModuleHelper;

// Get the module parameters
$slides = (array) $params->get('slides', true);

require ModuleHelper::getLayoutPath('mod_tinyslider', $params->get('layout', 'default'));
