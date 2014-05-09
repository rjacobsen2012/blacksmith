<?php namespace Configuration;

class DatabaseConfig
{

    protected $config;

    public function __construct($config)
    {
        $this->config = $config;
    }

    /**
     * Get database type from the config file
     */
    public function getDatabaseType()
    {
        if (isset($this->config['type'])) {
            return $this->config['type'];
        }

        return null;
    }

    /**
     * Get database host from the config file
     */
    public function getDatabaseHost()
    {
        if (isset($this->config['host'])) {
            return $this->config['host'];
        }

        return null;
    }

    /**
     * Get database user from the config file
     */
    public function getDatabaseUser()
    {
        if (isset($this->config['user'])) {
            return $this->config['user'];
        }

        return null;
    }

    /**
     * Get database password from the config file
     */
    public function getDatabasePassword()
    {
        if (isset($this->config['password'])) {
            return $this->config['password'];
        }

        return null;
    }

    /**
     * Get database name from the config file
     */
    public function getDatabaseName()
    {
        if (isset($this->config['database_name'])) {
            return $this->config['database_name'];
        }

        return null;
    }

    /**
     * Get database port from the config file
     */
    public function getDatabasePort()
    {
        if (isset($this->config['port'])) {
            return $this->config['port'];
        }

        return null;
    }

    /**
     * Get database socket from the config file
     */
    public function getDatabaseSocket()
    {
        if (isset($this->config['socket'])) {
            return $this->config['socket'];
        }

        return null;
    }

}