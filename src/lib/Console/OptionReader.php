<?php

namespace Console;

use Factories\GeneratorDelegateFactory;

/**
 * Class for reading optional command line parameters
 */
class OptionReader
{

    /**
     * Command line parameters
     * @var ${DS}options array
     */
    protected $options;

    /**
     * @var $error string
     */
    protected $error;

    /**
     * @var resource
     */
    protected $model = null;

    function __construct(array $options)
    {
        $this->options = $options;
    }

    /**
     * Get the architecture to be generated
     * @return string
     */
    public function getArchitecture()
    {
        return array_key_exists('architecture', $this->options) ? $this->options['architecture'] : GeneratorDelegateFactory::ARCH_HEXAGONAL;
    }

    /**
     * Determine if generation should be forced
     */
    public function isGenerationForced()
    {
        foreach ($this->options as $option => $value) {
            if ($option == 'f' || $option == 'force') {
                return true;
            }
        }

        return false;
    }

    /**
     * Determine if the user wants to use the field mapper database for field mapping
     */
    public function useFieldMapperDatabase()
    {
        if (count($this->options) > 0) {
            foreach ($this->options as $option => $value) {
                if ($option == 'database') {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * Determine if the user wants to use the field mapper model for field mapping
     */
    public function useFieldMapperModel()
    {
        if (count($this->options) > 0) {
            foreach ($this->options as $option => $value) {
                if ($option == 'model') {
                    $this->model = $value;
                    return $value;
                }
            }
        }

        return false;
    }

    /**
     * @return resource
     */
    public function getModel()
    {
        return $this->model;
    }

    /**
     * Get the fields for the current generation
     * @return array
     */
    public function getFields()
    {
        return array_key_exists('fields', $this->options) ? $this->options['fields'] : [];
    }

    /**
     * Function to validate the options given
     */
    public function validateOptions()
    {
        if (!$this->validateFields()) {
            $this->error = "You cannot specify both --fields and (--database or --model). Please use one or the other.";
        }

        if ($this->error) {
            return false;
        }
        return true;
    }

    /**
     * Function to check if both --fields and --fm are set, and if they are, throw an error
     */
    public function validateFields()
    {
        if ($this->getFields()) {

            if ($this->useFieldMapperDatabase() && $this->useFieldMapperModel()) {

                $this->error = "You cannot specify --fields, --database, and --model. Please use just one.";

                return false;

            } elseif ($this->useFieldMapperModel()) {

                $this->error = "You cannot specify both --fields and --model. Please use one or the other.";

                return false;

            } elseif ($this->useFieldMapperDatabase()) {

                $this->error = "You cannot specify both --fields and --database. Please use one or the other.";

                return false;

            }

        }

        return true;
    }

    /**
     * Function to get an error thrown
     *
     * @return string/null
     */
    public function getError()
    {
        return $this->error;
    }

} 