<?php

namespace Lucinda\Templating;

/**
 * Reports invalid XML configuration for the template renderer
 *
 * Configuration failures are separate from template syntax and execution failures.
 * Wrapper throws this exception when the templating element or a mandatory setting
 * is missing.
 *
 * @see Wrapper
 * @see ViewException
 */
final class ConfigurationException extends \Exception
{
}
