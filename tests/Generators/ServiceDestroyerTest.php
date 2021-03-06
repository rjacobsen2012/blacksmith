<?php namespace Generators;

use Illuminate\Filesystem\Filesystem;
use Parsers\FieldParser;
use Mustache_Engine;

class ServiceDestroyerTest extends \BlacksmithTest
{

    public function testParentClass()
    {
        $instance = new ServiceDestroyer(
            new Filesystem,
            new Mustache_Engine,
            new FieldParser
        );
        $this->assertInstanceOf("Generators\Generator", $instance);
    }
}
