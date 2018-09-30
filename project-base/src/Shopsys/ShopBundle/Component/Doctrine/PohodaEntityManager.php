<?php

namespace Shopsys\ShopBundle\Component\Doctrine;

use Doctrine\Common\EventManager;
use Doctrine\ORM\Configuration;
use Doctrine\ORM\Decorator\EntityManagerDecorator;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\ORMException;
use Doctrine\ORM\Query\ResultSetMapping;

class PohodaEntityManager extends EntityManagerDecorator
{

    /**
     * Factory method to create EntityManager instances.
     *
     * @param mixed         $conn         an array with the connection parameters or an existing Connection instance
     * @param Configuration $config       the Configuration instance to use
     * @param EventManager  $eventManager the EventManager instance to use
     *
     * @throws \InvalidArgumentException
     * @throws ORMException
     * @return PohodaEntityManager the created EntityManager
     */
    public static function create($conn, Configuration $config, EventManager $eventManager = null)
    {
        return new self(EntityManager::create($conn, $config, $eventManager));
    }

    /**
     * @return \DateTime
     */
    public function getCurrentDateTimeFromPohodaDatabase()
    {
        $rsm = new ResultSetMapping();
        $rsm->addScalarResult('datetime', 'datetime');
        $query = $this->createNativeQuery('SELECT GETDATE() as datetime', $rsm);
        $dateTimeStringWithMicroSeconds = $query->getSingleScalarResult();
        return new \DateTime($dateTimeStringWithMicroSeconds);
    }
}
}