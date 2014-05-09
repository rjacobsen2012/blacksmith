<?php namespace Delegates;

use Console\OptionReader;
use Console\GenerateCommand;
use Configuration\ConfigReaderInterface;
use Factories\GeneratorFactory;
use Illuminate\Filesystem\Filesystem;

interface GeneratorDelegateInterface
{

    public function run();

    /**
     * Calls the options validator
     *
     * @return bool
     */
    public function isOptionsValid();

    public function isFieldMapperDatabaseValid();

    public function isFieldMapperModelValid();

}
