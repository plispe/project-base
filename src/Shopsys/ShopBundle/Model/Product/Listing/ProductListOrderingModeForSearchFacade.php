<?php

namespace Shopsys\ShopBundle\Model\Product\Listing;

use Shopsys\ShopBundle\Model\Product\Listing\ProductListOrderingModeService;
use Symfony\Component\HttpFoundation\Request;

class ProductListOrderingModeForSearchFacade {

	const COOKIE_NAME = 'productSearchOrderingMode';

	/**
	 * @var \Shopsys\ShopBundle\Model\Product\Listing\ProductListOrderingModeService
	 */
	private $productListOrderingModeService;

	public function __construct(ProductListOrderingModeService $productListOrderingModeService) {
		$this->productListOrderingModeService = $productListOrderingModeService;
	}

	/**
	 * @return \Shopsys\ShopBundle\Model\Product\Listing\ProductListOrderingConfig
	 */
	public function getProductListOrderingConfig() {
		return new ProductListOrderingConfig(
			[
				ProductListOrderingModeService::ORDER_BY_RELEVANCE => t('relevance'),
				ProductListOrderingModeService::ORDER_BY_PRIORITY => t('TOP'),
				ProductListOrderingModeService::ORDER_BY_NAME_ASC => t('alphabetically A -> Z'),
				ProductListOrderingModeService::ORDER_BY_NAME_DESC => t('alphabetically Z -> A'),
				ProductListOrderingModeService::ORDER_BY_PRICE_ASC => t('from the cheapest'),
				ProductListOrderingModeService::ORDER_BY_PRICE_DESC => t('from most expensive'),
			],
			ProductListOrderingModeService::ORDER_BY_RELEVANCE,
			self::COOKIE_NAME
		);
	}

	/**
	 * @param \Symfony\Component\HttpFoundation\Request $request
	 * @return string
	 */
	public function getOrderingModeFromRequest(Request $request) {
		return $this->productListOrderingModeService->getOrderingModeFromRequest(
			$request,
			$this->getProductListOrderingConfig()
		);
	}

}
