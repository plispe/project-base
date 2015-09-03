<?php

namespace SS6\ShopBundle\Model\Product;

use SS6\ShopBundle\Model\Pricing\Vat\VatFacade;
use SS6\ShopBundle\Model\Product\Pricing\ProductInputPriceFacade;
use SS6\ShopBundle\Model\Product\Product;

class ProductDataFactory {

	/**
	 * @var \SS6\ShopBundle\Model\Pricing\Vat\VatFacade
	 */
	private $vatFacade;

	/**
	 * @var \SS6\ShopBundle\Model\Product\Pricing\ProductInputPriceFacade
	 */
	private $productInputPriceFacade;

	public function __construct(
		VatFacade $vatFacade,
		ProductInputPriceFacade $productInputPriceFacade
	) {
		$this->vatFacade = $vatFacade;
		$this->productInputPriceFacade = $productInputPriceFacade;
	}

	/**
	 * @return \SS6\ShopBundle\Model\Product\ProductData
	 */
	public function createDefault() {
		$productData = new ProductData();

		$productData->vat = $this->vatFacade->getDefaultVat();

		return $productData;
	}

	/**
	 * @param \SS6\ShopBundle\Model\Product\Product $product
	 * @param \SS6\ShopBundle\Model\Product\ProductDomain[] $productDomains
	 * @return \SS6\ShopBundle\Model\Product\ProductData
	 */
	public function createFromProduct(Product $product, array $productDomains) {
		$productData = $this->createDefault();

		$translations = $product->getTranslations();
		$names = [];
		$variantAliases = [];
		foreach ($translations as $translation) {
			$names[$translation->getLocale()] = $translation->getName();
			$variantAliases[$translation->getLocale()] = $translation->getVariantAlias();
		}
		$productData->name = $names;
		$productData->variantAlias = $variantAliases;

		$productData->catnum = $product->getCatnum();
		$productData->partno = $product->getPartno();
		$productData->ean = $product->getEan();
		$productData->price = $this->productInputPriceFacade->getInputPrice($product);
		$productData->vat = $product->getVat();
		$productData->sellingFrom = $product->getSellingFrom();
		$productData->sellingTo = $product->getSellingTo();
		$productData->sellingDenied = $product->isSellingDenied();
		$productData->flags = $product->getFlags()->toArray();
		$productData->usingStock = $product->isUsingStock();
		$productData->stockQuantity = $product->getStockQuantity();
		$productData->availability = $product->getAvailability();
		$productData->outOfStockAvailability = $product->getOutOfStockAvailability();
		$productData->outOfStockAction = $product->getOutOfStockAction();

		$productData->hidden = $product->isHidden();
		$hiddenOnDomains = [];
		foreach ($productDomains as $productDomain) {
			if ($productDomain->isHidden()) {
				$hiddenOnDomains[] = $productDomain->getDomainId();
			}
		}
		$productData->hiddenOnDomains = $hiddenOnDomains;

		$productData->categoriesByDomainId = $product->getCategoriesIndexedByDomainId();
		$productData->priceCalculationType = $product->getPriceCalculationType();
		$productData->brand = $product->getBrand();

		return $productData;
	}

}
