<?php namespace Configuration;


use Mockery as m;

class DatabaseConfigTest extends \BlacksmithTest
{

    protected $config = [
        'type' => 'mysql',
        'host' => '123.45.678.9',
        'user' => 'joe',
        'password' => 'crab',
        'database_name' => 'some_db',
        'port' => '1234',
        'socket' => 'some_socket'
    ];

    public function testGetDatabaseType()
    {
        $databaseConfig = new DatabaseConfig($this->config);
        $this->assertEquals("mysql", $databaseConfig->getDatabaseType());
    }

    public function testGetDatabaseTypeNull()
    {
        $databaseConfig = new DatabaseConfig(null);
        $this->assertNull($databaseConfig->getDatabaseType());
    }

    public function testGetDatabaseHost()
    {
        $databaseConfig = new DatabaseConfig($this->config);
        $this->assertEquals("123.45.678.9", $databaseConfig->getDatabaseHost());
    }

    public function testGetDatabaseHostNull()
    {
        $databaseConfig = new DatabaseConfig(null);
        $this->assertNull($databaseConfig->getDatabaseHost());
    }

    public function testGetDatabaseUser()
    {
        $databaseConfig = new DatabaseConfig($this->config);
        $this->assertEquals("joe", $databaseConfig->getDatabaseUser());
    }

    public function testGetDatabaseUserNull()
    {
        $databaseConfig = new DatabaseConfig(null);
        $this->assertNull($databaseConfig->getDatabaseUser());
    }

    public function testGetDatabasePassword()
    {
        $databaseConfig = new DatabaseConfig($this->config);
        $this->assertEquals("crab", $databaseConfig->getDatabasePassword());
    }

    public function testGetDatabasePasswordNull()
    {
        $databaseConfig = new DatabaseConfig(null);
        $this->assertNull($databaseConfig->getDatabasePassword());
    }

    public function testGetDatabaseName()
    {
        $databaseConfig = new DatabaseConfig($this->config);
        $this->assertEquals("some_db", $databaseConfig->getDatabaseName());
    }

    public function testGetDatabaseNameNull()
    {
        $databaseConfig = new DatabaseConfig(null);
        $this->assertNull($databaseConfig->getDatabaseName());
    }

    public function testGetDatabasePort()
    {
        $databaseConfig = new DatabaseConfig($this->config);
        $this->assertEquals("1234", $databaseConfig->getDatabasePort());
    }

    public function testGetDatabasePortNull()
    {
        $databaseConfig = new DatabaseConfig(null);
        $this->assertNull($databaseConfig->getDatabasePort());
    }

    public function testGetDatabaseSocket()
    {
        $databaseConfig = new DatabaseConfig($this->config);
        $this->assertEquals("some_socket", $databaseConfig->getDatabaseSocket());
    }

    public function testGetDatabaseSocketNull()
    {
        $databaseConfig = new DatabaseConfig(null);
        $this->assertNull($databaseConfig->getDatabaseSocket());
    }

}
