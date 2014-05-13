<?php namespace Delegates;

use Console\OptionReader;
use Console\GenerateCommand;
use Configuration\ConfigReaderInterface;
use Configuration\ConfigReader;
use Factories\GeneratorFactory;
use Illuminate\Filesystem\Filesystem;

/**
 * Class for delegation of application operation related
 * to executing a generation request and seeing that it is 
 * completed properly
 */
class GeneratorDelegate implements GeneratorDelegateInterface
{

    /**
     * Command that delegated the request
     * 
     * @var \Console\GenerateCommand
     */
    protected $command;

    /**
     * Configuration of generation
     * 
     * @var \Configuration\ConfigReader
     */
    protected $config;

    /**
     * Generator to perform the code generation
     * 
     * @var \Generators\Generator
     */
    protected $generator;

    /**
     * String containing the requested generation action
     * 
     * @var string
     */
    protected $generation_request;

    /**
     * String containing the entity name the 
     * requested generation should use
     * 
     * @var string
     */
    protected $generate_for_entity;

    /**
     * Options passed in for generation
     * 
     * @var \Console\OptionReader
     */
    protected $optionReader;

    /**
     * Filesystem object for interacting
     * with the filesystem when needed
     * @var Filesystem
     */
    protected $filesystem;

    /** @var \Mapper */
    protected $mapper = null;


    /**
     * @inheritDoc
     */
    public function __construct(
        GenerateCommand $cmd,
        ConfigReaderInterface $cfg,
        GeneratorFactory $genFactory,
        Filesystem $filesystem,
        array $command_args,
        OptionReader $optionReader
    ) {
        $this->command             = $cmd;
        $this->config              = $cfg;
        $this->generator           = @$genFactory->make($command_args['what'], $optionReader);
        $this->filesystem          = $filesystem;
        $this->generate_for_entity = $command_args['entity'];
        $this->generation_request  = $command_args['what'];
        $this->optionReader        = $optionReader;
    }


    /**
     * Function to run the delegated operations and return boolean
     * status of result.  If false the command should comment out
     * the reasons for the failure.
     * 
     * @return bool success / failure of operations
     */
    public function run()
    {
        //check if the loaded config is valid
        if (! $this->config->validateConfig()) {
            $this->throwError(
                'Error',
                'The loaded configuration file is invalid',
                true
            );
            return false;
        }

        //validate options used
        $this->isOptionsValid();

        //check is the field mapper database is set and is valid
        if (! $this->isFieldMapperDatabaseValid()) {
            $this->throwError(
                'Error',
                $this->config->getError(),
                true
            );
            return false;
        }

        //check is the field mapper model is set and is valid
        if (! $this->isFieldMapperModelValid()) {
            $this->throwError(
                'Error',
                $this->config->getError(),
                true
            );
            return false;
        }

        //get possible generations
        $possible_generations = $this->config->getAvailableGenerators(
            $this->config->getConfigType()
        );

        //see if passed in command is one that is available
        if (! in_array($this->generation_request, $possible_generations)) {
            $this->throwError(
                'Error',
                "{$this->generation_request} is not a valid option",
                true
            );

            $this->throwError(
                'Error Details',
                "Please choose from: ". implode(", ", $possible_generations),
                true
            );
            return false;
        }

        //should be good to generate, get the config values
        $settings  = $this->config->getConfigValue($this->generation_request);

        $tplFile   = $settings[ConfigReader::CONFIG_VAL_TEMPLATE];
        $template  = implode(DIRECTORY_SEPARATOR, [$this->config->getConfigDirectory(), $tplFile]);
        $directory = $settings[ConfigReader::CONFIG_VAL_DIRECTORY];
        $filename  = $settings[ConfigReader::CONFIG_VAL_FILENAME];

        //run generator
        $success = $this->generator->make(
            $this->generate_for_entity,
            $template,
            $directory,
            $filename,
            $this->mapper
        );

        if ($success) {

            $this->throwError(
                'Blacksmith',
                'Success, I generated the code for you in '. $this->generator->getTemplateDestination()
            );
            return true;

        } else {

            $this->throwError(
                'Blacksmith',
                'An unknown error occured, nothing was generated',
                true
            );
            return false;
        }
    }

    /**
     * Calls the options validator
     *
     * @return bool
     */
    public function isOptionsValid()
    {
        if (! $this->optionReader->validateOptions()) {
            $this->throwError(
                'Error',
                $this->optionReader->getError(),
                true
            );
            return false;
        }

        return true;
    }

    public function isFieldMapperDatabaseValid()
    {
        if ($this->optionReader->useFieldMapperDatabase()) {
            $this->config->setUseFieldMapperDatabase(true);
            $mapper = $this->config->validateFieldMapperDatabase();

            if (! $mapper) {
                $this->throwError(
                    'Error',
                    $this->config->getError(),
                    true
                );
                return false;
            } else {
                $this->mapper = $mapper;
            }
        }

        return true;
    }

    public function isFieldMapperModelValid()
    {
        $model = $this->optionReader->useFieldMapperModel();
        if ($model) {
            $mapper = $this->config->validateFieldMapperModel($model);

            if (! $mapper) {
                $this->throwError(
                    'Error',
                    $this->config->getError(),
                    true
                );
                return false;
            } else {
                $this->mapper = $mapper;
            }
        }

        return true;
    }

    /**
     * Function to throw an error
     *
     * @param $error
     * @param $message
     */
    public function throwError($heading, $message, $error = false)
    {
        $this->command->comment(
            $heading,
            $message,
            $error
        );
    }
}
