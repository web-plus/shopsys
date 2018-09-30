<?php

namespace Shopsys\ShopBundle\Model\Product\PohodaTransfer;

use Monolog\Logger;

class PohodaImportProductFacade
{
    private $productImportPohodaRepository;

    private $logger;

    /**
     * @param \Shopsys\ShopBundle\Model\Product\PohodaTransfer\PohodaImportProductRepository $productImportPohodaRepository
     */
    public function __construct (
        PohodaImportProductRepository $productImportPohodaRepository
    ) {
        $this->productImportPohodaRepository = $productImportPohodaRepository;
    }

    public function setLogger(Logger $logger)
    {
        $this->logger = $logger;
    }

    /**
     * @param \DateTime $dateTime
     */
    public function actualizeProductsByLastUpdateTime(\DateTime $dateTime)
    {
        $pohodaProductIds = $this->productImportPohodaRepository->getProductIdsUpdatedSinceDateTime($dateTime);

        /**
         * - log processed product ids
         * - possible implementation of simple queue for ids
         */

        $pohodaProducts = $this->productImportPohodaRepository->getProductsFromPohodaByPohodaIds($pohodaProductIds);

        /**
         * Implementation of some mapper for mapping pohoda products to shopsys Product
         *
         * and save.
         */
    }
}