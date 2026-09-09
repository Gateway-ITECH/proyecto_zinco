<?php

namespace Drupal\zinco_front\EventSubscriber;

use Drupal\Core\Session\AccountProxyInterface;
use Drupal\Core\Url;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\KernelEvents;
use Drupal\Core\Routing\RouteMatchInterface;

/**
 * Redirects anonymous users to the login page on access denied.
 */
class AccessDeniedSubscriber implements EventSubscriberInterface {

  /**
   * The current user account.
   *
   * @var \Drupal\Core\Session\AccountProxyInterface
   */
  protected $currentUser;

  /**
   * Constructs a new AccessDeniedSubscriber.
   *
   * @param \Drupal\Core\Session\AccountProxyInterface $current_user
   *   The current user account.
   */
  public function __construct(AccountProxyInterface $current_user) {
    $this->currentUser = $current_user;
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    $events[KernelEvents::EXCEPTION][] = ['onException', 10];
    return $events;
  }

  /**
   * Redirects to the login page if access is denied for anonymous users.
   *
   * @param \Symfony\Component\HttpKernel\Event\ExceptionEvent $event
   *   The exception event.
   */
  public function onException(ExceptionEvent $event) {
    $exception = $event->getThrowable();
    if ($exception instanceof AccessDeniedHttpException && $this->currentUser->isAnonymous()) {
      // Get the current path to use as destination.
      $query = \Drupal::destination()->getAsArray();
      $url = Url::fromRoute('user.login', [], ['query' => $query]);
      $response = new RedirectResponse($url->toString());
      $event->setResponse($response);
    }
  }

}
