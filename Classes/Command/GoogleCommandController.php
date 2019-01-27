<?php
declare(strict_types=1);
namespace Onedrop\GoogleNotify\Command;

/**
 * This file is part of the Onedrop.GoogleNotify package
 *
 * (c) 2019 Onedrop <service@1drop.de>
 *
 *  All rights reserved
 */
use Neos\Flow\Annotations as Flow;
use Neos\Flow\Cli\CommandController;
use Neos\Flow\Mvc\Exception\InvalidArgumentValueException;
use Onedrop\GoogleNotify\Slot\AuthStorage;

class GoogleCommandController extends CommandController
{

    /**
     * @var AuthStorage
     * @Flow\Inject()
     */
    protected $authStorage;

    /**
     * Store authConfig.json downloaded from Google to be used
     * with the Indexing API.
     *
     * @param  string                        $filePathToAuthJson
     * @throws InvalidArgumentValueException
     * @throws \Neos\Cache\Exception
     */
    public function storeCredentialsCommand(string $filePathToAuthJson)
    {
        if (!file_exists($filePathToAuthJson)) {
            throw new InvalidArgumentValueException('Please provide a valid path to json file', 1543333508);
        }
        $authConfig = json_decode(file_get_contents($filePathToAuthJson), true);
        if (json_last_error() === JSON_ERROR_NONE) {
            $this->authStorage->storeAuthJson($authConfig);
        } else {
            throw new InvalidArgumentValueException('JSON not valid', 1543401092);
        }
    }
}
