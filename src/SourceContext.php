<?php

namespace Lucinda\Templating;

/**
 * Locates original template context from VL comments in compiled PHP files.
 */
class SourceContext
{
    /**
     * @param string $compiledFile
     * @param int $compiledLine
     * @return array{source:string,line:int}|null
     */
    public static function fromCompiledLine(string $compiledFile, int $compiledLine): ?array
    {
        if (!is_file($compiledFile)) {
            return null;
        }

        $lines = file($compiledFile);
        if ($lines===false) {
            return null;
        }

        $stack = [];
        $limit = min($compiledLine, count($lines));
        for ($i=0; $i<$limit; $i++) {
            if (preg_match("/<!-- VL:START:\s*(.*?)\s*-->/", $lines[$i], $matches)) {
                $stack[] = ["source"=>$matches[1], "compiled_start"=>$i+1];
            } elseif (preg_match("/<!-- VL:END:\s*(.*?)\s*-->/", $lines[$i])) {
                array_pop($stack);
            }
        }

        if (empty($stack)) {
            return null;
        }

        $context = $stack[count($stack)-1];
        return [
            "source"=>$context["source"],
            "line"=>max(1, $compiledLine-$context["compiled_start"])
        ];
    }
}
