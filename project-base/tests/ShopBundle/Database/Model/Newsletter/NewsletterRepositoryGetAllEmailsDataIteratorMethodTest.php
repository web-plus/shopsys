<?php

namespace Tests\ShopBundle\Database\Model\Newsletter;

use DateTimeImmutable;
use PHPUnit\Framework\Assert;
use Shopsys\ShopBundle\Model\Newsletter\NewsletterRepository;
use Shopsys\ShopBundle\Model\Newsletter\NewsletterSubscriber;
use Tests\ShopBundle\Test\DatabaseTestCase;

class NewsletterRepositoryGetAllEmailsDataIteratorMethodTest extends DatabaseTestCase
{
    const FIRST_DOMAIN_ID = 1;

    /**
     * @var NewsletterRepository
     */
    private $repository;

    protected function setUp(): void
    {
        parent::setUp();
        $this->repository = $this->getServiceByType(NewsletterRepository::class);
    }

    public function testEmpty(): void
    {
        $iterator = $this->repository->getAllEmailsDataIteratorByDomainId(self::FIRST_DOMAIN_ID);
        Assert::assertFalse($iterator->next());
    }

    public function testOneItem(): void
    {
        $this->createNewsletterSubscriber('no-reply@shopsys.com', '2018-02-05 16:14:28', self::FIRST_DOMAIN_ID);

        $iterator = $this->repository->getAllEmailsDataIteratorByDomainId(self::FIRST_DOMAIN_ID);
        $firstRow = $iterator->next()[0];

        $expected = [
            'email' => 'no-reply@shopsys.com',
            'createdAt' => '2018-02-05 16:14:28',
        ];

        Assert::assertSame($expected, $firstRow);
    }

    /**
     * @param string $email
     * @param string $datetime
     * @param int $domainId
     */
    private function createNewsletterSubscriber(string $email, string $datetime, $domainId): void
    {
        $newsletterSubscriber = new NewsletterSubscriber($email, new DateTimeImmutable($datetime), $domainId);
        $em = $this->getEntityManager();
        $em->persist($newsletterSubscriber);
        $em->flush($newsletterSubscriber);
    }
}