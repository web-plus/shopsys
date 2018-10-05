<?php

namespace Shopsys\FrameworkBundle\Component\Domain;

use Shopsys\FrameworkBundle\Component\Domain\Multidomain\MultidomainEntityDataCreator;
use Shopsys\FrameworkBundle\Component\Setting\Setting;
use Shopsys\FrameworkBundle\Component\Setting\SettingValueRepository;
use Shopsys\FrameworkBundle\Component\Translation\TranslatableEntityDataCreator;
use Shopsys\FrameworkBundle\Model\Pricing\Group\PricingGroupDataFactory;
use Shopsys\FrameworkBundle\Model\Pricing\Group\PricingGroupFacade;
use Shopsys\FrameworkBundle\Model\Pricing\Group\PricingGroupSettingFacade;

class DomainDataCreator
{
    const TEMPLATE_DOMAIN_ID = 1;

    /**
     * @var \Shopsys\FrameworkBundle\Component\Domain\Domain
     */
    private $domain;

    /**
     * @var \Shopsys\FrameworkBundle\Component\Setting\Setting
     */
    private $setting;

    /**
     * @var \Shopsys\FrameworkBundle\Component\Setting\SettingValueRepository
     */
    private $settingValueRepository;

    /**
     * @var \Shopsys\FrameworkBundle\Component\Domain\Multidomain\MultidomainEntityDataCreator
     */
    private $multidomainEntityDataCreator;

    /**
     * @var \Shopsys\FrameworkBundle\Component\Translation\TranslatableEntityDataCreator
     */
    private $translatableEntityDataCreator;

    /**
     * @var \Shopsys\FrameworkBundle\Model\Pricing\Group\PricingGroupDataFactory
     */
    private $pricingGroupDataFactory;

    /**
     * @var \Shopsys\FrameworkBundle\Model\Pricing\Group\PricingGroupFacade
     */
    private $pricingGroupFacade;

    private $pricingGroupSettingFacade;

    public function __construct(
        Domain $domain,
        Setting $setting,
        SettingValueRepository $settingValueRepository,
        MultidomainEntityDataCreator $multidomainEntityDataCreator,
        TranslatableEntityDataCreator $translatableEntityDataCreator,
        PricingGroupDataFactory $pricingGroupDataFactory,
        PricingGroupFacade $pricingGroupFacade,
        PricingGroupSettingFacade $pricingGroupSettingFacade
    ) {
        $this->domain = $domain;
        $this->setting = $setting;
        $this->settingValueRepository = $settingValueRepository;
        $this->multidomainEntityDataCreator = $multidomainEntityDataCreator;
        $this->translatableEntityDataCreator = $translatableEntityDataCreator;
        $this->pricingGroupDataFactory = $pricingGroupDataFactory;
        $this->pricingGroupFacade = $pricingGroupFacade;
        $this->pricingGroupSettingFacade = $pricingGroupSettingFacade;
    }

    /**
     * @return int
     */
    public function createNewDomainsData()
    {
        $newDomainsCount = 0;
        foreach ($this->domain->getAllIncludingDomainConfigsWithoutDataCreated() as $domainConfig) {
            $domainId = $domainConfig->getId();
            try {
                $this->setting->getForDomain(Setting::DOMAIN_DATA_CREATED, $domainId);
            } catch (\Shopsys\FrameworkBundle\Component\Setting\Exception\SettingValueNotFoundException $ex) {
                $locale = $domainConfig->getLocale();
                $isNewLocale = $this->isNewLocale($locale);
                $this->settingValueRepository->copyAllMultidomainSettings(self::TEMPLATE_DOMAIN_ID, $domainId);
                $this->setting->clearCache();
                $this->setting->setForDomain(Setting::BASE_URL, $domainConfig->getUrl(), $domainId);

                $this->processDefaultPricingGroupForNewDomain($domainId);

                $this->multidomainEntityDataCreator->copyAllMultidomainDataForNewDomain(self::TEMPLATE_DOMAIN_ID, $domainId);
                if ($isNewLocale) {
                    $this->translatableEntityDataCreator->copyAllTranslatableDataForNewLocale(
                        $this->getTemplateLocale(),
                        $locale
                    );
                }
                $newDomainsCount++;
            }
        }

        return $newDomainsCount;
    }

    /**
     * @param string $locale
     * @return bool
     */
    private function isNewLocale($locale)
    {
        foreach ($this->domain->getAll() as $domainConfig) {
            if ($domainConfig->getLocale() === $locale) {
                return false;
            }
        }

        return true;
    }

    /**
     * @return string
     */
    private function getTemplateLocale()
    {
        return $this->domain->getDomainConfigById(self::TEMPLATE_DOMAIN_ID)->getLocale();
    }

    /**
     * @param int $domainId
     */
    private function processDefaultPricingGroupForNewDomain(int $domainId)
    {
        $pricingGroup = $this->createDefaultPricingGroupForNewDomain($domainId);
        $this->setting->setForDomain(Setting::DEFAULT_PRICING_GROUP, $pricingGroup->getId(), $domainId);
    }

    /**
     * @param int $domainId
     * @return \Shopsys\FrameworkBundle\Model\Pricing\Group\PricingGroup
     */
    private function createDefaultPricingGroupForNewDomain(int $domainId)
    {
        $pricingGroupData = $this->pricingGroupDataFactory->create();
        $pricingGroupData->name = 'Default';
        return $this->pricingGroupFacade->create($pricingGroupData, $domainId);
    }

    /**
     * @return array
     */
    public function getDomainsIdsFromDatabaseThatAreNotConfiguredInDomainsConfigs()
    {
        $domainsIdsByDomainsConfigs = $this->domain->getAllIds();
        $domainsIdsBySettingValuesInDatabase = $this->settingValueRepository->getAllDomainsIdsThatAreConfiguredInTheDatabase();

        $domainsIdsWithoutDomainsConfigs = [];
        foreach ($domainsIdsBySettingValuesInDatabase as $domainIdBySettingValue) {
            if (!in_array($domainIdBySettingValue, $domainsIdsByDomainsConfigs)) {
                $domainsIdsWithoutDomainsConfigs[] = $domainIdBySettingValue;
            }
        }

        return $domainsIdsWithoutDomainsConfigs;
    }

    /**
     * @param array $domainIds
     * @return array
     */
    public function removeDomainsDataForDomainsIds(array $domainIds)
    {
        $removedDomainsIds = [];
        if (count($domainIds) === 0) {
            return $removedDomainsIds;
        }

        foreach ($domainIds as $domainId) {
            $this->settingValueRepository->removeAllMultidomainSettingsByDomainId($domainId);
            $this->setting->clearCache();

            $pricingGroups = $this->pricingGroupFacade->getByDomainId($domainId);

            foreach ($pricingGroups as $pricingGroup) {
                $this->pricingGroupFacade->delete($pricingGroup->getId());
            }

            $this->multidomainEntityDataCreator->removeAllMultidomainDataForOldDomain($domainId);
            $removedDomainsIds[] = $domainId;
        }

        return $removedDomainsIds;
    }

    /**
     * @return array
     */
    public function getLocalesFromDatabaseThatAreNotConfiguredInDomainsConfigs()
    {
        $domainsByDomainsConfigs = $this->domain->getAll();
        $localesFromDatabase = $this->translatableEntityDataCreator->getAllLocalesThatAreUsedInDatabase();

        return $this->getDiffBetweenLocalesFromDatabaseAndLocalesFromDomainsConfigs($domainsByDomainsConfigs, $localesFromDatabase);
    }

    /**
     * @param \Shopsys\FrameworkBundle\Component\Domain\Config\DomainConfig[] $domainsConfigs
     * @param array $localesFromDatabase
     * @return array
     */
    private function getDiffBetweenLocalesFromDatabaseAndLocalesFromDomainsConfigs(
        array $domainsConfigs,
        array $localesFromDatabase
    ) {
        $localesFromDomainsConfigs = [];
        foreach ($domainsConfigs as $domainsConfig) {
            $localesFromDomainsConfigs[] = $domainsConfig->getLocale();
        }

        return array_diff($localesFromDatabase, $localesFromDomainsConfigs);
    }

    /**
     * @param array $removedDomainIds
     * @return \Shopsys\FrameworkBundle\Model\Pricing\Group\PricingGroup[]
     */
    public function getPricingGroupsInUseByDomainIds(array $removedDomainIds)
    {
        $pricingGroupsInUse = [];
        foreach ($removedDomainIds as $domainId) {
            $pricingGroups = $this->pricingGroupFacade->getByDomainId($domainId);

            foreach ($pricingGroups as $pricingGroup) {
                if ($this->pricingGroupSettingFacade->existsUserWithPricingGroup($pricingGroup)) {
                    $pricingGroupsInUse[] = $pricingGroup;
                }
            }
        }

        return $pricingGroupsInUse;
    }
}
