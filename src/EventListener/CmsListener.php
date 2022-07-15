<?php

namespace Adshares\CmsBundle\EventListener;

use Adshares\CmsBundle\Cms\Cms;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\Routing\RouterInterface;

class CmsListener implements EventSubscriberInterface
{
    public function __construct(
        private readonly Cms $cms,
        private readonly RouterInterface $router
    ) {
    }

    public function onKernelRequest(RequestEvent $event)
    {
        $request = $event->getRequest();
        if (!$request->hasPreviousSession()) {
            return;
        }

        if (null !== ($route = $request->get('_route'))) {
            $parameters = $request->attributes->get('_route_params');
            if (!str_starts_with($route, 'i18n_')) {
                $name = 'i18n_' . $route;
                if (null !== $this->router->getRouteCollection()->get($name)) {
                    $event->setResponse(new RedirectResponse($this->router->generate($name, $parameters)));
                    return;
                }
            }
            unset($parameters['_cms']);
            $this->cms->setRoute($route, $parameters);
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
