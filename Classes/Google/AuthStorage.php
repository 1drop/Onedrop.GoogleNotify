<?php
/***************************************************************
 *  Copyright notice
 *
 *  (c) 2018 Hans Hoechtl <hhoechtl@1drop.de>
 *  All rights reserved
 ***************************************************************/
namespace Onedrop\GoogleNotify\Slot;

/**
 * This file is part of the Onedrop.GoogleNotify package
 *
 * (c) 2019 Onedrop <service@1drop.de>
 *
 *  All rights reserved
 */
use Neos\Cache\Exception as CacheException;
use Neos\Cache\Frontend\VariableFrontend;
use Neos\Flow\Annotations as Flow;

/**
 * @Flow\Scope("singleton")
 */
class AuthStorage
{
    private const CREDENTIALS = 'SERVICE_CREDENTIALS';

    /**
     * @Flow\Inject
     * @var VariableFrontend
     */
    protected $cache;

    /**
     * @param  array          $authConfig
     * @throws CacheException
     */
    public function storeAuthJson(array $authConfig): void
    {
        $this->cache->set(self::CREDENTIALS, $authConfig);
    }

    /**
     * @return array
     */
    public function getAuth(): array
    {
        $authConfig = $this->cache->get(self::CREDENTIALS);
        if ($authConfig === false || !is_array($authConfig)) {
            return [];
        }
        return $authConfig;
    }

    /**
     * Remove existing tokens
     *
     * @return void
     */
    public function removeCredentials()
    {
        $this->cache->remove(self::CREDENTIALS);
    }
}
