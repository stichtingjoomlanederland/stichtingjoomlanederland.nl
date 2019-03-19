<?php
/**
 * @package		Perfecttemplate
 * @copyright	2018 Perfect Web Team / perfectwebteam.nl
 * @license		GNU General Public License version 3 or later
 */

use Joomla\CMS\Factory;

defined('_JEXEC') or die;

class PWTLayout
{

    /**
     * Function to get PWT Layout file
     *
     * @param        $type
     * @param string $data
     *
     * @return string
     *
     * @since version
     * @throws Exception
     */
    public static function render($type, $data = '')
    {
        $template = Factory::getApplication()->getTemplate();
        $jlayout  = new JLayoutFile($type, JPATH_THEMES . '/' . $template . '/html/layouts/template');

        return $jlayout->render($data);
    }

    /**
     * Function to get svg icon
     *
     * @param $type
     *
     * @return string
     *
     * @since 1.0.0
     * @throws Exception
     */
    public static function icon($type)
    {
        $template = Factory::getApplication()->getTemplate();

        return file_get_contents(JPATH_THEMES . '/' . $template . '/icons/' . $type . '.svg');
    }
}
