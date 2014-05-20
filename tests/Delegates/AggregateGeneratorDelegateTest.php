<?php namespace Delegates;

use Delegates\AggregateGeneratorDelegate;
use Console\GenerateCommand;
use Configuration\ConfigReader;
use Generators\Generator;
use Illuminate\Support\Str;
use Illuminate\Filesystem\Filesystem;
use Mustache_Engine;
use Mockery as m;

class AggregateGeneratorDelegateTest extends \BlacksmithTest
{
    private $command;

    private $config;

    private $generator;

    private $args;

    private $optionReader;

    public function setUp()
    {
        parent::setUp();
        $this->command = m::mock('Console\GenerateCommand');
        $this->config = m::mock('Configuration\ConfigReader');
        $this->generator = m::mock('Generators\Generator');
        $this->genFactory = m::mock('Factories\GeneratorFactory');
        $this->filesystem = m::mock('Illuminate\Filesystem\Filesystem');
        $this->filesystem->shouldDeferMissing();
        $this->args = [
            'command'     => 'generate',
            'entity'      => 'Order',
            'what'        => 'scaffold',
            'config-file' => null,
        ];

        $this->optionReader = m::mock('Console\OptionReader');
        $this->optionReader->shouldReceive('isGenerationForced')->andReturn(false);
        $this->optionReader->shouldReceive('getFields')->andReturn([]);
        $this->optionReader->shouldDeferMissing();

        $this->genFactory
            ->shouldReceive('make')
            ->with($this->args['what'], $this->optionReader)
            ->andReturn($this->generator);
    }



    public function testRunWithInvalidConfigAndFails()
    {
        $this->config->shouldReceive('validateConfig')->once()
            ->andReturn(false);

        $this->command->shouldReceive('displayMessage')->once()
            ->with('Error', 'The loaded configuration file is invalid', true);

        $delegate = new AggregateGeneratorDelegate(
            $this->command,
            $this->config,
            $this->genFactory,
            $this->filesystem,
            $this->args,
            $this->optionReader
        );
        $this->assertFalse($delegate->run());
    }



    public function testRunWithInvalidGenerationRequestAndFails()
    {
        //change the args to have an invalid generation request
        $requested = 'something-invalid';
        $this->args['what'] = $requested;

        //mock valid options
        $options = $this->getValidOptions();

        $this->config->shouldReceive('validateConfig')->once()
            ->andReturn(true);

        //return possible aggregates that include the requested
        $this->config->shouldReceive('getAvailableAggregates')->once()
            ->andReturn(array_keys($options));

        $this->command->shouldReceive('displayMessage')->once()
            ->with('Error', "{$requested} is not a valid option", true);

        $this->command->shouldReceive('displayMessage')->once()
            ->with('Error Details', "Please choose from: ". implode(", ", array_keys($options)), true);

        $delegate = $this->getMock(
            'Delegates\AggregateGeneratorDelegate',
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
        $options = $this->getValidOptions();
        $cnt = count($options['scaffold']);

        $this->config->shouldReceive('validateConfig')->once()
            ->andReturn(true);

        //return possible aggregates that include the requested
        $this->config->shouldReceive('getAvailableAggregates')->once()
            ->andReturn(array_keys($options));

        $this->config->shouldReceive('getAggregateValues')->once()
            ->with('scaffold')
            ->andReturn($options['scaffold']);

        $baseDir = '/path/to';
        $this->config->shouldReceive('getConfigDirectory')->times($cnt)
            ->andReturn($baseDir);

        //settings to be returned by getConfigValue below
        $settings = [
            ConfigReader::CONFIG_VAL_TEMPLATE  => 'template.txt',
            ConfigReader::CONFIG_VAL_DIRECTORY => '/path/to/dir',
            ConfigReader::CONFIG_VAL_FILENAME  => 'Output.php'
        ];


        foreach ($options['scaffold'] as $to_generate) {

            $this->config->shouldReceive('getConfigValue')
                ->withAnyArgs()
                ->andReturn($settings);

            $this->genFactory->shouldReceive('make')->once()
                ->with($to_generate, $this->optionReader)
                ->andReturn($this->generator);

            //mock call to generator->make()
            $this->generator->shouldReceive('make')
                ->withAnyArgs()->andReturn(true);

            $dest = '/path/to/dir/Output.php';
            $this->generator->shouldReceive('getTemplateDestination')
                ->andReturn($dest);

            $this->command->shouldReceive('displayMessage')->withAnyArgs();

        }//end foreach

        $delegate = $this->getMock(
            'Delegates\AggregateGeneratorDelegate',
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


    public function testRunWithFalseArgumentsShouldSucceed()
    {
        //mock valid options
        $options = $this->getValidOptions();

        $cnt = count($options['scaffold']);

        $this->config->shouldReceive('validateConfig')->once()
            ->andReturn(true);

        //return possible aggregates that include the requested
        $this->config->shouldReceive('getAvailableAggregates')->once()
            ->andReturn(array_keys($options));

        $options['scaffold']['view_show'] = false;
        $this->config->shouldReceive('getAggregateValues')->once()
            ->with('scaffold')
            ->andReturn($options['scaffold']);

        $baseDir = '/path/to';
        $this->config->shouldReceive('getConfigDirectory')->times($cnt)
            ->andReturn($baseDir);

        //settings to be returned by getConfigValue below
        $settings = [
            ConfigReader::CONFIG_VAL_TEMPLATE  => 'template.txt',
            ConfigReader::CONFIG_VAL_DIRECTORY => '/path/to/dir',
            ConfigReader::CONFIG_VAL_FILENAME  => 'Output.php'
        ];

        foreach ($options['scaffold'] as $to_generate) {
            //test skipping generation
            if ($to_generate === 'view_show') {
                $this->config->shouldReceive('getConfigValue')->once()
                    ->with($to_generate)
                    ->andReturn(false);
                $this->command->shouldReceive('comment')
                    ->with(
                        "Blacksmith",
                        "I skipped \"".$to_generate."\"",
                        true
                    );
                continue;
            }

            $this->genFactory->shouldReceive('make')->once()
                ->with($to_generate, $this->optionReader)
                ->andReturn($this->generator);

            $this->config->shouldReceive('getConfigValue')
                ->with($to_generate)
                ->andReturn($settings);

            //mock call to generator->make()
            $this->generator->shouldReceive('make')
                ->withAnyArgs()->andReturn(true);

            $dest = '/path/to/dir/Output.php';
            $this->generator->shouldReceive('getTemplateDestination')
                ->andReturn($dest);

            $this->command->shouldReceive('displayMessage')->withAnyArgs();

        }//end foreach

        $delegate = $this->getMock(
            'Delegates\AggregateGeneratorDelegate',
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

    public function testRunWithValidArgumentsShouldFail()
    {
        //mock valid options
        $options = $this->getValidOptions();
        $cnt = count($options['scaffold']);

        $this->config->shouldReceive('validateConfig')->once()
            ->andReturn(true);

        //return possible aggregates that include the requested
        $this->config->shouldReceive('getAvailableAggregates')->once()
            ->andReturn(array_keys($options));

        $this->config->shouldReceive('getAggregateValues')->once()
            ->with('scaffold')
            ->andReturn($options['scaffold']);

        $baseDir = '/path/to';
        $this->config->shouldReceive('getConfigDirectory')->times($cnt)
            ->andReturn($baseDir);

        //settings to be returned by getConfigValue below
        $settings = [
            ConfigReader::CONFIG_VAL_TEMPLATE  => 'template.txt',
            ConfigReader::CONFIG_VAL_DIRECTORY => '/path/to/dir',
            ConfigReader::CONFIG_VAL_FILENAME  => 'Output.php'
        ];


        foreach ($options['scaffold'] as $to_generate) {

            $this->config->shouldReceive('getConfigValue')
                ->withAnyArgs()
                ->andReturn($settings);

            $this->genFactory->shouldReceive('make')->once()
                ->with($to_generate, $this->optionReader)
                ->andReturn($this->generator);

            //mock call to generator->make()
            $this->generator->shouldReceive('make')
                ->withAnyArgs()->andReturn(false);


            $this->command->shouldReceive('displayMessage')
                ->with(
                    "Blacksmith",
                    "An unknown error occurred, nothing was generated for {$to_generate}",
                    true
                );

        }//end foreach

        $delegate = $this->getMock(
            'Delegates\AggregateGeneratorDelegate',
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

    public function testRunWithInvalidDatabaseArgumentsShouldFail()
    {
        //mock valid options
        $options = $this->getValidOptions();
        $cnt = count($options['scaffold']);

        $this->config->shouldReceive('validateConfig')->once()
            ->andReturn(true);

        $this->config->shouldReceive('getError')->once();

        $this->command->shouldReceive('displayMessage')->once()
            ->withAnyArgs();

        $delegate = $this->getMock(
            'Delegates\AggregateGeneratorDelegate',
            ['isFieldMapperDatabaseValid', 'isOptionsValid'],
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
            ->method('isOptionsValid')
            ->withAnyParameters();

        $result = $delegate->run();

        $this->assertFalse($result);
    }

    public function testRunWithInvalidModelArgumentsShouldFail()
    {
        //mock valid options
        $options = $this->getValidOptions();
        $cnt = count($options['scaffold']);

        $this->config->shouldReceive('validateConfig')->once()
            ->andReturn(true);

        $this->config->shouldReceive('getError')->once();

        $this->command->shouldReceive('displayMessage')->once()
            ->withAnyArgs();

        $delegate = $this->getMock(
            'Delegates\AggregateGeneratorDelegate',
            ['isFieldMapperModelValid', 'isOptionsValid'],
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
            ->method('isOptionsValid')
            ->withAnyParameters();

        $result = $delegate->run();

        $this->assertFalse($result);
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

        $delegate = new AggregateGeneratorDelegate(
            $this->command,
            $this->config,
            $this->genFactory,
            $this->filesystem,
            $this->args,
            $optionsReader
        );

        $result = $delegate->isOptionsValid();

        $this->assertTrue($result);
    }

    public function testIsOptionsValidFails()
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
            ->withAnyParameters()
            ->willReturn('some error');

        $this->command->shouldReceive('displayMessage')->once()
            ->withAnyArgs();

        $delegate = new AggregateGeneratorDelegate(
            $this->command,
            $this->config,
            $this->genFactory,
            $this->filesystem,
            $this->args,
            $optionsReader
        );

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
            ['useFieldMapperDatabase'],
            [$options]
        );

        $optionsReader->expects($this->any())
            ->method('useFieldMapperDatabase')
            ->withAnyParameters()
            ->willReturn(false);

        $delegate = new AggregateGeneratorDelegate(
            $this->command,
            $this->config,
            $this->genFactory,
            $this->filesystem,
            $this->args,
            $optionsReader
        );

        $result = $delegate->isFieldMapperDatabaseValid();

        $this->assertTrue($result);
    }

    public function testIsFieldMapperDatabaseValidMapperPasses()
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
            ->willReturn(true);

        $delegate = new AggregateGeneratorDelegate(
            $this->command,
            $configReader,
            $this->genFactory,
            $this->filesystem,
            $this->args,
            $optionsReader
        );

        $result = $delegate->isFieldMapperDatabaseValid();

        $this->assertTrue($result);
    }

    public function testIsFieldMapperDatabaseValidMapperFails()
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

        $this->command->shouldReceive('displayMessage')->once()
            ->withAnyArgs();

        $delegate = new AggregateGeneratorDelegate(
            $this->command,
            $configReader,
            $this->genFactory,
            $this->filesystem,
            $this->args,
            $optionsReader
        );

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

        $delegate = new AggregateGeneratorDelegate(
            $this->command,
            $this->config,
            $this->genFactory,
            $this->filesystem,
            $this->args,
            $optionsReader
        );

        $result = $delegate->isFieldMapperModelValid();

        $this->assertTrue($result);
    }

    public function testIsFieldMapperModelValidMapperPasses()
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

        $delegate = new AggregateGeneratorDelegate(
            $this->command,
            $configReader,
            $this->genFactory,
            $this->filesystem,
            $this->args,
            $optionsReader
        );

        $result = $delegate->isFieldMapperModelValid();

        $this->assertTrue($result);
    }

    public function testIsFieldMapperModelValidMapperFails()
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

        $this->command->shouldReceive('displayMessage')->once()
            ->withAnyArgs();

        $delegate = new AggregateGeneratorDelegate(
            $this->command,
            $configReader,
            $this->genFactory,
            $this->filesystem,
            $this->args,
            $optionsReader
        );

        $result = $delegate->isFieldMapperModelValid();

        $this->assertFalse($result);
    }

    public function testUpdateRoutesFile()
    {
        $name = 'orders';
        $dir = '/some/path';
        $routes = implode(DIRECTORY_SEPARATOR, [$dir, 'app', 'routes.php']);
        $data = "\n\nRoute::resource('" . $name . "', '" . ucwords($name) . "Controller');";

        $this->filesystem->shouldReceive('get')->once()
            ->with($routes)
            ->andReturn('');

        $this->filesystem->shouldReceive('exists')->once()
            ->with($routes)
            ->andReturn(true);

        $this->filesystem->shouldReceive('append')->once()
            ->with($routes, $data);

        $delegate = new AggregateGeneratorDelegate(
            $this->command,
            $this->config,
            $this->genFactory,
            $this->filesystem,
            $this->args,
            $this->optionReader
        );
        $delegate->updateRoutesFile($name, $dir);
    }


    public function testNotUpdateRoutesFileWithDuplicates()
    {
        $name = 'orders';
        $dir = '/some/path';
        $routes = implode(DIRECTORY_SEPARATOR, [$dir, 'app', 'routes.php']);
        $route = "Route::resource('" . $name . "', '" . ucwords($name) . "Controller');";
        $data = "\n\n".$route;

        $this->filesystem->shouldReceive('get')->once()
            ->with($routes)
            ->andReturn($route);

        $this->filesystem->shouldReceive('exists')->once()
            ->with($routes)
            ->andReturn(true);

        $this->filesystem->shouldReceive('append')->never()
            ->with($routes, $data);

        $delegate = new AggregateGeneratorDelegate(
            $this->command,
            $this->config,
            $this->genFactory,
            $this->filesystem,
            $this->args,
            $this->optionReader
        );
        $delegate->updateRoutesFile($name, $dir);
    }

    private function getValidOptions()
    {
        return [
                'scaffold' => [
                    "model",
                    "controller",
                    "seed",
                    "migration_create",
                    "view_create",
                    "view_update",
                    "view_show",
                    "view_index",
                    "form",
                    "unit_test",
                    "service_creator",
                    "service_creator_test",
                    "service_updater",
                    "service_updater_test",
                    "service_destroyer",
                    "service_destroyer_test",
                    "validator"
                ]
            ];
    }
}
