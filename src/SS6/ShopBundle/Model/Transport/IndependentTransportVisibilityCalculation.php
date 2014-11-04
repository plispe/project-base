<?php

namespace SS6\ShopBundle\Model\Transport;

use SS6\ShopBundle\Model\Domain\Domain;
use SS6\ShopBundle\Model\Transport\TransportRepository;

class IndependentTransportVisibilityCalculation {

	/**
	 * @var \SS6\ShopBundle\Model\Transport\TransportRepository
	 */
	private $transportRepository;

	/**
	 * @var \SS6\ShopBundle\Model\Domain\Domain
	 */
	private $domain;

	public function __construct(
		TransportRepository $transportRepository,
		Domain $domain
	) {
		$this->transportRepository = $transportRepository;
		$this->domain = $domain;
	}

	/**
	 * @param \SS6\ShopBundle\Model\Transport\Transport $transport
	 * @param int $domainId
	 * @return boolean
	 */
	public function isIndependentlyVisible(Transport $transport, $domainId) {
		$locale = $this->domain->getDomainConfigById($domainId)->getLocale();

		if (strlen($transport->getName($locale)) === 0) {
			return false;
		}

		if ($transport->isHidden()) {
			return false;
		}

		if (!$this->isOnDomain($transport, $domainId)) {
			return false;
		}

		return true;
	}

	/**
	 * @param \SS6\ShopBundle\Model\Transport\Transport $transport
	 * @param int $domainId
	 * @return boolean
	 */
	private function isOnDomain(Transport $transport, $domainId) {
		$transportDomains = $this->transportRepository->getTransportDomainsByTransport($transport);
		foreach ($transportDomains as $transportDomain) {
			if ($transportDomain->getDomainId() === $domainId) {
				return true;
			}
		}

		return false;
	}

}
