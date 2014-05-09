<?php namespace Parsers;

class FieldsGenerator
{

    public static function getFields($fields)
    {

        $fieldString = "";

        foreach ($fields as $name => $properties) {

            if ($fieldString) {
                $fieldString .= ", ";
            }

            $type = $properties['type'];

            if ($type == '\Carbon\Carbon') {
                $type = "timestamp";
            }

            $nullable = "";

            if (! $properties['required']) {
                $nullable = ":nullable";
            }

            $fieldString .= "$name:$type$nullable";

        }

        return $fieldString;

    }

}