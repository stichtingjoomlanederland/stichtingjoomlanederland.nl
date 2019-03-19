<?php
/*
 * @package		pionline-template
 * @copyright	Copyright (c) 2014 Perfect Web Team / perfectwebteam.nl
 * @license		GNU General Public License version 3 or later
 */

use Joomla\CMS\Document\HtmlDocument;
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Uri\Uri;

defined('_JEXEC') or die;

/**
 * Renders the pagination footer
 *
 * @param   array $list Array containing pagination footer
 *
 * @return  string  HTML markup for the full pagination footer
 *
 * @since   3.0
 */
function pagination_list_footer($list)
{
    $html = "<div class=\"pagination\">\n";
    $html .= $list['pageslinks'];
    $html .= "\n<input type=\"hidden\" name=\"" . $list['prefix'] . "limitstart\" value=\"" . $list['limitstart'] . "\" />";
    $html .= "\n</div>";

    return $html;
}

/**
 * Renders the pagination list
 *
 * @param   array $list Array containing pagination information
 *
 * @return  string  HTML markup for the full pagination object
 *
 * @since   3.0
 */
function pagination_list_render($list)
{
    $html = null;

    // Calculate to display range of pages
    $currentPage = 1;
    $range       = 1;
    $step        = 5;
    foreach ($list['pages'] as $k => $page)
    {
        if (!$page['active'])
        {
            $currentPage = $k;
        }
    }
    if ($currentPage >= $step)
    {
        if ($currentPage % $step == 0)
        {
            $range = ceil($currentPage / $step) + 1;
        }
        else
        {
            $range = ceil($currentPage / $step);
        }
    }

    $html .= '<nav class="pagination__container" role="navigation" aria-label="' . Text::_('TPL_PAGINATION_WRAPPER_ARIALABEL_LABEL') . '">';
    $html .= '<ul class="pagination__list">';
    $html .= $list['start']['data'];
    $html .= $list['previous']['data'];

    foreach ($list['pages'] as $k => $page)
    {
        $offset = 'offset-' . ($k !== $currentPage ? abs($currentPage - $k) : '0');
        $html   .= preg_replace('/(?\'a\'li.*?)class=["\'](.*?)["\']/i', '$1class="' . $offset . " $2\"", $page['data']);
    }

    $html .= $list['next']['data'];
    $html .= $list['end']['data'];

    $html .= '</ul>';
    $html .= '</nav>';

    return $html;
}

/**
 * Renders an active item in the pagination block
 *
 * @param   JPaginationObject $item The current pagination object
 *
 * @return  string  HTML markup for active item
 *
 * @since   3.0
 */
function pagination_item_active(&$item)
{
    /** @var HtmlDocument $doc */
    $doc       = Factory::getDocument();
    $class     = 'pagination__item';
    $arialabel = null;

    // Check for "Start" item
    if ($item->text == Text::_('JLIB_HTML_START'))
    {
        $text      = Text::_('JLIB_HTML_START');
        $class     .= ' pagination--first';
        $arialabel = Text::sprintf('TPL_PAGINATION_ARIALABEL_GOTOXPAGE_LABEL', $text);
    }

    // Check for "Prev" item
    if ($item->text == Text::_('JPREV'))
    {
        $text  = Text::_('JPREV');
        $class .= ' pagination--prev';
        $doc->addHeadLink(htmlspecialchars(Uri::base() . ltrim($item->link, '/')), 'prev');
        $arialabel = Text::sprintf('TPL_PAGINATION_ARIALABEL_GOTOPAGEX_LABEL', $text);
    }

    // Check for "Next" item
    if ($item->text == Text::_('JNEXT'))
    {
        $text  = Text::_('JNEXT');
        $class .= ' pagination--next';
        $doc->addHeadLink(htmlspecialchars(Uri::base() . ltrim($item->link, '/')), 'next');
        $arialabel = Text::sprintf('TPL_PAGINATION_ARIALABEL_GOTOPAGEX_LABEL', $text);
    }

    // Check for "End" item
    if ($item->text == Text::_('JLIB_HTML_END'))
    {
        $text      = Text::_('JLIB_HTML_END');
        $class     = ' pagination--last';
        $arialabel = Text::sprintf('TPL_PAGINATION_ARIALABEL_GOTOPAGEX_LABEL', $text);
    }

    // If the display object isn't set already, just render the item with its text
    if (!isset($display))
    {
        $text      = $item->text;
        $arialabel = Text::sprintf('TPL_PAGINATION_ARIALABEL_GOTOXPAGE_LABEL', $text);
    }

    $display = '<span>' . $text . '</span>';

    return '<li class="' . $class . '"><a class="pagination__item__content" href="' . $item->link . '" aria-label="' . $arialabel . '"><span>' . $display . '</span></a></li>';
}

/**
 * Renders an inactive item in the pagination block
 *
 * @param   JPaginationObject $item The current pagination object
 *
 * @return  string  HTML markup for inactive item
 *
 * @since   3.0
 */
function pagination_item_inactive(&$item)
{
    $class     = 'pagination__item pagination__item--inactive';
    $text      = $item->text;
    $arialabel = null;

    // Check for "Start" item
    if ($item->text == Text::_('JLIB_HTML_START'))
    {
        $text  = Text::_('JLIB_HTML_START');
        $class .= ' pagination--first';
    }

    // Check for "Prev" item
    if ($item->text == Text::_('JPREV'))
    {
        $text  = Text::_('JPREV');
        $class .= ' pagination--prev';
    }

    // Check for "Next" item
    if ($item->text == Text::_('JNEXT'))
    {
        $text  = Text::_('JNEXT');
        $class .= ' pagination--next';
    }

    // Check for "End" item
    if ($item->text == Text::_('JLIB_HTML_END'))
    {
        $text  = Text::_('JLIB_HTML_END');
        $class .= ' pagination--last';
    }

    // Check if the item is the active page
    if (isset($item->active) && ($item->active))
    {
        $text      = $item->text;
        $class     .= ' pagination--active';
        $arialabel = 'aria-label="' . Text::sprintf('TPL_PAGINATION_ARIALABEL_CURRENTPAGE_LABEL', $item->text) . '"';
    }

    $display = '<span>' . $text . '</span>';

    return '<li class="' . $class . '"><span class="pagination__item__content" ' . $arialabel . ' >' . $display . '</span></li>';
}
