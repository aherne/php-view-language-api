<?php

namespace Test\Lucinda\Templating\History;

use Lucinda\Templating\History\SourceMap;
use Lucinda\UnitTest\Result;

/** Verifies source lookup and immutable access to source-map entries. */
final class SourceMapTest
{
    /** @return Result Whether mapped, unmapped and independent source locations are returned correctly */
    public function getSourceLocation()
    {
        $locations = [1=>['source'=>'a.tpl', 'line'=>4], 8=>['source'=>'b.tpl', 'line'=>12]];
        $map = new SourceMap($locations);
        $locations[1]['line'] = 99;
        return new Result($map->getSourceLocation(1) === ['source'=>'a.tpl', 'line'=>4]
            && $map->getSourceLocation(8) === ['source'=>'b.tpl', 'line'=>12]
            && $map->getSourceLocation(3) === null);
    }

    /** @return Result Whether accessing the full correspondence set preserves the held map */
    public function getSourceLocations()
    {
        $locations = [3=>['source'=>'source.tpl', 'line'=>7]];
        $map = new SourceMap($locations);
        $exported = $map->getSourceLocations();
        $exported[3]['line'] = 99;
        return new Result($map->getSourceLocations() === $locations && (new SourceMap())->getSourceLocations() === []);
    }
}
