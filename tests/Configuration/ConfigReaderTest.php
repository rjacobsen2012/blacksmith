<?php namespace Configuration;


use Configuration\ConfigReader;
use Illuminate\Filesystem\Filesystem;
use Mockery as m;

class ConfigReaderTest extends \BlacksmithTest
{
    /**
     * @expectedException \Illuminate\Filesystem\FileNotFoundException
     */
    public function testErrorMissingConfig()
    {
        $path = '/path/to/config.json';
        $fs = m::mock('Illuminate\Filesystem\Filesystem');
        $fs->shouldReceive('exists')->once()->with($path)->andReturn(false);

        $reader = new ConfigReader($fs, $path);
    }



    public function testReadGivenConfig()
    {
        $path = '/path/to/config.json';
        $fs = m::mock('Illuminate\Filesystem\Filesystem');
        $fs->shouldReceive('exists')->once()->with($path)->andReturn(true);
        $fs->shouldReceive('get')->once()->with($path);

        $reader = new ConfigReader($fs, $path);
    }



    public function testReadDefaultConfig()
    {
        $path = __DIR__.'/../../src/lib/Generators/templates/hexagonal/config.json';
        $fs = m::mock('Illuminate\Filesystem\Filesystem');
        $fs->shouldReceive('get')->once()->withAnyArgs();

        $reader = new ConfigReader($fs);

        $dirExp = explode(DIRECTORY_SEPARATOR, $reader->getConfigDirectory());
        $this->assertEquals(
            'hexagonal',
            $dirExp[count($dirExp)-1]
        );
    }



    public function testValidConfig()
    {
        $path = realpath(__DIR__.'/../../src/lib/Generators/templates/hexagonal/config.json');

        $json = file_get_contents($path);

        $configArr = json_decode($json, true);

        $fs = m::mock('Illuminate\Filesystem\Filesystem');
        $fs->shouldReceive('get')->once()->withAnyArgs()
            ->andReturn($json);

        $reader = new ConfigReader($fs);

        $this->assertTrue($reader->validateConfig());
    }

    public function testSkippedConfigKey()
    {
        $path = realpath(__DIR__.'/../../src/lib/Generators/templates/hexagonal/config.json');

        $json = file_get_contents($path);

        //replace existing config value with false
        $json = preg_replace('/("view_show.*\{.*(\n.*)+?\n.+\},)/', '"view_show": false,', $json);

        $configArr = json_decode($json, true);

        $fs = m::mock('Illuminate\Filesystem\Filesystem');
        $fs->shouldReceive('get')->once()->withAnyArgs()
            ->andReturn($json);

        $reader = new ConfigReader($fs);

        $this->assertTrue($reader->validateConfig());
    }



    public function testGetAvailableGenerators()
    {
        $path = realpath(__DIR__.'/../../src/lib/Generators/templates/hexagonal/config.json');

        $json = file_get_contents($path);

        $fs = m::mock('Illuminate\Filesystem\Filesystem');
        $fs->shouldReceive('get')->once()->withAnyArgs()
            ->andReturn($json);

        $reader = new ConfigReader($fs);

        $this->assertTrue(
            is_array(
                $reader->getAvailableGenerators(
                    ConfigReader::CONFIG_TYPE_HEXAGONAL
                )
            )
        );

        $this->assertEquals(
            ConfigReader::CONFIG_TYPE_HEXAGONAL,
            $reader->getConfigType()
        );

        $this->assertFalse(
            $reader->getAvailableGenerators('foo')
        );
    }



    public function testInvalidConfigWithMissingConfigType()
    {
        $path = realpath(__DIR__.'/../../src/lib/Generators/templates/hexagonal/config.json');

        $json = file_get_contents($path);

        $configArr = json_decode($json, true);

        $missing = $configArr;
        unset($missing[ConfigReader::CONFIG_TYPE_KEY]);
        $missingJson = json_encode($missing, JSON_UNESCAPED_SLASHES);

        $fs = m::mock('Illuminate\Filesystem\Filesystem');
        $fs->shouldReceive('get')->once()->withAnyArgs()
            ->andReturn($missingJson);

        $reader = new ConfigReader($fs);
        $this->assertFalse($reader->validateConfig());
    }



    public function testInvalidConfigWithMissingRequiredKey()
    {
        $path = realpath(__DIR__.'/../../src/lib/Generators/templates/hexagonal/config.json');

        $json = file_get_contents($path);

        $configArr = json_decode($json, true);

        $missing = $configArr;
        unset($missing[ConfigReader::CONFIG_KEY_MODEL]);
        $missingJson = json_encode($missing, JSON_UNESCAPED_SLASHES);

        $fs = m::mock('Illuminate\Filesystem\Filesystem');
        $fs->shouldReceive('get')->once()->withAnyArgs()
            ->andReturn($missingJson);

        $reader = new ConfigReader($fs);
        $this->assertFalse($reader->validateConfig());
    }



    public function testInvalidConfigWithMissingRequiredTemplateSubKey()
    {
        $path = realpath(__DIR__.'/../../src/lib/Generators/templates/hexagonal/config.json');

        $json = file_get_contents($path);

        $configArr = json_decode($json, true);

        $missing = $configArr;
        unset($missing[ConfigReader::CONFIG_KEY_MODEL][ConfigReader::CONFIG_VAL_TEMPLATE]);
        $missingJson = json_encode($missing, JSON_UNESCAPED_SLASHES);

        $fs = m::mock('Illuminate\Filesystem\Filesystem');
        $fs->shouldReceive('get')->once()->withAnyArgs()
            ->andReturn($missingJson);

        $reader = new ConfigReader($fs);
        $this->assertFalse($reader->validateConfig());
    }



    public function testInvalidConfigWithMissingRequiredDirectorySubKey()
    {
        $path = realpath(__DIR__.'/../../src/lib/Generators/templates/hexagonal/config.json');

        $json = file_get_contents($path);

        $configArr = json_decode($json, true);

        $missing = $configArr;
        unset($missing[ConfigReader::CONFIG_KEY_MODEL][ConfigReader::CONFIG_VAL_DIRECTORY]);
        $missingJson = json_encode($missing, JSON_UNESCAPED_SLASHES);

        $fs = m::mock('Illuminate\Filesystem\Filesystem');
        $fs->shouldReceive('get')->once()->withAnyArgs()
            ->andReturn($missingJson);

        $reader = new ConfigReader($fs);
        $this->assertFalse($reader->validateConfig());
    }



    public function testInvalidConfigWithMissingRequiredFilenameSubKey()
    {
        $path = realpath(__DIR__.'/../../src/lib/Generators/templates/hexagonal/config.json');

        $json = file_get_contents($path);

        $configArr = json_decode($json, true);

        $missing = $configArr;
        unset($missing[ConfigReader::CONFIG_KEY_MODEL][ConfigReader::CONFIG_VAL_FILENAME]);
        $missingJson = json_encode($missing, JSON_UNESCAPED_SLASHES);

        $fs = m::mock('Illuminate\Filesystem\Filesystem');
        $fs->shouldReceive('get')->once()->withAnyArgs()
            ->andReturn($missingJson);

        $reader = new ConfigReader($fs);
        $this->assertFalse($reader->validateConfig());
    }


    public function testGetConfigValueShouldPass()
    {
        $path = realpath(__DIR__.'/../../src/lib/Generators/templates/hexagonal/config.json');

        $json = file_get_contents($path);

        $fs = m::mock('Illuminate\Filesystem\Filesystem');
        $fs->shouldReceive('get')->once()->withAnyArgs()
            ->andReturn($json);

        $reader = new ConfigReader($fs);
        $result = $reader->getConfigValue(ConfigReader::CONFIG_KEY_MODEL);
        
        $this->assertTrue(is_array($result));
    }


    public function testGetConfigValueShouldFail()
    {
        $path = realpath(__DIR__.'/../../src/lib/Generators/templates/hexagonal/config.json');

        $json = file_get_contents($path);

        $fs = m::mock('Illuminate\Filesystem\Filesystem');
        $fs->shouldReceive('get')->once()->withAnyArgs()
            ->andReturn($json);

        $reader = new ConfigReader($fs);
        $result = $reader->getConfigValue('something-invalid');
        $this->assertFalse($result);
    }


    public function testGetAggregateValues()
    {
        $path = realpath(__DIR__.'/../../src/lib/Generators/templates/hexagonal/config.json');

        $json = file_get_contents($path);

        $fs = m::mock('Illuminate\Filesystem\Filesystem');
        $fs->shouldReceive('get')->once()->withAnyArgs()
            ->andReturn($json);

        $reader = new ConfigReader($fs);
        $result = $reader->getAggregateValues(ConfigReader::CONFIG_AGG_KEY_SCAFFOLD);
        
        $this->assertTrue(is_array($result));
    }


    public function testGetAvailableAggregates()
    {
        $path = realpath(__DIR__.'/../../src/lib/Generators/templates/hexagonal/config.json');

        $json = file_get_contents($path);

        $fs = m::mock('Illuminate\Filesystem\Filesystem');
        $fs->shouldReceive('get')->once()->withAnyArgs()
            ->andReturn($json);

        $reader = new ConfigReader($fs);
        $result = $reader->getAvailableAggregates();
        
        $this->assertEquals(['scaffold'], $result);
    }



    public function testGetAggregateShoulFail()
    {
        $path = realpath(__DIR__.'/../../src/lib/Generators/templates/hexagonal/config.json');

        $json = file_get_contents($path);

        $fs = m::mock('Illuminate\Filesystem\Filesystem');
        $fs->shouldReceive('get')->once()->withAnyArgs()
            ->andReturn($json);

        $reader = new ConfigReader($fs);
        $result = $reader->getAggregateValues('something-invalid');
        $this->assertFalse($result);
    }

    public function testSetGetDatabaseConfigReader()
    {
        $databaseConfig = \Mockery::mock('Configuration\DatabaseConfig');

        $path = realpath(__DIR__.'/../../src/lib/Generators/templates/hexagonal/config.json');

        $json = file_get_contents($path);

        $fs = m::mock('Illuminate\Filesystem\Filesystem');
        $fs->shouldReceive('get')->once()->withAnyArgs()
            ->andReturn($json);

        $reader = new ConfigReader($fs);

        $reader->setDatabaseConfigReader($databaseConfig);
        $this->assertTrue($reader->getDatabaseConfigReader() instanceof $databaseConfig);
    }

    public function testSetGetFieldMapper()
    {
        $fieldMapper = \Mockery::mock('\Mapper');

        $path = realpath(__DIR__.'/../../src/lib/Generators/templates/hexagonal/config.json');

        $json = file_get_contents($path);

        $fs = m::mock('Illuminate\Filesystem\Filesystem');
        $fs->shouldReceive('get')->once()->withAnyArgs()
            ->andReturn($json);

        $reader = new ConfigReader($fs);

        $reader->setFieldMapper($fieldMapper);
        $this->assertTrue($reader->getFieldMapper() instanceof $fieldMapper);
    }

    public function testGetFieldMapperIfNotSet()
    {
        $path = realpath(__DIR__.'/../../src/lib/Generators/templates/hexagonal/config.json');

        $json = file_get_contents($path);

        $fs = m::mock('Illuminate\Filesystem\Filesystem');
        $fs->shouldReceive('get')->once()->withAnyArgs()
            ->andReturn($json);

        $reader = new ConfigReader($fs);

        $fieldMapper = $reader->getFieldMapper();
        $this->assertTrue($fieldMapper instanceof $fieldMapper);
    }

    public function testSetGetUseFieldMapperDatabase()
    {
        $path = realpath(__DIR__.'/../../src/lib/Generators/templates/hexagonal/config.json');

        $json = file_get_contents($path);

        $fs = m::mock('Illuminate\Filesystem\Filesystem');
        $fs->shouldReceive('get')->once()->withAnyArgs()
            ->andReturn($json);

        $reader = new ConfigReader($fs);

        $reader->setUseFieldMapperDatabase(true);
        $this->assertTrue($reader->useFieldMapperDatabase());
    }

    public function testValidateFieldMapperDatabasePasses()
    {
        $fieldMapper = $this->getMock(
            '\Mapper',
            ['setDbConfig', 'validateDbConnection']
        );

        $fieldMapper->expects($this->any())
            ->method('setDbConfig')
            ->withAnyParameters();

        $fieldMapper->expects($this->any())
            ->method('validateDbConnection')
            ->withAnyParameters()
            ->willReturn(true);

        $path = realpath(__DIR__.'/../../src/lib/Generators/templates/hexagonal/config.json');

        $json = file_get_contents($path);

        $fs = m::mock('Illuminate\Filesystem\Filesystem');
        $fs->shouldReceive('get')->once()->withAnyArgs()
            ->andReturn($json);

        $reader = new ConfigReader($fs);

        $reader->setFieldMapper($fieldMapper);

        $reader->setUseFieldMapperDatabase(true);

        $valid = $reader->validateFieldMapperDatabase();

        $this->assertTrue($valid instanceof \Mapper);

    }

    public function testValidateFieldMapperDatabase()
    {
        $path = realpath(__DIR__.'/../../src/lib/Generators/templates/hexagonal/config.json');

        $json = file_get_contents($path);

        $fs = m::mock('Illuminate\Filesystem\Filesystem');
        $fs->shouldReceive('get')->once()->withAnyArgs()
            ->andReturn($json);

        $reader = new ConfigReader($fs);

        $reader->setUseFieldMapperDatabase(false);

        $valid = $reader->validateFieldMapperDatabase();

        $this->assertTrue($valid);

    }

    public function testValidateFieldMapperDatabaseFails()
    {
        $fieldMapper = $this->getMock(
            '\Mapper',
            ['setDbConfig', 'validateDbConnection']
        );

        $fieldMapper->expects($this->any())
            ->method('setDbConfig')
            ->withAnyParameters();

        $fieldMapper->expects($this->any())
            ->method('validateDbConnection')
            ->withAnyParameters()
            ->willReturn(true);

        $path = realpath(__DIR__.'/../../src/lib/Generators/templates/hexagonal/config.json');

        $json = file_get_contents($path);

        $fs = m::mock('Illuminate\Filesystem\Filesystem');
        $fs->shouldReceive('get')->once()->withAnyArgs()
            ->andReturn($json);

        $reader = $this->getMock(
            'Configuration\ConfigReader',
            ['useDatabaseConfig'],
            [$fs]
        );

        $reader->expects($this->any())
            ->method('useDatabaseConfig')
            ->willReturn(false);

        $reader->setFieldMapper($fieldMapper);

        $reader->setUseFieldMapperDatabase(true);

        $valid = $reader->validateFieldMapperDatabase();

        $this->assertFalse($valid);

    }

    public function testValidateFieldMapperDatabaseFieldMapperFailsToConnect()
    {
        $fieldMapper = $this->getMock(
            '\Mapper',
            ['setDbConfig', 'validateDbConnection']
        );

        $fieldMapper->expects($this->any())
            ->method('setDbConfig')
            ->withAnyParameters();

        $fieldMapper->expects($this->any())
            ->method('validateDbConnection')
            ->withAnyParameters()
            ->willReturn(false);

        $path = realpath(__DIR__.'/../../src/lib/Generators/templates/hexagonal/config.json');

        $json = file_get_contents($path);

        $fs = m::mock('Illuminate\Filesystem\Filesystem');
        $fs->shouldReceive('get')->once()->withAnyArgs()
            ->andReturn($json);

        $reader = $this->getMock(
            'Configuration\ConfigReader',
            ['useDatabaseConfig'],
            [$fs]
        );

        $reader->expects($this->any())
            ->method('useDatabaseConfig')
            ->willReturn(true);

        $reader->setFieldMapper($fieldMapper);

        $reader->setUseFieldMapperDatabase(true);

        $e = null;

        try {
            $valid = $reader->validateFieldMapperDatabase();
        } catch (\Exception $e) {

        }

        $this->assertTrue($e instanceof \Exception);

    }

    public function testFindAutoLoader()
    {
        $testpath = "/www/blacksmith/src/lib/Configuration";

        $path = realpath(__DIR__.'/../../src/lib/Generators/templates/hexagonal/config.json');

        $json = file_get_contents($path);

        $fs = m::mock('Illuminate\Filesystem\Filesystem');
        $fs->shouldReceive('get')->once()->withAnyArgs()
            ->andReturn($json);

        $reader = new ConfigReader($fs);

        $actualPath = $reader->findAutoLoader($testpath);
        $this->assertEquals("/www/blacksmith/src", $actualPath);

    }

    public function testLoadAutoloaderPasses()
    {
        $testpath = "/www/blacksmith/src/lib/Configuration";

        $path = realpath(__DIR__.'/../../src/lib/Generators/templates/hexagonal/config.json');

        $json = file_get_contents($path);

        $fs = m::mock('Illuminate\Filesystem\Filesystem');
        $fs->shouldReceive('get')->once()->withAnyArgs()
            ->andReturn($json);

        $reader = new ConfigReader($fs);

        $actualPath = $reader->findAutoLoader($testpath);

        $reader->loadAutoloader("$actualPath/vendor/autoload.php");
    }

    public function testLoadAutoloaderFails()
    {
        $testpath = "/www/blacksmith/src/lib/Configuration";

        $path = realpath(__DIR__.'/../../src/lib/Generators/templates/hexagonal/config.json');

        $json = file_get_contents($path);

        $fs = m::mock('Illuminate\Filesystem\Filesystem');
        $fs->shouldReceive('get')->once()->withAnyArgs()
            ->andReturn($json);

        $reader = new ConfigReader($fs);

        $actualPath = $reader->findAutoLoader($testpath);

        $e = null;

        try {
            $reader->loadAutoloader("$actualPath/autoload.php");
        } catch (\Exception $e) {

        }

        $this->assertTrue($e instanceof \Exception);

    }

    public function testValidateFieldMapperModelPasses()
    {
        $fieldMapper = $this->getMock(
            '\Mapper',
            ['validateModel']
        );

        $fieldMapper->expects($this->any())
            ->method('validateModel')
            ->withAnyParameters()
            ->willReturn(true);

        $path = realpath(__DIR__.'/../../src/lib/Generators/templates/hexagonal/config.json');

        $json = file_get_contents($path);

        $fs = m::mock('Illuminate\Filesystem\Filesystem');
        $fs->shouldReceive('get')->once()->withAnyArgs()
            ->andReturn($json);

        $reader = $this->getMock(
            'Configuration\ConfigReader',
            ['loadAutoLoader'],
            [$fs]
        );

        $reader->expects($this->any())
            ->method('loadAutoLoader');

        $reader->setFieldMapper($fieldMapper);

        $valid = $reader->validateFieldMapperModel("/www/blacksmith/src/vendor/database/field-dresser/tests/fixtures/Company");

        $this->assertTrue($valid instanceof \Mapper);

    }

    public function testValidateFieldMapperModelFails()
    {
        $fieldMapper = $this->getMock(
            '\Mapper',
            ['validateModel']
        );

        $fieldMapper->expects($this->any())
            ->method('validateModel')
            ->withAnyParameters()
            ->willReturn(false);

        $path = realpath(__DIR__.'/../../src/lib/Generators/templates/hexagonal/config.json');

        $json = file_get_contents($path);

        $fs = m::mock('Illuminate\Filesystem\Filesystem');
        $fs->shouldReceive('get')->once()->withAnyArgs()
            ->andReturn($json);

        $reader = $this->getMock(
            'Configuration\ConfigReader',
            ['loadAutoLoader'],
            [$fs]
        );

        $reader->expects($this->any())
            ->method('loadAutoLoader');

        $reader->setFieldMapper($fieldMapper);

        $valid = $reader->validateFieldMapperModel("/www/blacksmith/src/vendor/database/field-dresser/tests/fixtures/Company");

        $this->assertFalse($valid);

    }

    public function testValidateFieldMapperModelPassesWithNoModel()
    {
        $path = realpath(__DIR__.'/../../src/lib/Generators/templates/hexagonal/config.json');

        $json = file_get_contents($path);

        $fs = m::mock('Illuminate\Filesystem\Filesystem');
        $fs->shouldReceive('get')->once()->withAnyArgs()
            ->andReturn($json);

        $reader = new ConfigReader($fs);

        $valid = $reader->validateFieldMapperModel(null);

        $this->assertTrue($valid);

    }

    public function testSetGetError()
    {
        $path = realpath(__DIR__.'/../../src/lib/Generators/templates/hexagonal/config.json');

        $json = file_get_contents($path);

        $fs = m::mock('Illuminate\Filesystem\Filesystem');
        $fs->shouldReceive('get')->once()->withAnyArgs()
            ->andReturn($json);

        $reader = new ConfigReader($fs);

        $reader->setError("some error");
        $error = $reader->getError();
        $this->assertEquals("some error", $error);

    }

}
