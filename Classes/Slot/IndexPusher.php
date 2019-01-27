<?php
namespace Onedrop\GoogleNotify\Slot;

/**
 * This file is part of the Onedrop.GoogleNotify package
 *
 * (c) 2019 Onedrop <service@1drop.de>
 *
 *  All rights reserved
 */
use Google_Exception as GoogleException;
use Google_Service_Indexing_UrlNotification as UrlNotification;
use Neos\ContentRepository\Domain\Model\NodeInterface;
use Neos\Flow\Annotations as Flow;
use Neos\Flow\Http\Request;
use Neos\Flow\Http\Response;
use Neos\Flow\Http\Uri;
use Neos\Flow\Log\PsrSystemLoggerInterface;
use Neos\Flow\Mvc\ActionRequest;
use Neos\Flow\Mvc\Controller\Arguments;
use Neos\Flow\Mvc\Controller\ControllerContext;
use Neos\Flow\Mvc\Routing\UriBuilder;
use Neos\Neos\Domain\Model\Domain;
use Neos\Neos\Domain\Model\Site;
use Neos\Neos\Domain\Repository\SiteRepository;
use Neos\Neos\Service\LinkingService;
use Onedrop\GoogleNotify\Service\GoogleIndexing;

/**
 * Class GoogleIndexing
 * @Flow\Scope("singleton")
 */
class IndexPusher
{
    const NOTIFICATION_TYPE_UPDATE = 'URL_UPDATED';
    const NOTIFICATION_TYPE_DELETE = 'URL_DELETED';

    /**
     * @var SiteRepository
     * @Flow\Inject()
     */
    protected $siteRepository;
    /**
     * @var string
     * @Flow\InjectConfiguration(package="Neos.Flow", path="http.baseUri")
     */
    protected $systemBaseUri;
    /**
     * @var string
     * @Flow\InjectConfiguration(package="Onedrop.GoogleNotify", path="baseUri")
     */
    protected $customBaseUri;
    /**
     * @var array
     * @Flow\InjectConfiguration(package="Onedrop.GoogleNotify", path="nodeTypes")
     */
    protected $indexingNodeTypes = [];
    /**
     * @Flow\Inject
     * @var PsrSystemLoggerInterface
     */
    protected $systemLogger;
    /**
     * @var UriBuilder
     * @Flow\Inject()
     */
    protected $uriBuilder;
    /**
     * @var LinkingService
     * @Flow\Inject()
     */
    protected $linkingService;
    /**
     * @var GoogleIndexing
     * @Flow\Inject()
     */
    protected $googleIndexingService;

    /**
     * Determine base uri for node.
     * First try to get domain if is set on site.
     * Second check for custom configuration Onedrop.GoogleNotify.baseUri
     * Third check for system wide configuration Neos.Flow.http.baseUri
     *
     * @param  NodeInterface $node
     * @return string
     */
    protected function determineBaseUri(NodeInterface $node): string
    {
        $siteNode = $node->getContext()->getRootNode();
        /** @var Site $site */
        /** @noinspection PhpUndefinedMethodInspection */
        $site = $this->siteRepository->findOneByNodeName($siteNode->getName());

        $baseUri = '';
        $primaryDomain = $site->getPrimaryDomain();
        if ($primaryDomain instanceof Domain) {
            $baseUri = $primaryDomain->getScheme() . '://' . $primaryDomain->getHostname() . '/';
        } elseif (!empty($this->customBaseUri)) {
            $baseUri = $this->customBaseUri;
        } elseif (!empty($this->systemBaseUri)) {
            $baseUri = $this->systemBaseUri;
        } else {
            $this->systemLogger->log('Please set Onedrop.GoogleNotify.baseUri', LOG_WARNING);
        }
        return $baseUri;
    }

    /**
     * Generate a fake controller context which is needed to create node uris.
     *
     * @param  NodeInterface     $node
     * @return ControllerContext
     */
    protected function simulateControllerContext(NodeInterface $node): string
    {
        $baseUri = $this->determineBaseUri($node);
        if (!getenv('FLOW_REWRITEURLS')) {
            putenv('FLOW_REWRITEURLS=1');
        }
        $httpRequest = Request::create(new Uri($baseUri));
        $request = new ActionRequest($httpRequest);
        $this->uriBuilder->setRequest($request);
        $this->uriBuilder->setCreateAbsoluteUri(true);
        return new ControllerContext(
            $this->uriBuilder->getRequest(),
            new Response(),
            new Arguments([]),
            $this->uriBuilder
        );
    }

    /**
     * Generate frontend URI for a given node
     *
     * @param  NodeInterface $node
     * @return bool|string
     */
    protected function generateAbsolutePublicNodeUri(NodeInterface $node)
    {
        try {
            return $this->linkingService->createNodeUri($this->simulateControllerContext($node), $node, null, null, true);
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Send notification API request to Google
     *
     * @param NodeInterface $node
     * @param string        $notificationType
     */
    protected function sendNotificationWithType(NodeInterface $node, string $notificationType): void
    {
        if (!in_array($node->getNodeType()->getName(), $this->indexingNodeTypes)) {
            return;
        }
        if ($node->getContext()->getWorkspaceName() !== 'live') {
            return;
        }
        $absoluteNodeUri = $this->generateAbsolutePublicNodeUri($node);
        $this->systemLogger->info('Sending ' . $notificationType . ' notification to Google for Node ' . $node->getPath() . ' with url ' . $absoluteNodeUri);
        if (!$absoluteNodeUri) {
            $this->systemLogger->error('Could not create URI for node ' . $node->getIdentifier() . '. Not updating google indexing');
            return;
        }
        $urlNotification = new UrlNotification();
        $urlNotification->setType($notificationType);
        $urlNotification->setUrl($absoluteNodeUri);
        try {
            $this->googleIndexingService->getUrlNotifications()->publish($urlNotification);
        } catch (GoogleException $e) {
            $this->systemLogger->error($e->getMessage());
        }
    }

    /**
     * Executed if a node is updated in the NeosCR
     *
     * @param NodeInterface $node
     */
    public function sendNodeUpdated(NodeInterface $node): void
    {
        $this->sendNotificationWithType($node, self::NOTIFICATION_TYPE_UPDATE);
    }

    /**
     * Executed if a node is removed from the NeosCR
     *
     * @param NodeInterface $node
     */
    public function sendNodeRemoved(NodeInterface $node): void
    {
        $this->sendNotificationWithType($node, self::NOTIFICATION_TYPE_DELETE);
    }
}
