<?php

namespace Shopsys\FrameworkBundle\Component\Setting;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Query\ResultSetMapping;

class SettingValueRepository
{
    /**
     * @var \Doctrine\ORM\EntityManagerInterface
     */
    protected $em;

    /**
     * @param \Doctrine\ORM\EntityManagerInterface $em
     */
    public function __construct(EntityManagerInterface $em)
    {
        $this->em = $em;
    }

    /**
     * @return \Doctrine\ORM\EntityRepository
     */
    protected function getSettingValueRepository()
    {
        return $this->em->getRepository(SettingValue::class);
    }

    /**
     * @param int $domainId
     * @return \Shopsys\FrameworkBundle\Component\Setting\SettingValue[]
     */
    public function getAllByDomainId($domainId)
    {
        return $this->getSettingValueRepository()->findBy(['domainId' => $domainId]);
    }

    /**
     * @param int $fromDomainId
     * @param int $toDomainId
     */
    public function copyAllMultidomainSettings($fromDomainId, $toDomainId)
    {
        $query = $this->em->createNativeQuery(
            'INSERT INTO setting_values (name, value, type, domain_id)
            SELECT name, value, type, :toDomainId
            FROM setting_values
            WHERE domain_id = :fromDomainId
                AND EXISTS (
                    SELECT 1
                    FROM setting_values
                    WHERE domain_id IS NOT NULL
                        AND domain_id != :commonDomainId
                )',
            new ResultSetMapping()
        );
        $query->execute([
            'toDomainId' => $toDomainId,
            'fromDomainId' => $fromDomainId,
            'commonDomainId' => SettingValue::DOMAIN_ID_COMMON,
        ]);
    }

    public function getAllDomainsIdsThatAreConfiguredInTheDatabase()
    {
        $query = $this->getSettingValueRepository()->createQueryBuilder('sv')
            ->select('sv.domainId')
            ->where('sv.domainId > 0')
            ->groupBy('sv.domainId');

        $rows = $query->getQuery()->execute();

        $domainsIds = [];
        foreach ($rows as $row) {
            $domainsIds[] = $row['domainId'];
        }

        return $domainsIds;
    }

    /**
     * @param int $domainId
     */
    public function removeAllMultidomainSettingsByDomainId(int $domainId)
    {
        $nativeQuery = $this->em->createNativeQuery(
            'DELETE FROM setting_values WHERE domain_id = :domainId',
            new ResultSetMapping()
        );

        $nativeQuery->execute([
            'domainId' => $domainId,
        ]);
    }
}
