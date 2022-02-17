<?php

namespace TwinElements\Component\LocaleSubscriber;

use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class LocaleSubscriber implements EventSubscriberInterface
{
    /**
     * @var string $defaultLocale
     */
    private string $defaultLocale;
    /**
     * @var array $locales
     */
    private array $locales;

    /**
     * @param string $defaultLocale
     * @param string $locales
     */
    public function __construct(string $defaultLocale, string $locales)
    {
        $this->defaultLocale = $defaultLocale;
        $this->locales = explode('|', $locales);
    }

    /**
     * @param RequestEvent $event
     */
    public function onKernelRequest(RequestEvent $event)
    {
        if (!$event->isMainRequest()) {
            return;
        }

        $request = $event->getRequest();
        if (!$request->attributes->getBoolean('is_front', false)) {
            if (!$request->hasPreviousSession()) {
                return;
            }

            if ($request->getSession()->has('_locale')) {
                $sessionLocale = $request->getSession()->get('_locale');
                $request->attributes->set('_locale', $sessionLocale);
            } else {
                $request->getSession()->set('_locale', $this->defaultLocale);
                $request->setLocale($this->defaultLocale);
            }

            return;
        }

        $request->setLocale($request->attributes->get('_locale'));
    }

    /**
     * @param ExceptionEvent $event
     */
    public function onKernelException(ExceptionEvent $event)
    {
        $request = $event->getRequest();
        $pathinfoArray = explode('/', substr($request->getPathInfo(), 1));
        if (in_array($pathinfoArray[0], $this->locales)) {
            $request->setLocale($pathinfoArray[0]);
            $request->attributes->set('_locale', $pathinfoArray[0]);
        } else {
            $request->setLocale($this->defaultLocale);
            $request->attributes->set('_locale', $this->defaultLocale);
        }
    }

    public static function getSubscribedEvents(): array
    {
        return array(
            // must be registered before (i.e. with a higher priority than) the default Locale listener
            KernelEvents::REQUEST => array(array('onKernelRequest', 20)),
            KernelEvents::EXCEPTION => array(array('onKernelException', 0))
        );
    }
}
