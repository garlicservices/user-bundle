<?php

namespace Garlic\User\Exception;

/**
 * Class ErrorHandler
 */
class ErrorHandler extends \Exception
{
    protected $severity;

    /**
     * ErrorHandler constructor.
     *
     * @param string $message
     * @param int    $code
     * @param string $severity
     * @param string $filename
     * @param string $lineno
     */
    public function __construct($message, $code, $severity, $filename, $lineno)
    {
        $this->message = $message;
        $this->code = $code;
        $this->severity = $severity;
        $this->file = $filename;
        $this->line = $lineno;
    }

    /**
     * Get Severity
     *
     * @return string
     */
    public function getSeverity()
    {
        return $this->severity;
    }
}
