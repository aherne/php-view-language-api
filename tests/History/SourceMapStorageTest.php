<?php

namespace Test\Lucinda\Templating\History;

use Lucinda\Templating\History\SourceMap;
use Lucinda\Templating\History\SourceMapStorage;
use Lucinda\UnitTest\Result;

/** Verifies persisted-map round trips and rejection of stale or malformed metadata. */
final class SourceMapStorageTest
{
    /** @return Result Whether saved maps retain all entries and require the expected configuration */
    public function save()
    {
        $file = tempnam(sys_get_temp_dir(), 'vl-map-storage-');
        $php = 'compiled text';
        $locations = [1=>['source'=>'source.tpl', 'line'=>4], 9=>['source'=>'tag.tpl', 'line'=>2]];
        $storage = new SourceMapStorage();
        try {
            file_put_contents($file, $php);
            $storage->save(new SourceMap($locations), $file, $php, 'settings');
            return new Result($storage->load($file, 'settings')?->getSourceLocations() === $locations
                && $storage->load($file, 'other settings') === null);
        } finally {
            unlink($file);
            @unlink($file.'.map.json');
        }
    }

    /** @return Result[] Checks for missing artifacts, stale PHP, invalid metadata and empty maps */
    public function load()
    {
        $file = tempnam(sys_get_temp_dir(), 'vl-map-storage-');
        $storage = new SourceMapStorage();
        $results = [];
        try {
            file_put_contents($file, 'compiled text');
            $results[] = new Result($storage->load($file) === null, 'missing map');
            $storage->save(new SourceMap([1=>['source'=>'source.tpl', 'line'=>4]]), $file, 'compiled text', 'settings');
            $validMetadata = json_decode(file_get_contents($file.'.map.json'), true);
            $cases = [
                'incompatible format'=>['version'=>-1],
                'mismatched PHP hash'=>['hash'=>'invalid'],
                'invalid mapping collection'=>['lines'=>'invalid'],
                'invalid mapping entry'=>['lines'=>[1=>'invalid']],
                'invalid compiled line'=>['lines'=>['bad'=>['source'=>'a.tpl', 'line'=>1]]],
                'zero compiled line'=>['lines'=>[0=>['source'=>'a.tpl', 'line'=>1]]],
                'negative compiled line'=>['lines'=>[-1=>['source'=>'a.tpl', 'line'=>1]]],
                'invalid source name'=>['lines'=>[1=>['source'=>false, 'line'=>1]]],
                'invalid source line'=>['lines'=>[1=>['source'=>'a.tpl', 'line'=>0]]]
            ];
            foreach ($cases as $name=>$changes) {
                file_put_contents($file.'.map.json', json_encode(array_replace($validMetadata, $changes)));
                $results[] = new Result($storage->load($file) === null, $name);
            }
            file_put_contents($file.'.map.json', '{invalid json');
            $results[] = new Result($storage->load($file) === null, 'malformed JSON');
            file_put_contents($file.'.map.json', json_encode($validMetadata));
            file_put_contents($file, 'changed PHP');
            $results[] = new Result($storage->load($file) === null, 'modified compilation');
            $storage->save(new SourceMap(), $file, 'changed PHP', 'settings');
            $results[] = new Result($storage->load($file)?->getSourceLocations() === [], 'empty map remains valid');
            unlink($file);
            $results[] = new Result($storage->load($file) === null, 'missing compilation');
            return $results;
        } finally {
            if (is_file($file)) unlink($file);
            @unlink($file.'.map.json');
        }
    }
}
