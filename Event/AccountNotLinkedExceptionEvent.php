<?php

namespace Garlic\User\Event;

use Garlic\User\Exception\AccountNotLinkedException;
use Symfony\Component\EventDispatcher\Event;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Class AccountNotLinkedExceptionEvent
 */
class AccountNotLinkedExceptionEvent extends Event
{
    /**
     * @var AccountNotLinkedException
     */
    private $error;

    /**
     * @var Response
     */
    private $response;

    /**
     * @var null|string
     */
    protected $redirectUrl;

    /**
     * @var null|Request
     */
    protected $request;

    /**
     * UserEvent constructor.
     *
     * @param AccountNotLinkedException $error
     * @param string|null               $redirectUrl
     * @param Request|null              $request
     * @param Response|null             $response
     */
    public function __construct(
        AccountNotLinkedException $error,
        $redirectUrl = null,
        Request $request = null,
        Response $response = null
    ) {
        $this->error = $error;
        $this->redirectUrl = $redirectUrl;
        $this->request = $request;
        $this->response = $response;
    }

    /**
     * Get error
     *
     * @return AccountNotLinkedException
     */
    public function getError()
    {
        return $this->error;
    }

    /**
     * Get redirect url
     *
     * @return null|string
     */
    public function getRedirectUrl()
    {
        return $this->redirectUrl;
    }

    /**
     * Get Request
     *
     * @return Request
     */
    public function getRequest()
    {
        return $this->request;
    }

    /**
     * Get response
     *
     * @return Response
     */
    public function getResponse()
    {
        return $this->response;
    }

    /**
     * Sets a new response object.
     *
     * @param Response $response
     */
    public function setResponse(Response $response)
    {
        $this->response = $response;
    }
}
