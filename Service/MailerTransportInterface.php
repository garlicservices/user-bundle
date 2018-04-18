<?php

namespace Garlic\User\Service;

/**
 * Interface MailerTransportInterface
 */
interface MailerTransportInterface
{
    /**
     * Send template email
     *
     * @param string      $templateId
     * @param array       $vars
     * @param string      $toEmail
     * @param string|null $fromEmail
     * @param null        $locale
     *
     * @return \Psr\Http\Message\ResponseInterface
     */
    public function sendTemplate(
        string $templateId,
        array $vars,
        string $toEmail,
        string $fromEmail = null,
        $locale = null
    );
}
