<?php

namespace Shopsys\ShopBundle\Model\Transfer;

class TransferFacade
{
    /**
     * @return \Shopsys\ShopBundle\Model\Transfer\Transfer
     */
    public function getTransferByIdentifier()
    {
        return new Transfer();
    }

}