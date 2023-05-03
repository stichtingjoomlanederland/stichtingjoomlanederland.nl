<?php
/**
 * @package    JDiDEAL
 *
 * @author     Roland Dalmulder <contact@rolandd.com>
 * @copyright  Copyright (C) 2009 - 2023 RolandD Cyber Produksi. All rights reserved.
 * @license    GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 * @link       https://rolandd.com
 */

defined('_JEXEC') or die;

use Joomla\CMS\Installer\InstallerScript;

/**
 * Script to run on installation of RO Payments package.
 *
 * @package     ROPayments
 * @subpackage  Install
 * @since       6.0
 */
class Pkg_JdidealgatewayInstallerScript extends InstallerScript
{
    /**
     * Extension script constructor.
     *
     * @since   3.0.0
     */
    public function __construct()
    {
        $this->minimumJoomla = '3.7';
        $this->minimumPhp = '7.4';
    }
}
