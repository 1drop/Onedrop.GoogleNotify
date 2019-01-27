<?php
/***************************************************************
 *  Copyright notice
 *
 *  (c) 2018 Hans Hoechtl <hhoechtl@1drop.de>
 *  All rights reserved
 ***************************************************************/
namespace Onedrop\GoogleNotify\Service;

/**
 * This file is part of the Onedrop.GoogleNotify package
 *
 * (c) 2019 Onedrop <service@1drop.de>
 *
 *  All rights reserved
 */
use Google_Service_Indexing_Resource_UrlNotifications as UrlNotifications;
use Neos\Flow\Annotations as Flow;
use Neos\Flow\Security\Exception\AuthenticationRequiredException;

/**
 * Class GoogleIndexing
 *
 * @Flow\Scope("singleton")
 */
class GoogleIndexing extends \Google_Service_Indexing
{
    /**
     * @return UrlNotifications
     */
    public function getUrlNotifications(): UrlNotifications
    {
        return $this->urlNotifications;
    }

    /**
     * @throws AuthenticationRequiredException
     * @return $this
     */
    public function requireAuthentication()
    {
        if (empty($this->getClient()->getAccessToken())) {
            throw new AuthenticationRequiredException('No access token', 1543330939);
        }
        return $this;
    }
}
