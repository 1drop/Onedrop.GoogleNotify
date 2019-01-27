<?php
namespace Onedrop\GoogleNotify\Slot;

/**
 * This file is part of the Onedrop.GoogleNotify package
 *
 * (c) 2019 Onedrop <service@1drop.de>
 *
 *  All rights reserved
 */
use Neos\Flow\Annotations as Flow;
use Neos\Flow\Log\PsrSystemLoggerInterface;

class ClientFactory
{
    /**
     * @Flow\InjectConfiguration(path="authentication.applicationName", package="Onedrop.GoogleNotify")
     * @var string
     */
    protected $applicationName;
    /**
     * @var PsrSystemLoggerInterface
     * @Flow\Inject()
     */
    protected $systemLogger;
    /**
     * @var AuthStorage
     * @Flow\Inject()
     */
    protected $authStorage;

    /**
     * @throws \Google_Exception
     * @return \Google_Client
     */
    public function create()
    {
        $client = new \Google_Client();
        if (empty($this->authStorage->getAuth())) {
            $this->systemLogger->warning('Missing Google credentials. Please use "./flow google:storecredentials auth.json"');
        } else {
            $client->setAuthConfig($this->authStorage->getAuth());
        }
        $client->setLogger($this->systemLogger);
        $client->setApplicationName($this->applicationName);
        $client->addScope(\Google_Service_Indexing::INDEXING);
        $client->setHttpClient(new \GuzzleHttp\Client());
        return $client;
    }
}
