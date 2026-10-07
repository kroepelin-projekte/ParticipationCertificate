<?php

use ILIAS\ResourceStorage\Stakeholder\AbstractResourceStakeholder;

class Stakeholder extends AbstractResourceStakeholder
{
    public function __construct()
    {
    }

    /**
     * @return string
     */
    public function getId(): string
    {
        return 'dhbwparticipationpdf';
    }

    /**
     * @return int
     */
    public function getOwnerOfNewResources(): int
    {
        return SYSTEM_USER_ID;
    }
}
