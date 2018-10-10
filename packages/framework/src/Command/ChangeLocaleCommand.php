<?php

namespace Shopsys\FrameworkBundle\Command;

use Doctrine\ORM\EntityManagerInterface;
use Shopsys\FrameworkBundle\Component\Domain\Domain;
use Shopsys\FrameworkBundle\Component\Domain\DomainDataCreator;
use Shopsys\FrameworkBundle\Component\Setting\Setting;
use Shopsys\FrameworkBundle\Component\Setting\SettingValueRepository;
use Shopsys\FrameworkBundle\Component\Translation\TranslatableEntityDataCreator;
use Shopsys\FrameworkBundle\Model\Localization\DbIndexesFacade;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class ChangeLocaleCommand extends Command
{
    /**
     * @var string
     */
    protected static $defaultName = 'shopsys:change-locale';

    /**
     * @var \Shopsys\FrameworkBundle\Component\Domain\DomainDataCreator
     */
    private $domainDataCreator;

    /**
     * @var \Shopsys\FrameworkBundle\Model\Localization\DbIndexesFacade
     */
    private $dbIndexesFacade;
    /**
     * @var \Shopsys\FrameworkBundle\Component\Domain\Domain
     */
    private $domain;
    /**
     * @var \Shopsys\FrameworkBundle\Component\Translation\TranslatableEntityDataCreator
     */
    private $translatableEntityDataCreator;
    /**
     * @var \Doctrine\ORM\EntityManagerInterface
     */
    private $em;
    /**
     * @var \Shopsys\FrameworkBundle\Component\Setting\SettingValueRepository
     */
    private $settingValueRepository;
    /**
     * @var \Shopsys\FrameworkBundle\Component\Setting\Setting
     */
    private $setting;

    /**
     * @param \Doctrine\ORM\EntityManagerInterface $em
     * @param \Shopsys\FrameworkBundle\Component\Domain\DomainDataCreator $domainDataCreator
     * @param \Shopsys\FrameworkBundle\Model\Localization\DbIndexesFacade $dbIndexesFacade
     * @param \Shopsys\FrameworkBundle\Component\Domain\Domain $domain
     * @param \Shopsys\FrameworkBundle\Component\Translation\TranslatableEntityDataCreator $translatableEntityDataCreator
     */
    public function __construct(
        EntityManagerInterface $em,
        DomainDataCreator $domainDataCreator,
        DbIndexesFacade $dbIndexesFacade,
        Domain $domain,
        TranslatableEntityDataCreator $translatableEntityDataCreator,
        SettingValueRepository $settingValueRepository,
        Setting $setting
    ) {
        $this->em = $em;
        $this->domainDataCreator = $domainDataCreator;
        $this->dbIndexesFacade = $dbIndexesFacade;
        $this->domain = $domain;
        $this->translatableEntityDataCreator = $translatableEntityDataCreator;
        $this->settingValueRepository = $settingValueRepository;
        $this->setting = $setting;

        parent::__construct();
    }

    protected function configure()
    {
        $this
            ->setDescription('Process new locales');
    }

    /**
     * @param \Symfony\Component\Console\Input\InputInterface $input
     * @param \Symfony\Component\Console\Output\OutputInterface $output
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->em->transactional(function () use ($output) {
            $this->doExecute($output);
        });
    }

    private function doExecute(OutputInterface $output)
    {
        $output->writeln('Start of changing locale.');

        $domainsWithNewLocale = $this->getDomainsWithNewLocale();

        if (count($domainsWithNewLocale) === 0) {
            $output->writeln('<fg=green>There are not new locales.</fg=green>');
            return;
        }

        $output->writeln('<fg=green>There are some new locales.</fg=green>');

        $this->processNewDomainsLocales($domainsWithNewLocale, $output);

        $output->writeln('<fg=green>New locales processed.</fg=green>');

        $this->dbIndexesFacade->updateLocaleSpecificIndexes();
        $output->writeln('<fg=green>All locale specific db indexes updated.</fg=green>');
    }

    /**
     * @return \Shopsys\FrameworkBundle\Component\Domain\Config\DomainConfig[]
     */
    private function getDomainsWithNewLocale()
    {
        $oldLocalesFromDatabase = $this->settingValueRepository->getAllLocalesThatAreUsedInDatabase();

        $domainsWithNewLocale = [];
        foreach ($this->domain->getAll() as $domain) {
            if (!in_array($domain->getLocale(), $oldLocalesFromDatabase)) {
                $domainsWithNewLocale[] = $domain;
            }
        }

        return $domainsWithNewLocale;
    }

    /**
     * @param \Shopsys\FrameworkBundle\Component\Domain\Config\DomainConfig[] $domains
     * @param \Symfony\Component\Console\Output\OutputInterface $output
     */
    private function processNewDomainsLocales(array $domains, OutputInterface $output)
    {
        $templateLocale = $this->domainDataCreator->getTemplateLocale();
        $alreadyProcessedLocales = [];
        foreach ($domains as $domain) {

            /**
             * If we changed the locale of template domain,
             * we meed to use its original locale as a template locale
             */
            if ($domain->getId() === DomainDataCreator::TEMPLATE_DOMAIN_ID) {
                $templateLocale = $this->setting->getForDomain(Setting::DOMAIN_LOCALE, $domain->getId());
            }

            if (!in_array($domain->getLocale(), $alreadyProcessedLocales)) {
                $this->translatableEntityDataCreator->copyAllTranslatableDataForNewLocale(
                    $templateLocale,
                    $domain->getLocale()
                );
                $alreadyProcessedLocales[] = $domain->getLocale();

                $this->setting->setForDomain(Setting::DOMAIN_LOCALE, $domain->getLocale(), $domain->getId());

                $output->writeln('Processed new locale:' . $domain->getLocale());
            }
        }
    }
}
