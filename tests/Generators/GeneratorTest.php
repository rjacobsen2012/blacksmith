<?php namespace Generators;

use Console\OptionReader;
use DateTime;
use Generators\Generator;
use Illuminate\Filesystem\Filesystem;
use Mustache_Engine;
use Mockery as m;

class GeneratorTest extends \BlacksmithTest
{

    public function testMake()
    {
        $parsedTemplate = "
            Order
            Order
            Orders
            orders
            order
        ";

        $template = '/path/to/source/template.txt';
        $destination = '/render/template/here';
        $file = 'Order.php';

        $fs = m::mock('Illuminate\Filesystem\Filesystem');
        $me = m::mock('Mustache_Engine');
        $me->shouldDeferMissing();
        $fp = m::mock('Parsers\FieldParser');
        $fp->shouldDeferMissing();

        $or = m::mock('Console\OptionReader');
        $or->shouldReceive('getFields')->andReturn('name:string');
        $or->shouldReceive('isGenerationForced')->andReturn(false);

        $generator = m::mock('Generators\Generator', array($fs, $me, $fp, $or));
        $generator->shouldDeferMissing();

        $generator->shouldReceive('getFileName')
            ->twice()
            ->andReturn($file);

        $generator->shouldReceive('getTemplate')
            ->twice()
            ->with($template)
            ->andReturn($parsedTemplate);

        $outfile = implode(DIRECTORY_SEPARATOR, [$destination, $file]);

        $outfileDir = pathinfo($outfile, PATHINFO_DIRNAME);
        $fs->shouldReceive('exists')
            ->twice()
            ->with($outfileDir)
            ->andReturn(false);

        $fs->shouldReceive('makeDirectory')
            ->twice()
            ->with($outfileDir, 0777, true)
            ->andReturn(true);

        $fs->shouldReceive('exists')
            ->once()
            ->with($outfile)->andReturn(false);

        $fs->shouldReceive('put')->once()
            ->with($outfile, $parsedTemplate)->andReturn(true);

        //first we test a file that does not exist
        $this->assertTrue($generator->make('order', $template, $destination));

        //go ahead and assert on entity name
        $this->assertEquals('order', strtolower($generator->getEntityName()));

        $this->assertEquals(
            $parsedTemplate,
            $generator->getParsedTemplate()
        );

        $this->assertEquals(
            $outfile,
            $generator->getTemplateDestination()
        );

        //NOW SECOND TEST

        //now we mock that the file already exists
        $fs->shouldReceive('exists')
            ->once()
            ->with($outfile)->andReturn(true);

        //test making a file that exists, should return false
        $this->assertFalse($generator->make('order', $template, $destination, null, null));

        //assert that the field data was parsed
        $this->assertEquals(
            ['name' => ['type' => 'string']],
            $generator->getFieldData()
        );
    }

    public function testMakeWithMapper()
    {

        $mapper = $this->getMock(
            '\Mapper'
        );

        $mapper->expects($this->any())
            ->method('getFields')
            ->withAnyParameters()
            ->willReturn(
                [
                    'name' => [
                        'type' => 'string',
                        'required' => false,
                        'read' => true,
                        'write' => true
                    ]
                ]
            );

        $parsedTemplate = "
            Order
            Order
            Orders
            orders
            order
        ";

        $template = '/path/to/source/template.txt';
        $destination = '/render/template/here';
        $file = 'Order.php';

        $fs = m::mock('Illuminate\Filesystem\Filesystem');
        $me = m::mock('Mustache_Engine');
        $me->shouldDeferMissing();

        $fp = $this->getMock(
            'Parsers\FieldParser',
            ['parse']
        );

        $expected = [
            'name' => [
                'type' => 'string',
                'decorators' => [
                    'nullable'
                ]
            ]
        ];

        $fp->expects($this->any())
            ->method('parse')
            ->withAnyParameters()
            ->willReturn($expected);

        $fp = m::mock('Parsers\FieldParser');
        $fp->shouldDeferMissing();

        $or = $this->getMock(
            'Console\OptionReader',
            ['getFields', 'getModel'],
            [[]]
        );

        $or->expects($this->any())
            ->method('getFields')
            ->withAnyParameters()
            ->willReturn(false);

        $or->expects($this->any())
            ->method('getModel')
            ->withAnyParameters()
            ->willReturn("User");

        $generator = m::mock('Generators\Generator', array($fs, $me, $fp, $or));
        $generator->shouldDeferMissing();

        $generator->shouldReceive('getFileName')
            ->twice()
            ->andReturn($file);

        $generator->shouldReceive('getTemplate')
            ->twice()
            ->with($template)
            ->andReturn($parsedTemplate);

        $outfile = implode(DIRECTORY_SEPARATOR, [$destination, $file]);

        $outfileDir = pathinfo($outfile, PATHINFO_DIRNAME);
        $fs->shouldReceive('exists')
            ->twice()
            ->with($outfileDir)
            ->andReturn(false);

        $fs->shouldReceive('makeDirectory')
            ->twice()
            ->with($outfileDir, 0777, true)
            ->andReturn(true);

        $fs->shouldReceive('exists')
            ->once()
            ->with($outfile)->andReturn(false);

        $fs->shouldReceive('put')->once()
            ->with($outfile, $parsedTemplate)->andReturn(true);

        //first we test a file that does not exist
        $this->assertTrue($generator->make('order', $template, $destination, null, $mapper));

        //go ahead and assert on entity name
        $this->assertEquals('order', strtolower($generator->getEntityName()));

        $this->assertEquals(
            $parsedTemplate,
            $generator->getParsedTemplate()
        );

        $this->assertEquals(
            $outfile,
            $generator->getTemplateDestination()
        );

        //NOW SECOND TEST

        //now we mock that the file already exists
        $fs->shouldReceive('exists')
            ->once()
            ->with($outfile)->andReturn(true);

        //test making a file that exists, should return false
        $this->assertFalse($generator->make('order', $template, $destination, null, $mapper));

        //assert that the field data was parsed
        $this->assertEquals(
            $expected,
            $generator->getFieldData()
        );
    }

    public function testMakeWithMapperWithNoModel()
    {

        $mapper = $this->getMock(
            '\Mapper'
        );

        $mapper->expects($this->any())
            ->method('getFields')
            ->withAnyParameters()
            ->willReturn(
                [
                    'name' => [
                        'type' => 'string',
                        'required' => false,
                        'read' => true,
                        'write' => true
                    ]
                ]
            );

        $parsedTemplate = "
            Order
            Order
            Orders
            orders
            order
        ";

        $template = '/path/to/source/template.txt';
        $destination = '/render/template/here';
        $file = 'Order.php';

        $fs = m::mock('Illuminate\Filesystem\Filesystem');
        $me = m::mock('Mustache_Engine');
        $me->shouldDeferMissing();

        $fp = $this->getMock(
            'Parsers\FieldParser',
            ['parse']
        );

        $expected = [
            'name' => [
                'type' => 'string',
                'decorators' => [
                    'nullable'
                ]
            ]
        ];

        $fp->expects($this->any())
            ->method('parse')
            ->withAnyParameters()
            ->willReturn($expected);

        $fp = m::mock('Parsers\FieldParser');
        $fp->shouldDeferMissing();

        $or = $this->getMock(
            'Console\OptionReader',
            ['getFields', 'getModel'],
            [[]]
        );

        $or->expects($this->any())
            ->method('getFields')
            ->withAnyParameters()
            ->willReturn(false);

        $or->expects($this->any())
            ->method('getModel')
            ->withAnyParameters()
            ->willReturn(null);

        $generator = m::mock('Generators\Generator', array($fs, $me, $fp, $or));
        $generator->shouldDeferMissing();

        $generator->shouldReceive('getFileName')
            ->twice()
            ->andReturn($file);

        $generator->shouldReceive('getTemplate')
            ->twice()
            ->with($template)
            ->andReturn($parsedTemplate);

        $outfile = implode(DIRECTORY_SEPARATOR, [$destination, $file]);

        $outfileDir = pathinfo($outfile, PATHINFO_DIRNAME);
        $fs->shouldReceive('exists')
            ->twice()
            ->with($outfileDir)
            ->andReturn(false);

        $fs->shouldReceive('makeDirectory')
            ->twice()
            ->with($outfileDir, 0777, true)
            ->andReturn(true);

        $fs->shouldReceive('exists')
            ->once()
            ->with($outfile)->andReturn(false);

        $fs->shouldReceive('put')->once()
            ->with($outfile, $parsedTemplate)->andReturn(true);

        //first we test a file that does not exist
        $this->assertTrue($generator->make('order', $template, $destination, null, $mapper));

        //go ahead and assert on entity name
        $this->assertEquals('order', strtolower($generator->getEntityName()));

        $this->assertEquals(
            $parsedTemplate,
            $generator->getParsedTemplate()
        );

        $this->assertEquals(
            $outfile,
            $generator->getTemplateDestination()
        );

        //NOW SECOND TEST

        //now we mock that the file already exists
        $fs->shouldReceive('exists')
            ->once()
            ->with($outfile)->andReturn(true);

        //test making a file that exists, should return false
        $this->assertFalse($generator->make('order', $template, $destination, null, $mapper));

        //assert that the field data was parsed
        $this->assertEquals(
            $expected,
            $generator->getFieldData()
        );
    }

    public function testMakeWithCustomFilename()
    {
        $template = '/path/to/source/template.txt';
        $destTplPath = '/render/{{instance}}/template/here';
        $destination = '/render/order/template/here';
        $tplFileName = '{{Entity}}FileName.php';
        $prsFileName = 'OrderFileName.php';

        $outfile = implode(DIRECTORY_SEPARATOR, [$destination, $prsFileName]);

        $fs = m::mock('Illuminate\Filesystem\Filesystem');

        $outfileDir = pathinfo($outfile, PATHINFO_DIRNAME);
        $fs->shouldReceive('exists')
            ->once()
            ->with($outfileDir)
            ->andReturn(false);

        $fs->shouldReceive('makeDirectory')
            ->once()
            ->with($outfileDir, 0777, true)
            ->andReturn(true);

        $fs->shouldReceive('exists')
            ->once()
            ->with($outfile)
            ->andReturn(false);

        $fs->shouldReceive('put')
            ->once()
            ->with($outfile, "");

        $me = m::mock('Mustache_Engine');
        $me->shouldDeferMissing();

        $fp = m::mock('Parsers\FieldParser');
        $fp->shouldDeferMissing();

        $or = m::mock('Console\OptionReader');
        $or->shouldReceive('getFields')->andReturn([]);

        $generator = m::mock('Generators\Generator', array(
                $fs,
                $me,
                $fp,
                $or
            ));
        $generator->shouldDeferMissing();

        $generator->shouldReceive('getTemplate')
            ->once()
            ->with($template)
            ->andReturn("");

        $result = $generator->make('order', $template, $destTplPath, $tplFileName);

        $this->assertEquals(
            $prsFileName,
            $generator->getFileName()
        );
    }



    public function testGetTemplateVarsForSimpleEntity()
    {
        $generator = m::mock('Generators\Generator');
        $generator->shouldDeferMissing();

        $generator->shouldReceive('getEntityName')->once()
            ->andReturn('Order');

        $expected = [
            'Entity'     => 'Order',
            'Entities'   => 'Orders',
            'collection' => 'orders',
            'instance'   => 'order',
            'fields'     => [],
            'year'       => (new DateTime())->format('Y')
        ];

        $this->assertEquals($expected, $generator->getTemplateVars());
    }



    public function testGetTemplateVarsForSimpleEntityWithFields()
    {
        $generator = m::mock('Generators\Generator');
        $generator->shouldDeferMissing();

        $generator->shouldReceive('getEntityName')->once()
            ->andReturn('Order');

        $generator->shouldReceive('getFieldData')->once()
            ->andReturn(['name' => ['type' => 'string']]);

        $expected = [
            'Entity'     => 'Order',
            'Entities'   => 'Orders',
            'collection' => 'orders',
            'instance'   => 'order',
            'fields'     => ['name' => ['type' => 'string']],
            'year'       => (new DateTime())->format('Y')
        ];

        $this->assertEquals($expected, $generator->getTemplateVars());
    }



    public function testGetTemplateVarsForComplexEntity()
    {
        $generator = m::mock('Generators\Generator');
        $generator->shouldDeferMissing();

        $generator->shouldReceive('getEntityName')->once()
            ->andReturn('EcommerceOrderCreator');

        $expected = [
            'Entity'     => 'EcommerceOrderCreator',
            'Entities'   => 'EcommerceOrderCreators',
            'collection' => 'ecommerce_order_creators',
            'instance'   => 'ecommerce_order_creator',
            'fields'     => [],
            'year'       => (new DateTime())->format('Y')
        ];

        $this->assertEquals($expected, $generator->getTemplateVars());
    }



    public function testGetTemplate()
    {
        $templateText = '
            Nested{{Entity}}SomethingOrAnother
            CollectionOf{{Entities}}
            ${{collection}}->call();
            $this->{{instance}}->function();
        ';

        $parsedTemplate = '
            NestedOrderSomethingOrAnother
            CollectionOfOrders
            $orders->call();
            $this->order->function();
        ';

        $template = '/path/to/source/template.txt';

        $fp = m::mock('Parsers\FieldParser');
        $fp->shouldDeferMissing();

        $fs = m::mock('Illuminate\Filesystem\Filesystem');

        $fs->shouldReceive('get')->once()->with($template)->andReturn($templateText);

        $generator = m::mock('Generators\Generator', array(
                $fs,
                new Mustache_Engine,
                $fp,
                new OptionReader([])
            ));
        $generator->shouldDeferMissing();

        $generator->shouldReceive('getEntityName')->once()
            ->andReturn('Order');

        $result = $generator->getTemplate($template);
        $this->assertEquals(
            $parsedTemplate,
            $result
        );
    }
}
