<?php

namespace Adshares\CmsBundle\EventListener;

use Adshares\CmsBundle\Cms\Cms;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\RequestEvent;

class CmsListener implements EventSubscriberInterface
{
    public function __construct(private readonly Cms $cms)
    {
    }

    public function onKernelRequest(RequestEvent $event)
    {
        $request = $event->getRequest();
        if (!$request->hasPreviousSession()) {
            return;
        }
        if (null !== $request->get('_cms')) {
            $this->cms->setEditMode(true);
        }
    }

    public static function getSubscribedEvents(): array
    {
        return [RequestEvent::class => 'onKernelRequest'];
    }
}
