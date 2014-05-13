<?php namespace Delegates;

use Console\OptionReader;
use Delegates\GeneratorDelegate;
use Console\GenerateCommand;
use Configuration\ConfigReader;
use Generators\Generator;
use Illuminate\Support\Str;
use Illuminate\Filesystem\Filesystem;
use Mustache_Engine;
use Mockery as m;

class GeneratorDelegateTest extends \BlacksmithTest
{
    private $command;

    private $config;

    private $generator;

    private $args;

    private $optionReader;

    private $genFactory;

    private $filesystem;

    public function setUp()
    {
        parent::setUp();
        $this->command = m::mock('Console\GenerateCommand');
        $this->config = m::mock('Configuration\ConfigReader');
        $this->generator = m::mock('Generators\Generator');
        $this->genFactory = m::mock('Factories\GeneratorFactory');
        $this->filesystem = m::mock('Illuminate\Filesystem\Filesystem');
        $this->filesystem->shouldDeferMissing();
        $this->optionReader = m::mock('Console\OptionReader');
        $this->args = [
            'command'     => 'generate',
            'entity'      => 'Order',
            'what'        => 'model',
            'config-file' => null,
        ];

        $this->genFactory
            ->shouldReceive('make')
            ->with($this->args['what'], $this->optionReader)
            ->andReturn($this->generator);
    }



    public function testRunWithInvalidConfigAndFails()
    {
        $this->config->shouldReceive('validateConfig')->once()
            ->andReturn(false);


        $this->command->shouldReceive('comment')->once()
            ->with('Error', 'The loaded configuration file is invalid', true);

        $delegate = $this->getMock(
            'Delegates\GeneratorDelegate',
            ['isOptionsValid'],
            [
                $this->command,
                $this->config,
                $this->genFactory,
                $this->filesystem,
                $this->args,
                $this->optionReader
            ]
        );

        $delegate->expects($this->any())
            ->method('isOptionsValid')
            ->withAnyParameters();

        $this->assertFalse($delegate->run());
    }


    public function testRunWithInvalidGenerationRequestAndFails()
    {
        $this->genFactory->shouldReceive('make');

        //change the args to have an invalid generation request
        $requested = 'something-invalid';
        $options = ['foo', 'bar', 'biz'];

        $this->args['what'] = $requested;


        $this->config->shouldReceive('validateConfig')->once()
            ->andReturn(true);

        //return possible generators that include the requested
        $this->config->shouldReceive('getAvailableGenerators')->once()
            ->andReturn($options);

        $this->config->shouldReceive('getConfigType')->once();

        $this->optionReader->shouldReceive('useFieldMapperDatabase')->once()
            ->andReturn(false);

        $this->optionReader->shouldReceive('useFieldMapperModel')->once()
            ->andReturn(null);


        $this->command->shouldReceive('comment')->once()
            ->with('Error', "{$requested} is not a valid option", true);

        $this->command->shouldReceive('comment')->once()
            ->with('Error Details', "Please choose from: ". implode(", ", $options), true);

        $delegate = $this->getMock(
            'Delegates\GeneratorDelegate',
            ['isOptionsValid'],
            [
                $this->command,
                $this->config,
                $this->genFactory,
                $this->filesystem,
                $this->args,
                $this->optionReader
            ]
        );

        $delegate->expects($this->any())
            ->method('isOptionsValid')
            ->withAnyParameters();

        $this->assertFalse($delegate->run());
    }


    public function testRunWithValidArgumentsShouldSucceed()
    {
        //mock valid options
        $options = ['model', 'controller'];

        $this->config->shouldReceive('validateConfig')->once()
            ->andReturn(true);

        //return possible generators that include the requested
        $this->config->shouldReceive('getAvailableGenerators')->once()
            ->andReturn($options);

        $this->config->shouldReceive('getConfigType')->once();

        $this->optionReader->shouldReceive('useFieldMapperDatabase')->once()
            ->andReturn(false);

        $this->optionReader->shouldReceive('useFieldMapperModel')->once()
            ->andReturn(null);

        $baseDir = '/path/to';
        $this->config->shouldReceive('getConfigDirectory')->once()
            ->andReturn($baseDir);

        //settings to be returned by getConfigValue below
        $settings = [
            ConfigReader::CONFIG_VAL_TEMPLATE  => 'template.txt',
            ConfigReader::CONFIG_VAL_DIRECTORY => '/path/to/dir',
            ConfigReader::CONFIG_VAL_FILENAME  => 'Output.php'
        ];

        $this->config->shouldReceive('getConfigValue')->once()
            ->with($this->args['what'])
            ->andReturn($settings);

        //mock call to generator->make()
        $this->generator->shouldReceive('make')->once()
            ->with(
                $this->args['entity'],
                implode(DIRECTORY_SEPARATOR, [$baseDir, $settings[ConfigReader::CONFIG_VAL_TEMPLATE]]),
                $settings[ConfigReader::CONFIG_VAL_DIRECTORY],
                $settings[ConfigReader::CONFIG_VAL_FILENAME],
                null
            )->andReturn(true);

        $dest = '/path/to/dir/Output.php';

        $this->generator->shouldReceive('getTemplateDestination')->once()
            ->andReturn($dest);

        $this->command->shouldReceive('comment')->once()
            ->with('Blacksmith', "Success, I generated the code for you in {$dest}", false);

        $delegate = $this->getMock(
            'Delegates\GeneratorDelegate',
            ['isOptionsValid'],
            [
                $this->command,
                $this->config,
                $this->genFactory,
                $this->filesystem,
                $this->args,
                $this->optionReader
            ]
        );

        $delegate->expects($this->any())
            ->method('isOptionsValid')
            ->withAnyParameters();

        $this->assertTrue($delegate->run());
    }

    public function testRunWithValidArgumentsButGeneratorFailure()
    {
        //mock valid options
        $options = ['model', 'controller'];

        $this->config->shouldReceive('validateConfig')->once()
            ->andReturn(true);

        //return possible generators that include the requested
        $this->config->shouldReceive('getAvailableGenerators')->once()
            ->andReturn($options);

        $this->config->shouldReceive('getConfigType')->once();

        $baseDir = "/path/to";
        $this->config->shouldReceive('getConfigDirectory')->once()
            ->andReturn($baseDir);

        $this->optionReader->shouldReceive('useFieldMapperDatabase')->once()
            ->andReturn(false);

        $this->optionReader->shouldReceive('useFieldMapperModel')->once()
            ->andReturn(null);

        //settings to be returned by getConfigValue below
        $settings = [
            ConfigReader::CONFIG_VAL_TEMPLATE  => 'template.txt',
            ConfigReader::CONFIG_VAL_DIRECTORY => '/path/to/dir',
            ConfigReader::CONFIG_VAL_FILENAME  => 'Output.php'
        ];

        $this->config->shouldReceive('getConfigValue')->once()
            ->with($this->args['what'])
            ->andReturn($settings);

        //mock call to generator->make()
        $this->generator->shouldReceive('make')->once()
            ->with(
                $this->args['entity'],
                implode(DIRECTORY_SEPARATOR, [$baseDir, $settings[ConfigReader::CONFIG_VAL_TEMPLATE]]),
                $settings[ConfigReader::CONFIG_VAL_DIRECTORY],
                $settings[ConfigReader::CONFIG_VAL_FILENAME],
                null
            )->andReturn(false);

        $this->command->shouldReceive('comment')->once()
            ->with('Blacksmith', "An unknown error occured, nothing was generated", true);

        $delegate = $this->getMock(
            'Delegates\GeneratorDelegate',
            ['isOptionsValid'],
            [
                $this->command,
                $this->config,
                $this->genFactory,
                $this->filesystem,
                $this->args,
                $this->optionReader
            ]
        );

        $delegate->expects($this->any())
            ->method('isOptionsValid')
            ->withAnyParameters();

        $this->assertFalse($delegate->run());
    }

    public function testRunWithValidArgumentsButGeneratorWithMapperDatabaseFailure()
    {
        //mock valid options
        $options = ['model', 'controller'];

        $this->config->shouldReceive('validateConfig')->once()
            ->andReturn(true);

        $this->config->shouldReceive('getError')->once()
            ->withAnyArgs()
            ->andReturn('some error');

        $delegate = $this->getMock(
            'Delegates\GeneratorDelegate',
            ['isFieldMapperDatabaseValid', 'throwError', 'isOptionsValid'],
            [
                $this->command,
                $this->config,
                $this->genFactory,
                $this->filesystem,
                $this->args,
                $this->optionReader
            ]
        );

        $delegate->expects($this->any())
            ->method('isFieldMapperDatabaseValid')
            ->withAnyParameters()
            ->willReturn(false);

        $delegate->expects($this->any())
            ->method('throwError')
            ->withAnyParameters();

        $delegate->expects($this->any())
            ->method('isOptionsValid')
            ->withAnyParameters();

        $this->assertFalse($delegate->run());
    }

    public function testRunWithValidArgumentsButGeneratorWithMapperModelFailure()
    {
        //mock valid options
        $options = ['model', 'controller'];

        $this->config->shouldReceive('validateConfig')->once()
            ->andReturn(true);

        $this->config->shouldReceive('getError')->once()
            ->withAnyArgs()
            ->andReturn('some error');

        $this->optionReader->shouldReceive('useFieldMapperDatabase')->once()
            ->andReturn(false);

        $delegate = $this->getMock(
            'Delegates\GeneratorDelegate',
            ['isFieldMapperModelValid', 'throwError', 'isOptionsValid'],
            [
                $this->command,
                $this->config,
                $this->genFactory,
                $this->filesystem,
                $this->args,
                $this->optionReader
            ]
        );

        $delegate->expects($this->any())
            ->method('isFieldMapperModelValid')
            ->withAnyParameters()
            ->willReturn(false);

        $delegate->expects($this->any())
            ->method('throwError')
            ->withAnyParameters();

        $delegate->expects($this->any())
            ->method('isOptionsValid')
            ->withAnyParameters();

        $this->assertFalse($delegate->run());
    }

    public function testIsOptionsValidPasses()
    {
        $options = [
            'f',
            'architecture' => 'test',
            'fields' => 'username:string:unique, age:integer:nullable'
        ];

        $optionsReader = $this->getMock(
            'Console\OptionReader',
            ['validateOptions'],
            [$options]
        );

        $optionsReader->expects($this->any())
            ->method('validateOptions')
            ->withAnyParameters()
            ->willReturn(true);

        $generator = m::mock('Generators\Generator');
        $args = [
            'command'     => 'generate',
            'entity'      => 'Order',
            'what'        => 'model',
            'config-file' => null,
        ];

        $this->genFactory
            ->shouldReceive('make')
            ->with($args['what'], $optionsReader)
            ->andReturn($generator);

        $delegate = new GeneratorDelegate(
            $this->command,
            $this->config,
            $this->genFactory,
            $this->filesystem,
            $args,
            $optionsReader
        );

        $result = $delegate->isOptionsValid();

        $this->assertTrue($result);
    }

    public function testIsOptionsValidFailed()
    {
        $options = [
            'f',
            'architecture' => 'test',
            'fields' => 'username:string:unique, age:integer:nullable'
        ];

        $optionsReader = $this->getMock(
            'Console\OptionReader',
            ['validateOptions', 'getError'],
            [$options]
        );

        $optionsReader->expects($this->any())
            ->method('validateOptions')
            ->withAnyParameters()
            ->willReturn(false);

        $optionsReader->expects($this->any())
            ->method('getError')
            ->withAnyParameters();

        $generator = m::mock('Generators\Generator');
        $args = [
            'command'     => 'generate',
            'entity'      => 'Order',
            'what'        => 'model',
            'config-file' => null,
        ];

        $this->genFactory
            ->shouldReceive('make')
            ->with($args['what'], $optionsReader)
            ->andReturn($generator);

        $delegate = $this->getMock(
            'Delegates\GeneratorDelegate',
            ['throwError'],
            [
                $this->command,
                $this->config,
                $this->genFactory,
                $this->filesystem,
                $args,
                $optionsReader
            ]
        );

        $delegate->expects($this->any())
            ->method('throwError')
            ->withAnyParameters();

        $result = $delegate->isOptionsValid();

        $this->assertFalse($result);
    }

    public function testIsFieldMapperDatabaseValidPasses()
    {
        $options = [
            'f',
            'architecture' => 'test',
            'fields' => 'username:string:unique, age:integer:nullable'
        ];

        $optionsReader = $this->getMock(
            'Console\OptionReader',
            ['validateOptions', 'useFieldMapperDatabase'],
            [$options]
        );

        $optionsReader->expects($this->any())
            ->method('validateOptions')
            ->withAnyParameters()
            ->willReturn(true);

        $optionsReader->expects($this->any())
            ->method('useFieldMapperDatabase')
            ->withAnyParameters()
            ->willReturn(false);

        $generator = m::mock('Generators\Generator');
        $args = [
            'command'     => 'generate',
            'entity'      => 'Order',
            'what'        => 'model',
            'config-file' => null,
        ];

        $this->genFactory
            ->shouldReceive('make')
            ->with($args['what'], $optionsReader)
            ->andReturn($generator);

        $delegate = new GeneratorDelegate(
            $this->command,
            $this->config,
            $this->genFactory,
            $this->filesystem,
            $args,
            $optionsReader
        );

        $result = $delegate->isFieldMapperDatabaseValid();

        $this->assertTrue($result);
    }

    public function testIsFieldMapperDatabaseValidWithModelPasses()
    {
        $options = [
            'f',
            'architecture' => 'test',
            'fields' => 'username:string:unique, age:integer:nullable'
        ];

        $optionsReader = $this->getMock(
            'Console\OptionReader',
            ['validateOptions', 'useFieldMapperDatabase'],
            [$options]
        );

        $optionsReader->expects($this->any())
            ->method('validateOptions')
            ->withAnyParameters()
            ->willReturn(true);

        $optionsReader->expects($this->any())
            ->method('useFieldMapperDatabase')
            ->withAnyParameters()
            ->willReturn(true);

        $path = realpath(__DIR__.'/../../src/lib/Generators/templates/hexagonal/config.json');

        $json = file_get_contents($path);

        $fs = m::mock('Illuminate\Filesystem\Filesystem');
        $fs->shouldReceive('get')->once()->withAnyArgs()
            ->andReturn($json);

        $configReader = $this->getMock(
            'Configuration\ConfigReader',
            ['setUseFieldMapperDatabase', 'validateFieldMapperDatabase'],
            [$fs]
        );

        $configReader->expects($this->any())
            ->method('setUseFieldMapperDatabase')
            ->withAnyParameters()
            ->willReturn(true);

        $configReader->expects($this->any())
            ->method('validateFieldMapperDatabase')
            ->withAnyParameters()
            ->willReturn(true);

        $generator = m::mock('Generators\Generator');
        $args = [
            'command'     => 'generate',
            'entity'      => 'Order',
            'what'        => 'model',
            'config-file' => null,
        ];

        $this->genFactory
            ->shouldReceive('make')
            ->with($args['what'], $optionsReader)
            ->andReturn($generator);

        $delegate = new GeneratorDelegate(
            $this->command,
            $configReader,
            $this->genFactory,
            $this->filesystem,
            $args,
            $optionsReader
        );

        $result = $delegate->isFieldMapperDatabaseValid();

        $this->assertTrue($result);
    }

    public function testIsFieldMapperDatabaseValidWithModelFails()
    {
        $options = [
            'f',
            'architecture' => 'test',
            'fields' => 'username:string:unique, age:integer:nullable'
        ];

        $optionsReader = $this->getMock(
            'Console\OptionReader',
            ['useFieldMapperDatabase'],
            [$options]
        );

        $optionsReader->expects($this->any())
            ->method('useFieldMapperDatabase')
            ->withAnyParameters()
            ->willReturn(true);

        $path = realpath(__DIR__.'/../../src/lib/Generators/templates/hexagonal/config.json');

        $json = file_get_contents($path);

        $fs = m::mock('Illuminate\Filesystem\Filesystem');
        $fs->shouldReceive('get')->once()->withAnyArgs()
            ->andReturn($json);

        $configReader = $this->getMock(
            'Configuration\ConfigReader',
            ['setUseFieldMapperDatabase', 'validateFieldMapperDatabase'],
            [$fs]
        );

        $configReader->expects($this->any())
            ->method('setUseFieldMapperDatabase')
            ->withAnyParameters()
            ->willReturn(true);

        $configReader->expects($this->any())
            ->method('validateFieldMapperDatabase')
            ->withAnyParameters()
            ->willReturn(false);

        $generator = m::mock('Generators\Generator');
        $args = [
            'command'     => 'generate',
            'entity'      => 'Order',
            'what'        => 'model',
            'config-file' => null,
        ];

        $this->genFactory
            ->shouldReceive('make')
            ->with($args['what'], $optionsReader)
            ->andReturn($generator);

        $delegate = $this->getMock(
            'Delegates\GeneratorDelegate',
            ['throwError'],
            [
                $this->command,
                $configReader,
                $this->genFactory,
                $this->filesystem,
                $args,
                $optionsReader
            ]
        );

        $delegate->expects($this->any())
            ->method('throwError')
            ->withAnyParameters();

        $result = $delegate->isFieldMapperDatabaseValid();

        $this->assertFalse($result);
    }

    public function testIsFieldMapperModelValidPasses()
    {
        $options = [
            'f',
            'architecture' => 'test',
            'fields' => 'username:string:unique, age:integer:nullable'
        ];

        $optionsReader = $this->getMock(
            'Console\OptionReader',
            ['useFieldMapperModel'],
            [$options]
        );

        $optionsReader->expects($this->any())
            ->method('useFieldMapperModel')
            ->withAnyParameters()
            ->willReturn(false);

        $generator = m::mock('Generators\Generator');
        $args = [
            'command'     => 'generate',
            'entity'      => 'Order',
            'what'        => 'model',
            'config-file' => null,
        ];

        $this->genFactory
            ->shouldReceive('make')
            ->with($args['what'], $optionsReader)
            ->andReturn($generator);

        $delegate = new GeneratorDelegate(
            $this->command,
            $this->config,
            $this->genFactory,
            $this->filesystem,
            $args,
            $optionsReader
        );

        $result = $delegate->isFieldMapperModelValid();

        $this->assertTrue($result);
    }

    public function testIsFieldMapperModelValidWithModelPasses()
    {
        $options = [
            'f',
            'architecture' => 'test',
            'fields' => 'username:string:unique, age:integer:nullable'
        ];

        $optionsReader = $this->getMock(
            'Console\OptionReader',
            ['useFieldMapperDatabase'],
            [$options]
        );

        $optionsReader->expects($this->any())
            ->method('useFieldMapperDatabase')
            ->withAnyParameters()
            ->willReturn(false);

        $path = realpath(__DIR__.'/../../src/lib/Generators/templates/hexagonal/config.json');

        $json = file_get_contents($path);

        $fs = m::mock('Illuminate\Filesystem\Filesystem');
        $fs->shouldReceive('get')->once()->withAnyArgs()
            ->andReturn($json);

        $configReader = $this->getMock(
            'Configuration\ConfigReader',
            ['validateFieldMapperModel'],
            [$fs]
        );

        $configReader->expects($this->any())
            ->method('validateFieldMapperModel')
            ->withAnyParameters()
            ->willReturn(true);

        $generator = m::mock('Generators\Generator');
        $args = [
            'command'     => 'generate',
            'entity'      => 'Order',
            'what'        => 'model',
            'config-file' => null,
        ];

        $this->genFactory
            ->shouldReceive('make')
            ->with($args['what'], $optionsReader)
            ->andReturn($generator);

        $delegate = new GeneratorDelegate(
            $this->command,
            $configReader,
            $this->genFactory,
            $this->filesystem,
            $args,
            $optionsReader
        );

        $result = $delegate->isFieldMapperModelValid();

        $this->assertTrue($result);
    }

    public function testIsFieldMapperModelValidWithModelFails()
    {
        $options = [
            'f',
            'architecture' => 'test',
            'fields' => 'username:string:unique, age:integer:nullable'
        ];

        $optionsReader = $this->getMock(
            'Console\OptionReader',
            ['useFieldMapperModel'],
            [$options]
        );

        $optionsReader->expects($this->any())
            ->method('useFieldMapperModel')
            ->withAnyParameters()
            ->willReturn(true);

        $path = realpath(__DIR__.'/../../src/lib/Generators/templates/hexagonal/config.json');

        $json = file_get_contents($path);

        $fs = m::mock('Illuminate\Filesystem\Filesystem');
        $fs->shouldReceive('get')->once()->withAnyArgs()
            ->andReturn($json);

        $configReader = $this->getMock(
            'Configuration\ConfigReader',
            ['validateFieldMapperModel'],
            [$fs]
        );

        $configReader->expects($this->any())
            ->method('validateFieldMapperModel')
            ->withAnyParameters()
            ->willReturn(false);

        $configReader->expects($this->any())
            ->method('validateFieldMapperDatabase')
            ->withAnyParameters()
            ->willReturn(false);

        $generator = m::mock('Generators\Generator');
        $args = [
            'command'     => 'generate',
            'entity'      => 'Order',
            'what'        => 'model',
            'config-file' => null,
        ];

        $this->genFactory
            ->shouldReceive('make')
            ->with($args['what'], $optionsReader)
            ->andReturn($generator);

        $delegate = $this->getMock(
            'Delegates\GeneratorDelegate',
            ['throwError'],
            [
                $this->command,
                $configReader,
                $this->genFactory,
                $this->filesystem,
                $args,
                $optionsReader
            ]
        );

        $delegate->expects($this->any())
            ->method('throwError')
            ->withAnyParameters();

        $result = $delegate->isFieldMapperModelValid();

        $this->assertFalse($result);
    }

}
