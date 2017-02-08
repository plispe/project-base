<?php

namespace Shopsys\ShopBundle\Model\AdminNavigation;

use Shopsys\ShopBundle\Model\AdminNavigation\MenuItem;

class Menu {

	/**
	 * @var \Shopsys\ShopBundle\Model\AdminNavigation\MenuItem[]
	 */
	private $items;

	/**
	 * @var \Shopsys\ShopBundle\Model\AdminNavigation\MenuItem[]
	 */
	private $regularItems;

	/**
	 * @var \Shopsys\ShopBundle\Model\AdminNavigation\MenuItem
	 */
	private $settingsItem;

	/**
	 * @param \Shopsys\ShopBundle\Model\AdminNavigation\MenuItem[] $items
	 */
	public function __construct(array $items) {
		$this->items = $items;

		$this->regularItems = [];

		foreach ($items as $item) {
			if ($item->getType() === MenuItem::TYPE_REGULAR) {
				$this->regularItems[] = $item;
			} elseif ($item->getType() === MenuItem::TYPE_SETTINGS) {
				$this->settingsItem = $item;
			}
		}

		if (!isset($this->settingsItem)) {
			throw new \Shopsys\ShopBundle\Model\AdminNavigation\Exception\MissingSettingsItemException(
				'Menu item of type ' . MenuItem::TYPE_SETTINGS . ' not found in config'
			);
		}
	}

	/**
	 * @return \Shopsys\ShopBundle\Model\AdminNavigation\MenuItem[]
	 */
	public function getItems() {
		return $this->items;
	}

	/**
	 * @return \Shopsys\ShopBundle\Model\AdminNavigation\MenuItem[]
	 */
	public function getRegularItems() {
		return $this->regularItems;
	}

	/**
	 * @return \Shopsys\ShopBundle\Model\AdminNavigation\MenuItem
	 */
	public function getSettingsItem() {
		return $this->settingsItem;
	}

	/**
	 * @return \Shopsys\ShopBundle\Model\AdminNavigation\MenuItem[]
	 */
	public function getSettingsItems() {
		return $this->settingsItem->getItems();
	}

	/**
	 * Finds deepest item matching specified route.
	 *
	 * @param string $route
	 * @param array|null $parameters
	 * @return \Shopsys\ShopBundle\Model\AdminNavigation\MenuItem|null
	 */
	private function getItemMatchingRoute($route, array $parameters = null) {
		$item = $this->getItemMatchingRouteRecursive($this->getItems(), $route, $parameters);

		return $item;
	}

	/**
	 * Finds deepest item matching specified route.
	 *
	 * @param string $route
	 * @param array|null $parameters
	 * @return \Shopsys\ShopBundle\Model\AdminNavigation\MenuItem|null
	 */
	private function getItemMatchingRouteRecursive(array $items, $route, array $parameters = null) {
		foreach ($items as $item) {
			if ($item->getItems() !== null) {
				$matchingItem = $this->getItemMatchingRouteRecursive($item->getItems(), $route, $parameters);

				if ($matchingItem !== null) {
					return $matchingItem;
				}
			}

			if ($this->isItemMatchingRoute($item, $route, $parameters)) {
				return $item;
			}
		}

		return null;
	}

	/**
	 * @param \Shopsys\ShopBundle\Model\AdminNavigation\MenuItem $item
	 * @param string $route
	 * @param array|null $parameters
	 * @return \Shopsys\ShopBundle\Model\AdminNavigation\MenuItem
	 */
	private function isItemMatchingRoute(MenuItem $item, $route, array $parameters = null) {
		if ($item->getRoute() !== $route) {
			return false;
		}

		if ($item->getRouteParameters() !== null) {
			foreach ($item->getRouteParameters() as $itemRouteParameterName => $itemRouteParameterValue) {
				if (!isset($parameters[$itemRouteParameterName])) {
					return false;
				}

				if ($parameters[$itemRouteParameterName] != $itemRouteParameterValue) {
					return false;
				}
			}
		}

		return true;
	}

	/**
	 * @param \Shopsys\ShopBundle\Model\AdminNavigation\MenuItem $item
	 * @return \Shopsys\ShopBundle\Model\AdminNavigation\MenuItem[]|null
	 */
	private function getItemPath(MenuItem $item) {
		return $this->getItemPathRecursive($this->getItems(), $item);
	}

	/**
	 * @param \Shopsys\ShopBundle\Model\AdminNavigation\MenuItem $item
	 * @return bool
	 */
	private function isItemDescendantOfSettings(MenuItem $item) {
		$itemPath = $this->getItemPath($item);
		if ($itemPath !== null) {
			foreach ($itemPath as $ancestor) {
				if ($ancestor->getType() === MenuItem::TYPE_SETTINGS) {
					return true;
				}
			}
		}

		return false;
	}

	/**
	 * @param \Shopsys\ShopBundle\Model\AdminNavigation\MenuItem $items
	 * @param \Shopsys\ShopBundle\Model\AdminNavigation\MenuItem $item
	 * @return \Shopsys\ShopBundle\Model\AdminNavigation\MenuItem[]|null
	 */
	private function getItemPathRecursive(array $items, MenuItem $item) {
		foreach ($items as $subitem) {
			if ($subitem === $item) {
				return [$item];
			}

			if ($subitem->getItems() !== null) {
				$path = $this->getItemPathRecursive($subitem->getItems(), $item);

				if ($path !== null) {
					array_unshift($path, $subitem);
					return $path;
				}
			}
		}

		return null;
	}

	/**
	 * @param string $route
	 * @param array|null $parameters
	 * @return \Shopsys\ShopBundle\Model\AdminNavigation\MenuItem[]
	 */
	public function getMenuPath($route, $parameters) {
		$matchingItem = $this->getItemMatchingRoute($route, $parameters);
		if ($matchingItem === null) {
			throw new \Shopsys\ShopBundle\Model\AdminNavigation\Exception\MenuItemNotMatchingRouteException($route, $parameters);
		}

		return $this->getItemPath($matchingItem);
	}

	/**
	 * @param string $route
	 * @param array|null $parameters
	 * @return bool
	 */
	public function isRouteMatchingDescendantOfSettings($route, $parameters) {
		$matchingItem = $this->getItemMatchingRoute($route, $parameters);
		if ($matchingItem === null) {
			return false;
		}

		return $this->isItemDescendantOfSettings($matchingItem);
	}
}
