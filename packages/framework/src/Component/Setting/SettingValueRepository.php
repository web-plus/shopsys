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

    /**
     * @param int $fromDomainId
     * @param int $toDomainId
     */
    public function save($fromDomainId, $toDomainId)
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

    /**
     * @return array
     */
    public function getAllLocalesThatAreUsedInDatabase()
    {
        $resultSetMapping = new ResultSetMapping();
        $resultSetMapping->addScalarResult('value', 'value');

        $query = $this->em->createNativeQuery(
            'SELECT value
                FROM setting_values
                WHERE name = :domainLocale',
            $resultSetMapping
        );

        $rows = $query->execute([
            'domainLocale' => Setting::DOMAIN_LOCALE,
        ]);

        $locales = [];
        foreach ($rows as $row) {
            $locales[] = $row['value'];
        }

        return $locales;
    }
}
