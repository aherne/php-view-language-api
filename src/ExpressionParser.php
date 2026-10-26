<?php

namespace Lucinda\Templating;

use Lucinda\Templating\History\SourceDocument;

/**
 * Translates output expressions in template text into PHP echo statements
 *
 * Dotted variables become array access and nested expressions may call PHP helpers.
 * The optional escaping policy wraps output in HTML escaping; it defaults to raw
 * output for text and console templates. Document parsing anchors generated PHP to
 * the expression's original location.
 *
 * This class remains extensible because TagExpressionParser overrides expression
 * translation for attributes instead of generating output statements.
 *
 * @see TagExpressionParser
 * @see SourceDocument
 */
class ExpressionParser
{
    /**
     * Whether generated echo statements HTML-escape their expression values
     */
    private bool $escapeOutput;

    /**
     * Sets whether output expressions are wrapped in HTML escaping
     *
     * @param bool $escapeOutput Whether generated echo statements call htmlspecialchars
     */
    public function __construct(bool $escapeOutput = false)
    {
        $this->escapeOutput = $escapeOutput;
    }

    /**
     * Translates output expressions in an untracked text string
     *
     * @param string $subject Template text containing zero or more ${...} expressions
     * @return string Text with expressions replaced by PHP output statements
     * @throws ViewException If the expression regex cannot be evaluated
     */
    public function parse(string $subject): string
    {
        return $this->parseDocument(new SourceDocument($subject))->getText();
    }

    /**
     * Translates expressions while retaining each expression's source location
     *
     * @param SourceDocument $subject Template text with original file and line information
     * @return SourceDocument Text containing generated PHP anchored to each expression
     * @throws ViewException If the expression regex cannot be evaluated
     */
    public function parseDocument(SourceDocument $subject): SourceDocument
    {
        return $subject->replace(
            '/\$\{((?:(?>[^{}]+?)|(?R))*?)\}/',
            fn(array $matches): string => $this->parseCallback($matches)
        );
    }

    /**
     * Converts one captured output expression into a PHP echo statement
     *
     * @param array<int,string> $matches Complete expression at index zero and its body at index one
     * @return string Generated PHP statement, optionally with HTML escaping
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
     * Wraps a PHP expression in the configured output operation
     *
     * @param string $expression PHP expression ready to evaluate
     * @return string PHP echo statement with raw or HTML-escaped output
     */
    private function wrapOutput(string $expression): string
    {
        if ($this->escapeOutput) {
            return '<?php echo htmlspecialchars((string) ('.$expression.'), ENT_QUOTES, "UTF-8"); ?>';
        }
        return '<?php echo '.$expression.'; ?>';
    }

    /**
     * Translates dotted and nested template-variable notation into PHP access syntax
     *
     * @param string $dottedVariable Template expression or expression fragment to convert
     * @return string PHP variable, indexed access or expression fragment
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
