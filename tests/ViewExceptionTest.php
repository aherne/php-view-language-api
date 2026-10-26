<?php

namespace Test\Lucinda\Templating;

use Lucinda\Templating\ViewException;
use Lucinda\UnitTest\Result;

final class ViewExceptionTest
{
    public function getSourceLocation()
    {
        $cause = new \RuntimeException('cause');
        $location = ['source'=>'a.tpl', 'line'=>3];
        $e = new ViewException('bad macro', 1, $cause, $location);
        return new Result($e->getSourceLocation() === $location && $e->getPrevious() === $cause
            && $e->getMessage() === 'bad macro @ a.tpl:3' && (new ViewException('plain'))->getMessage() === 'plain');
    }
}
