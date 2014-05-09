<?php namespace Configuration;

use Illuminate\Filesystem\Filesystem;

/**
 * Interface that Blacksmith ConfigReaders must implement
 */
interface ConfigReaderInterface
{

    /**
     * Constructor function to setup our class variables
     * and load / parse the passed in config or load the default
     *
     * @param Filesystem $fs
     * @param string     $path
     */
    public function __construct(Filesystem $fs, $path = null);

    /**
     * Function to validate the currently loaded config
     *
     * @return bool
     */
    public function validateConfig();

    public function setDatabaseConfigReader(DatabaseConfig $databaseConfig);

    public function getDatabaseConfigReader();

    public function setFieldMapper(\Mapper $mapper);

    public function getFieldMapper();

    public function useFieldMapperDatabase($use);

    public function validateFieldMapperDatabase();

    public function validateFieldMapperModel($model);

    /**
     * Function to return an array of possible
     * generations for the loaded config
     *
     * @param string $config_type
     * @return array
     */
    public function getAvailableGenerators($config_type);

    /**
     * Function to return the loaded config type
     *
     * @return string
     */
    public function getConfigType();


    /**
     * Function to return the config keys
     * for an aggregate
     *
     * @param  string $key
     * @return array
     */
    public function getAggregateValues($key);


    /**
     * Function to return the available aggregate keys
     *
     * @param  string $key
     * @return array
     */
    public function getAvailableAggregates();

    /**
     * Function to get a configuration
     * key's values
     *
     * @param  string $key valid config key
     * @return array       config value for given key
     */
    public function getConfigValue($key);

    /**
     * Function to get the directory where the config
     * file is contained
     *
     * @return string
     */
    public function getConfigDirectory();

    public function getError();

}
