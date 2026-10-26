<?php

namespace Lucinda\Templating\TagLib\Std;

use Lucinda\Templating\StartTag;
use Lucinda\Templating\SystemTag;

/**
 * Translates a loop-continue tag into a PHP continue statement
 *
 * The generated statement skips to the next loop iteration. PHP validates whether
 * the statement occurs in a legal loop context.
 *
 * Tag syntax: <:continue/>
 *
 * @see \Lucinda\Templating\SystemTagParser
 */
final class ContinueTag extends SystemTag implements StartTag
{
    /**
     * Translates a loop-continue tag into a PHP continue statement
     *
     * Attributes are unused by this standalone declaration.
     *
     * @param array<string,string> $parameters Parsed declaration attributes keyed by name
     * @return string PHP replacement for the opening or standalone declaration
     */
    public function parseStartTag(array $parameters=[]): string
    {
        return '<?php continue; ?>';
    }
}
