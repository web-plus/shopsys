<?php

namespace Shopsys\ShopBundle\Model\Transfer;

class Transfer
{
    /**
     * @var string
     */
    protected $name;

    /**
     * @var boolean
     */
    protected $isEnabled;

    /**
     * @return bool
     */
    public function isEnabled()
    {
        return $this->isEnabled;
    }

    /**
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

}