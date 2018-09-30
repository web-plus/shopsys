<?php

namespace Shopsys\ShopBundle\Model\Product\PohodaTransfer;

use Doctrine\ORM\EntityManager;
use Shopsys\ShopBundle\Component\Doctrine\PohodaEntityManager;
use Shopsys\Plugin\Cron\SimpleCronModuleInterface;
use Shopsys\ShopBundle\Model\Transfer\TransferFacade;
use Symfony\Bridge\Monolog\Logger;

class PohodaImportProductCronModule implements SimpleCronModuleInterface
{
    /**
     * @var \Shopsys\ShopBundle\Model\Product\PohodaTransfer\PohodaImportProductFacade
     */
    private $pohodaImportProductFacade;
    /**
     * @var \Shopsys\ShopBundle\Model\Transfer\TransferFacade
     */
    private $transferFacade;
    /**
     * @var \Symfony\Bridge\Monolog\Logger
     */
    private $logger;
    /**
     * @var \Shopsys\ShopBundle\Component\Doctrine\PohodaEntityManager
     */
    private $pohodaEntityManager;
    /**
     * @var \Doctrine\ORM\EntityManager
     */
    private $entityManager;

    public function __construct(
        TransferFacade $transferFacade,
        PohodaImportProductFacade $pohodaImportProductFacade,
        PohodaEntityManager $pohodaEntityManager,
        EntityManager $entityManager
    ) {
        $this->transferFacade = $transferFacade;
        $this->pohodaImportProductFacade = $pohodaImportProductFacade;
        $this->pohodaEntityManager = $pohodaEntityManager;
        $this->entityManager = $entityManager;
    }

    /**
     * @param \Symfony\Bridge\Monolog\Logger $logger
     */
    public function setLogger(Logger $logger)
    {
        $this->logger = $logger;
    }

    /**
     * @inheritdoc
     */
    public function run()
    {
        $this->pohodaImportProductFacade->setLogger($this->logger);

        /**
         * get currentDateTime from Pohoda server as lastUpdateTime for future transfers
         */
        $dateTimeBeforeTransferFromPohodaServer = $this->pohodaEntityManager->getCurrentDateTimeFromPohodaDatabase();

        /**
         * concept of transfers agenda - table of transfers on dashboard in admin, ...
         */
        $transfer = $this->transferFacade->getTransferByIdentifier();
        if (!$transfer->isEnabled()) {
            $this->logger->warning(sprintf('Transfer `%s` is disabled, skip this transfer.', $transfer->getName()));
            return;
        }

        $this->logger->debug(sprintf('IS Pohoda time: %s', $dateTimeBeforeTransferFromPohodaServer->format('Y-m-d H:i:s')));

        try {
            $this->entityManager->beginTransaction();

            $this->pohodaImportProductFacade->actualizeProductsByLastUpdateTime($dateTimeBeforeTransferFromPohodaServer);

            $this->entityManager->commit();
        } catch (\Exception $exception) {
            $this->entityManager->rollback();
            throw $exception;
        }
    }
}