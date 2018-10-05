<?php

namespace Shopsys\FrameworkBundle\Command;

use Doctrine\ORM\EntityManagerInterface;
use Shopsys\FrameworkBundle\Component\Domain\DomainDataCreator;
use Shopsys\FrameworkBundle\Component\Domain\DomainDbFunctionsFacade;
use Shopsys\FrameworkBundle\Component\Domain\Multidomain\MultidomainEntityClassFinderFacade;
use Shopsys\FrameworkBundle\Model\Localization\DbIndexesFacade;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Question\ChoiceQuestion;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class RemoveDomainsDataCommand extends Command
{
    /**
     * @var string
     */
    protected static $defaultName = 'shopsys:domains-data:remove';

    /**
     * @var \Doctrine\ORM\EntityManagerInterface
     */
    private $em;

    /**
     * @var \Shopsys\FrameworkBundle\Component\Domain\DomainDbFunctionsFacade
     */
    private $domainDbFunctionsFacade;

    /**
     * @var \Shopsys\FrameworkBundle\Component\Domain\DomainDataCreator
     */
    private $domainDataCreator;

    /**
     * @var \Shopsys\FrameworkBundle\Component\Domain\Multidomain\MultidomainEntityClassFinderFacade
     */
    private $multidomainEntityClassFinderFacade;

    /**
     * @var \Shopsys\FrameworkBundle\Model\Localization\DbIndexesFacade
     */
    private $dbIndexesFacade;

    /**
     * @param \Doctrine\ORM\EntityManagerInterface $em
     * @param \Shopsys\FrameworkBundle\Component\Domain\DomainDbFunctionsFacade $domainDbFunctionsFacade
     * @param \Shopsys\FrameworkBundle\Component\Domain\DomainDataCreator $domainDataCreator
     * @param \Shopsys\FrameworkBundle\Component\Domain\Multidomain\MultidomainEntityClassFinderFacade $multidomainEntityClassFinderFacade
     * @param \Shopsys\FrameworkBundle\Model\Localization\DbIndexesFacade $dbIndexesFacade
     */
    public function __construct(
        EntityManagerInterface $em,
        DomainDbFunctionsFacade $domainDbFunctionsFacade,
        DomainDataCreator $domainDataCreator,
        MultidomainEntityClassFinderFacade $multidomainEntityClassFinderFacade,
        DbIndexesFacade $dbIndexesFacade
    ) {
        $this->em = $em;
        $this->domainDbFunctionsFacade = $domainDbFunctionsFacade;
        $this->domainDataCreator = $domainDataCreator;
        $this->multidomainEntityClassFinderFacade = $multidomainEntityClassFinderFacade;
        $this->dbIndexesFacade = $dbIndexesFacade;

        parent::__construct();
    }

    protected function configure()
    {
        $this
            ->setDescription('Remove domains data for domains that are not listed in domains.yml');
    }

    /**
     * @param \Symfony\Component\Console\Input\InputInterface $input
     * @param \Symfony\Component\Console\Output\OutputInterface $output
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $symfonyStyleIo = new SymfonyStyle($input, $output);

        $this->em->transactional(function () use ($output, $symfonyStyleIo) {
            $this->doExecute($output, $symfonyStyleIo);
        });
    }

    private function doExecute(OutputInterface $output, SymfonyStyle $symfonyStyleIo)
    {
        $output->writeln('Start of removing old domains data.');

        $this->processDomainsRemoval($output, $symfonyStyleIo);
        $this->processLocalesRemoval($output, $symfonyStyleIo);
    }

    private function processDomainsRemoval(OutputInterface $output, SymfonyStyle $symfonyStyleIo)
    {
        $domainsIdsForRemoval = $this->domainDataCreator->getDomainsIdsFromDatabaseThatAreNotConfiguredInDomainsConfigs();
        if (count($domainsIdsForRemoval) === 0) {
            $output->writeln('<fg=green>There are no domains for removal.</fg=green>');
            return;
        }

        $pricingGroupsInUse = $this->domainDataCreator->getPricingGroupsInUseByDomainIds($domainsIdsForRemoval);
        if (count($pricingGroupsInUse) > 0) {

            $pricingGroupsInUseMessageParts = [];
            foreach ($pricingGroupsInUse as $pricingGroup) {
                $pricingGroupsInUseMessageParts[] = '
                    - Pricing group ' . $pricingGroup->getName() . ' on domain ' . $pricingGroup->getDomainId() . ' 
                ';
            }
            $output->writeln(
                '<fg=green>There are some pricing groups that cannot be removed because they are still in use. 
                You have to choose other one to be set everywhere where these ones are used and then run this command again.
                Pricing groups that needs to be unset:' . implode(' ', $pricingGroupsInUseMessageParts). '</fg=green>
            ');
            return;
        }
        $this->domainDbFunctionsFacade->actualizeDomainDbFunctionsByDomainConfigs();

        $removeDomainsQuestion = new ChoiceQuestion(
            'Some domains are not listed in domains.yml so they should be removed from database.
                Remove domains with ids: ' . implode(',', $domainsIdsForRemoval) . '?',
            ['y', 'n']
        );
        $removeDomainsResponse = $symfonyStyleIo->askQuestion($removeDomainsQuestion);

        if ($removeDomainsResponse === 'y') {
            $this->domainDataCreator->removeDomainsDataForDomainsIds($domainsIdsForRemoval);
            $output->writeln('<fg=green>Old domains removed: ' . implode(',', $domainsIdsForRemoval) . '.</fg=green>');
        }
    }

    private function processLocalesRemoval(OutputInterface $output, SymfonyStyle $symfonyStyleIo)
    {
        $localesForRemoval = $this->domainDataCreator->getLocalesFromDatabaseThatAreNotConfiguredInDomainsConfigs();
        if (count($localesForRemoval) === 0) {
            $output->writeln('<fg=green>There are no locales for removal.</fg=green>');
            return;
        }

        $removeLocalesQuestion = new ChoiceQuestion(
            'Some locales are not listed in domains.yml so they should be removed from database.
                Remove locales with codes: ' . implode(',', $localesForRemoval) . '?',
            ['y', 'n']
        );
        $removeLocalesResponse = $symfonyStyleIo->askQuestion($removeLocalesQuestion);

        if ($removeLocalesResponse === 'y') {
            // $this->domainDataCreator->removeDomainsDataForDomainsIds($domainsIdsForRemoval);
            $this->dbIndexesFacade->updateLocaleSpecificIndexes();
            $output->writeln('<fg=green>All locale specific db indexes updated.</fg=green>');
        }
    }
}
