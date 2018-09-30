<?php

namespace Shopsys\ShopBundle\Model\Product\PohodaTransfer;

use Doctrine\ORM\Query\ResultSetMapping;
use Shopsys\ShopBundle\Component\Doctrine\PohodaEntityManager;

class PohodaImportProductRepository
{
    const DEFAULT_POHODA_STOCK_ID = 1;
    const DEFAULT_POHODA_PRICE_GROUP_ID = 2;
    const DATE_TIME_FORMAT = 'Y-m-d H:i:s';
    const FIRST_UPDATE_TIME = '2000-01-01 00:00:00';

    /**
     * @var \Shopsys\ShopBundle\Component\Doctrine\PohodaEntityManager
     */
    private $pohodaEntityManager;


    public function __construct(PohodaEntityManager $pohodaEntityManager)
    {
        $this->pohodaEntityManager = $pohodaEntityManager;
    }

    /**
     * @param \DateTime|null $dateTime
     * @return string[]
     */
    public function getProductIdsUpdatedSinceDateTime(\DateTime $dateTime = null)
    {
        $resultSetMapping = new ResultSetMapping();
        $resultSetMapping->addScalarResult('ID', 'ID');

        $mssqlQuery = '
            SELECT ID FROM SKz 
            WHERE RefSklad = :defaultStockId AND IDS IN (SELECT IDS
                FROM SKz 
                WHERE DatSave > :lastUpdateDateTime
                GROUP BY IDS
            ) ORDER BY  DatSave ASC
        ';

        $query = $this->pohodaEntityManager->createNativeQuery($mssqlQuery, $resultSetMapping);
        $query->setParameters(
            [
                'defaultStockId' => self::DEFAULT_POHODA_STOCK_ID,
                'lastUpdateDateTime' => $dateTime === null ? self::FIRST_UPDATE_TIME : $dateTime->format(self::DATE_TIME_FORMAT),
            ]
        );

        $pohodaIds = [];
        foreach ($query->iterate() as $item) {
            $pohodaIds[] = $item['ID'];
        }
        return $pohodaIds;
    }

    /**
     * @param array $pohodaProductIds
     * @return array
     */
    public function getProductsFromPohodaByPohodaIds(array $pohodaProductIds)
    {
        /**
         * mssql query with select for database of Pohoda
         */
        $mssqlQuery = '';
        $query = $this->pohodaEntityManager->createNativeQuery($mssqlQuery, new ResultSetMapping());

        $query->execute();

        return [];
    }
}