<?php
require __DIR__ . '/vendor/autoload.php';
try {
    $testClassName = ($argv[1] ?? null);
    new Lucinda\UnitTest\ConsoleController("unit-tests.xml", "local", $testClassName);
} catch (Exception $e) {
    echo $e->getMessage();
}
