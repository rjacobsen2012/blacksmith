<?php namespace Parsers;


use Parsers\FieldsGenerator;

class FieldsGeneratorTest extends \BlacksmithTest
{
    public function testGetFields()
    {
        $fields = [
            'name' => [
                'type' => 'string',
                'required' => false,
                'read' => true,
                'write' => true
            ],
            'subdomain' => [
                'type' => 'string',
                'required' => true,
                'read' => true,
                'write' => true
            ],
            'created_at' => [
                'type' => '\Carbon\Carbon',
                'required' => true,
                'read' => true,
                'write' => true
            ]
        ];

        $expected = 'name:string:nullable, subdomain:string, created_at:timestamp';

        $fieldsGenerator = new FieldsGenerator();
        $this->assertEquals($expected, $fieldsGenerator->getFields($fields));
    }
}
