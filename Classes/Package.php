<?php
declare(strict_types=1);
namespace Onedrop\GoogleNotify;

/**
 * This file is part of the Onedrop.GoogleNotify package
 *
 * (c) 2019 Onedrop <service@1drop.de>
 *
 *  All rights reserved
 */

use Neos\ContentRepository\Domain\Model\Node;
use Neos\Flow\Core\Bootstrap;
use Onedrop\GoogleNotify\Slot\IndexPusher;

/**
 * Class Package
 */
class Package extends \Neos\Flow\Package\Package
{
    /**
     * @param Bootstrap $bootstrap
     */
    public function boot(Bootstrap $bootstrap)
    {
        $dispatcher = $bootstrap->getSignalSlotDispatcher();
        $dispatcher->connect(
            Node::class,
            'nodeAdded',
            IndexPusher::class,
            'sendNodeUpdated'
        );
        $dispatcher->connect(
            Node::class,
            'nodeUpdated',
            IndexPusher::class,
            'sendNodeUpdated'
        );
        $dispatcher->connect(
            Node::class,
            'nodeRemoved',
            IndexPusher::class,
            'sendNodeRemoved'
        );
    }
}
