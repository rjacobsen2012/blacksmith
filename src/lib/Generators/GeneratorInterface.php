<?php namespace Generators;

use Console\OptionReader;
use Illuminate\Filesystem\Filesystem;
use Parsers\FieldParser;
use Mustache_Engine;

interface GeneratorInterface
{
    public function make($entity, $sourceTemplate, $destinationDir, $fileName = null, \Mapper $mapper = null);

    /**
     * Function to return the filesystem location
     * of the parsed template's final destination
     *
     * @return string
     */
    public function getTemplateDestination();
}
