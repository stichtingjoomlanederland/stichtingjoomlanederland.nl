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

use Joomla\CMS\Component\Router\RouterBase;
use Joomla\CMS\Factory;
use Joomla\CMS\Uri\Uri;

/**
 * Routing class.
 *
 * @package  JDiDEAL
 * @since    4.3.1
 */
class JdidealgatewayRouter extends RouterBase
{
    /**
     * Generic method to preprocess a URL
     *
     * @param   array  $query  An associative array of URL arguments
     *
     * @return  array  The URL arguments to use to assemble the subsequent URL.
     *
     * @since   2.0.0
     * @throws  Exception
     */
    public function preprocess($query)
    {
        if (!isset($query['view']) && isset($query['task']) && strpos($query['task'], '.'))
        {
            [$query['view'], $query['task']] = explode('.', $query['task']);
        }

        if (!isset($query['view']) && !isset($query['Itemid']))
        {
            return $query;
        }

        if (!isset($query['view']))
        {
            // Get the view from the Itemid.
            $menuItem = Factory::getApplication()->getMenu()->getItem($query['Itemid']);

            $link = $menuItem->link;

            if ($menuItem->language !== '*')
            {
                $link .= '&lang=' . $menuItem->language;
            }

            $matches = [];
            preg_match("/view=([a-z,A-z,0-9]*)/", $link, $matches);

            $view  = count($matches) ? $matches[1] : null;
            $url   = Uri::getInstance($link);
            $query = $url->getQuery(true);
        }
        else
        {
            $view = $query['view'];
        }

        $languageTag = Factory::getApplication()->getLanguage()->getTag();

        if (isset($query['lang']))
        {
            $languageTag = $query['lang'];
        }

        $id = $query['id'] ?? null;

        $profileId = $query['profile_id'] ?? null;
        $layout = $query['layout'] ?? ($query['task'] ?? null);

        $menuItem = $this->getMenuItem(
            $view,
            $languageTag,
            $id,
            $profileId,
            $layout
        );

        if (isset($menuItem->id))
        {
            $query['Itemid'] = $menuItem->id;
        }

        return $query;
    }

    /**
	 * Build the route for the com_jdidealgateway component.
	 *
	 * @param   array  &$query  An array of URL arguments.
	 *
	 * @return  array  The URL arguments to use to assemble the subsequent URL.
	 *
	 * @since   4.3.1
	 */
	public function build(&$query)
	{
		// Initialize variables.
		$segments = [];

		// Check for view
		if (!isset($query['view']) && !isset($query['Itemid']))
		{
			// No view and no Itemid is gonna be hard ;)
			return $segments;
		}

		if (!isset($query['view']))
		{
			// Get the view from the Itemid.
			$link = Factory::getApplication()->getMenu()->getItem(
				$query['Itemid']
			)->link;

			$matches = array();
			preg_match("/view=([a-z,A-z,0-9]*)/", $link, $matches);

			$view = count($matches) ? $matches[1] : null;
		}
		else
		{
			// Easy mode
			$view = $query['view'];
			unset($query['view']);
		}

		if (isset($query['lang']))
		{
			unset($query['lang']);
		}

		switch ($view)
		{
			case 'pay':
				if (isset($query['task']))
				{
					switch ($query['task'])
					{
						case 'pay.result':
							$segments[] = 'result';
							break;
						case 'pay.sendmoney':
							$segments[] = 'send';
							break;
					}

					unset($query['task']);
				}

				$payFields = [
					'amount',
					'email',
					'remark',
					'number',
					'silent',
				];

				// Collect the values
				$values = [];

				foreach ($payFields as $name)
				{
					if (isset($query[$name]))
					{
						$values[$name] = $query[$name];
						unset($query[$name]);
					}
				}

				unset($query['task'], $query['profile_id']);

				if ($values)
				{
					$segments[] = base64_encode(json_encode($values));
				}
				break;
			case 'checkout':
			case 'status':
				break;
		}

		return $segments;
	}

    /**
     * Find the item ID for a given view.
     *
     * @param   string       $view      The name of the view to find the item ID for
     * @param   string       $language  The language to use for finding menu items
     * @param   int|null     $id        The id of an item
     * @param   string|null  $layout
     *
     * @return  mixed  The item ID or null if not found.
     *
     * @since   1.0.0
     */
    private function getMenuItem(
        string $view,
        string $language,
        int $id = null,
        int $profileId = null,
        string $layout = null
    ) {
        // Get all relevant menu items for the given language.
        $items = $this->menu->getItems(
            ['component', 'language'],
            ['com_jdidealgateway', $language]
        );

        // Get the items not assigned to a language
        if ($language !== '*')
        {
            $items = array_merge(
                $items,
                $this->menu->getItems(
                    ['component', 'language'],
                    ['com_jdidealgateway', '*']
                )
            );
        }

        $menuItem = null;

        if ($id)
        {
            foreach ($items as $item)
            {
                if (isset($item->query['view'], $item->query['id'])
                    && $item->query['view'] === $view
                    && (int) $item->query['id'] === $id)
                {
                    $menuItem = $item;
                    break;
                }
            }
        }

        if ($layout)
        {
            foreach ($items as $item)
            {
                if (isset($item->query['view'], $item->query['layout'])
                    && $item->query['view'] === $view
                    && $item->query['layout'] === $layout)
                {
                    $menuItem = $item;
                    break;
                }
            }
        }

        if (!$menuItem)
        {
            foreach ($items as $item)
            {
                if (isset($item->query['view'], $item->query['profile_id'])
                    && $item->query['view'] === $view
                    && (int) $item->query['profile_id'] === (int) $profileId
                    && !isset($item->query['layout']))
                {
                    $menuItem = $item;
                    break;
                }
            }
        }

        if (!$menuItem && $layout)
        {
            foreach ($items as $item)
            {
                if (isset($item->query['view'], $item->query['layout'], $item->query['profile_id'])
                    && $item->query['view'] === $view
                    && (int) $item->query['profile_id'] === (int) $profileId
                    && (string) $item->query['layout'] === $layout)
                {
                    $menuItem = $item;
                    break;
                }
            }
        }

        if (!$menuItem)
        {
            foreach ($items as $item)
            {
                if (isset($item->query['view'])
                    && $item->query['view'] === $view
                    && !isset($item->query['layout']))
                {
                    $menuItem = $item;
                    break;
                }
            }
        }

        return $menuItem;
    }

	/**
	 * Parse the segments of a URL.
	 *
	 * @param   array  $segments  The segments of the URL to parse.
	 *
	 * @return  array  The URL attributes to be used by the application.
	 *
	 * @since   4.3.1
	 */
	public function parse(&$segments)
	{
		$vars = [];

		// Get the view
		$menu = $this->menu->getActive();

		// Get the view from the menu
		if ($menu)
		{
			$view = $menu->query['view'];
		}

		// If there is no menu item, get the view from the URL
		if (empty($view))
		{
			$view = $this->app->input->get('view');
		}

		switch ($view)
		{
			case 'pay':
				switch ($segments[0])
				{
					case 'result':
						$vars['task'] = 'pay.result';
						break;
					case 'send':
						$vars['task'] = 'sendmoney';
						break;
				}

				if (isset($segments[1]))
				{
					$values = json_decode(base64_decode($segments[1]), true);

					if (is_array($values))
					{
						$vars = array_merge($vars, $values);
					}
				}

				$vars['view'] = 'pay';
				break;
		}

        $segments = [];

		return $vars;
	}
}
