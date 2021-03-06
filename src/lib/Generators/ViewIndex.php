<?php namespace Generators;

use Illuminate\Filesystem\Filesystem;
use Mustache_Engine;
use Parsers\FieldParser;
use Illuminate\Support\Str;

class ViewIndex extends Generator implements GeneratorInterface
{

    /**
     * Constructor to set member vars
     * 
     * @param Filesystem $filesystem
     * @param Mustache_Engine $mustache
     */
    public function __construct(Filesystem $filesystem, Mustache_Engine $mustache, FieldParser $fieldParser)
    {
        parent::__construct($filesystem, $mustache, $fieldParser);
    }

    /**
     * Function to get the minimum template variables
     * 
     * @return array
     */
    public function getTemplateVars()
    {
        $entity    = $this->getEntityName();
        $fieldData = $this->getFieldData();
        $instance  = Str::singular(Str::snake($entity));
        $cols      = array_keys($fieldData);
        $cells     = array_map(
            function ($x) use ($instance) {
                return "\${$instance}->{$x}";
            },
            $cols
        );

        return [
            'Entity'     => Str::studly($entity),
            'Entities'   => Str::plural(Str::studly($entity)),
            'collection' => Str::plural(Str::snake($entity)),
            'instance'   => $instance,
            'fields'     => $fieldData,
            'headings'   => $cols,
            'cells'      => $cells
        ];
    }
}
