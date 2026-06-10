<?php

namespace Lucinda\Templating;

/**
 * Implements scalar expressions that are going to be interpreted as PHP when response is displayed to client.
 *
 * Example of expression:
 *         ${request.client.ip}
 * This will be converted to:
 *         <?php echo $request["client"]["ip"]; ?>
 */
class ExpressionParser
{
    private bool $escapeOutput;

    /**
     * @param bool $escapeOutput Whether parsed expressions should be HTML-escaped.
     */
    public function __construct(bool $escapeOutput = false)
    {
        $this->escapeOutput = $escapeOutput;
    }

    /**
     * Looks for variable expressions in SUBJECT and returns answer where expressions are converted to PHP.
     *
     * @param  string $subject
     * @return string
     */
    public function parse(string $subject): string
    {
        if (!str_contains($subject, '${')) {
            return $subject;
        }
        return preg_replace_callback(
            "/[\$]\{((?:(?>[^{}]+?)|(?R))*?)\}/",
            array($this,"parseCallback"),
            $subject
        );
    }

    /**
     * For each macro-expression found, calls for its conversion to PHP and wraps it up as scriptlet.
     *
     * @param  array<int,string> $matches
     * @return string
     */
    protected function parseCallback(array $matches): string
    {
        $position = strpos($matches[1], "(");
        if ($position!==false) {
            $variable = $this->convertToVariable(substr($matches[1], $position));
            return $this->wrapOutput(substr($matches[1], 0, $position).$variable);
        } else {
            $variable = $this->convertToVariable($matches[0]);
            return $this->wrapOutput($variable);
        }
    }

    /**
     * Wraps parsed expression into a PHP echo scriptlet.
     *
     * @param string $expression PHP expression.
     * @return string
     */
    private function wrapOutput(string $expression): string
    {
        if ($this->escapeOutput) {
            return '<?php echo htmlspecialchars((string) ('.$expression.'), ENT_QUOTES, "UTF-8"); ?>';
        }
        return '<?php echo '.$expression.'; ?>';
    }

    /**
     * Performs conversion of expression to PHP.
     *
     * @param  string $dottedVariable
     * @return string
     */
    protected function convertToVariable(string $dottedVariable): string
    {
        if (!str_contains($dottedVariable, ".")) {
            return str_replace(array("{","}"), "", $dottedVariable);
        } else {
            return preg_replace(
                ['/\${(\w+)(\.)?/','/\}/','/\./','/\[(\w+)\]/','/\[\]/'],
                ['$$1[',']','][','["$1"]',''],
                $dottedVariable
            );
        }
    }
}
