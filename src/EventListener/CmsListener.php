<?php

namespace Adshares\CmsBundle\EventListener;

use Adshares\CmsBundle\Cms\Cms;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

class CmsListener implements EventSubscriberInterface
{
    public function __construct(
        private readonly Cms $cms,
        private readonly RouterInterface $router,
        private readonly TranslatorInterface $translator,
        private readonly string $defaultLocale
    ) {
    }

    public function onKernelRequest(RequestEvent $event)
    {
        $request = $event->getRequest();
        if (!$request->hasPreviousSession()) {
            return;
        }

        if (null !== ($route = $request->get('_route'))) {
            $parameters = array_merge($request->query->all(), $request->attributes->get('_route_params'));
            if (!str_starts_with($route, 'i18n_') && $this->defaultLocale !== $this->translator->getLocale()) {
                $name = 'i18n_' . $route;
                if (null !== $this->router->getRouteCollection()->get($name)) {
                    $event->setResponse(
                        new RedirectResponse(
                            $this->router->generate($name, array_merge($request->query->all(), $parameters))
                        )
                    );
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
