<?php

namespace Lucinda\Templating\History;

/**
 * Builds a source map from the final source-aware PHP document
 *
 * The operation walks text spans in emission order. Literal spans advance through
 * original source lines; generated spans stay anchored to their directive. All
 * intermediate state belongs to one build call and the input document is unchanged.
 * The builder does not access the filesystem or encode cache metadata.
 *
 * @see SourceDocument
 * @see SourceMap
 * @see ViewLanguageParser
 */
final class SourceMapBuilder
{
    /**
     * Builds line mappings after every PHP layout transformation has completed
     *
     * @param SourceDocument $document Final generated PHP and its original source spans
     * @return SourceMap Original template locations indexed by generated PHP line
     */
    public function build(SourceDocument $document): SourceMap
    {
        $sourceLocationsByCompiledLine = [];
        $generatedLine = 1;
        foreach ($document->getSpans() as $span) {
            $sourceLine = $span["line"];
            $parts = explode("\n", $span["text"]);
            foreach ($parts as $index=>$part) {
                if ($index > 0) {
                    ++$generatedLine;
                    if ($span["literal"]) {
                        ++$sourceLine;
                    }
                }
                if ($span["source"] !== "") {
                    // Meaningful text takes precedence over an earlier whitespace span.
                    if (!isset($sourceLocationsByCompiledLine[$generatedLine]) || trim($part) !== "") {
                        $sourceLocationsByCompiledLine[$generatedLine] = ["source"=>$span["source"], "line"=>$sourceLine];
                    }
                }
            }
        }
        return new SourceMap($sourceLocationsByCompiledLine);
    }
}
