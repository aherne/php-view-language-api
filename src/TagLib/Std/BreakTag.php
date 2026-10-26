<?php

namespace Lucinda\Templating\TagLib\Std;

use Lucinda\Templating\StartTag;
use Lucinda\Templating\SystemTag;

/**
 * Translates a loop-break tag into a PHP break statement
 *
 * The generated statement ends the nearest executing loop. PHP validates whether
 * the statement occurs in a legal loop context.
 *
 * Tag syntax: <:break/>
 *
 * @see \Lucinda\Templating\SystemTagParser
 */
final class BreakTag extends SystemTag implements StartTag
{
    /**
     * Translates a loop-break tag into a PHP break statement
     *
     * Attributes are unused by this standalone declaration.
     *
     * @param array<string,string> $parameters Parsed declaration attributes keyed by name
     * @return string PHP replacement for the opening or standalone declaration
     */
    public function parseStartTag(array $parameters=[]): string
    {
        return '<?php break; ?>';
    }
}
