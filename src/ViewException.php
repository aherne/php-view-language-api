<?php

namespace Lucinda\Templating;

/**
 * Reports a template compilation or rendering failure with optional source context
 *
 * The original file and one-based line remain available as structured data and are
 * also appended to the exception message. Location keys are source (file path string)
 * and line (one-based integer). A previous throwable preserves the PHP
 * error or parser failure that caused the diagnostic.
 *
 * @see Wrapper
 * @see SourceDocument
 */
final class ViewException extends \Exception
{
    /**
     * @var array<string,string|int>|null Original template location when known
     */
    private ?array $sourceLocation;

    /**
     * Creates a template diagnostic with an optional original source location
     *
     * @param string $message Explanation of the compilation or rendering failure
     * @param int $code Exception code retained for the caller
     * @param \Throwable|null $previous Original parser or PHP failure
     * @param array<string,string|int>|null $sourceLocation Original file and one-based line to include
     */
    public function __construct(string $message = "", int $code = 0, ?\Throwable $previous = null, ?array $sourceLocation = null)
    {
        $this->sourceLocation = $sourceLocation;
        if ($sourceLocation !== null) {
            $message .= " @ ".$sourceLocation["source"].":".$sourceLocation["line"];
        }
        parent::__construct($message, $code, $previous);
    }

    /**
     * Gets the original location associated with the template failure
     *
     * @return array<string,string|int>|null Original template file and line, or null when unavailable
     */
    public function getSourceLocation(): ?array
    {
        return $this->sourceLocation;
    }
}
