<?php

namespace Creonit\PropelSchemaConverterBundle;


use Symfony\Component\Yaml\Yaml;

class SchemaConverter
{

    const EXTRA_TOKEN = '+';

    public function convert(\SplFileInfo $source)
    {
        $schema = Yaml::parse(file_get_contents($source->getRealPath()));

        $config = isset($schema['config']) ? $schema['config'] : [];
        $configRequired = isset($config['required']) ? (boolean) $config['required'] : false;

        $databaseXml = new \SimpleXMLElement('<database/>', XML_NOTATION_NODE);
        $database = $schema['database'];
        if (isset($database[self::EXTRA_TOKEN]) && is_array($database[self::EXTRA_TOKEN])) {
            foreach ($database[self::EXTRA_TOKEN] as $key => $value) {
                $databaseXml->addAttribute($key, $value);
            }
        }

        if (isset($database[self::EXTRA_TOKEN . 'vendor']) && is_array($database[self::EXTRA_TOKEN . 'vendor'])) {
            $vendorXml = $databaseXml->addChild('vendor');
            foreach ($database[self::EXTRA_TOKEN . 'vendor'] as $key => $value) {
                if('parameters' == $key){
                    foreach($value as $parameterKey => $parameterValue){
                        $vendorParameterXml = $vendorXml->addChild('parameter');
                        $vendorParameterXml->addAttribute('name', $parameterKey);
                        $vendorParameterXml->addAttribute('value', $parameterValue);
                    }
                }else{
                    $vendorXml->addAttribute($key, $value);
                }
            }
        }

        if (isset($database[self::EXTRA_TOKEN . 'behavior']) && is_array($database[self::EXTRA_TOKEN . 'behavior'])) {
            foreach ($database[self::EXTRA_TOKEN . 'behavior'] as $behavior) {
                $this->appendBehavior($databaseXml, $behavior);
            }
        }

        foreach ($database as $tableName => $table) {
            if (in_array($tableName, [self::EXTRA_TOKEN, self::EXTRA_TOKEN . 'behavior', self::EXTRA_TOKEN . 'vendor'])) continue;

            $tableXml = $databaseXml->addChild('table');
            $tableXml->addAttribute('name', $tableName);

            if (isset($table[self::EXTRA_TOKEN]) && is_array($table[self::EXTRA_TOKEN])) {
                foreach ($table[self::EXTRA_TOKEN] as $key => $value) {
                    $tableXml->addAttribute($key, $value);
                }
            }

            if (isset($table[self::EXTRA_TOKEN . 'vendor']) && is_array($table[self::EXTRA_TOKEN . 'vendor'])) {
                $vendorXml = $tableXml->addChild('vendor');
                foreach ($table[self::EXTRA_TOKEN . 'vendor'] as $key => $value) {
                    if('parameters' == $key){
                        foreach($value as $parameterKey => $parameterValue){
                            $vendorParameterXml = $vendorXml->addChild('parameter');
                            $vendorParameterXml->addAttribute('name', $parameterKey);
                            $vendorParameterXml->addAttribute('value', $parameterValue);
                        }
                    }else{
                        $vendorXml->addAttribute($key, $value);
                    }
                }
            }

            if (isset($table[self::EXTRA_TOKEN . 'behavior']) && is_array($table[self::EXTRA_TOKEN . 'behavior'])) {
                foreach ($table[self::EXTRA_TOKEN . 'behavior'] as $behavior) {
                    $this->appendBehavior($tableXml, $behavior);
                }
            }

            if (isset($table[self::EXTRA_TOKEN . 'index']) && is_array($table[self::EXTRA_TOKEN . 'index'])) {
                foreach ($table[self::EXTRA_TOKEN . 'index'] as $index) {
                    $index = (array) $index;
                    $indexXml = $tableXml->addChild('index');
                    foreach($index as $indexColumn){
                        if(preg_match('/^(\w+)\((\d+)\)$/', $indexColumn, $match)){
                            $indexColumnName = $match[1];
                            $indexColumnSize = $match[2];
                        }else{
                            $indexColumnName = $indexColumn;
                            $indexColumnSize = null;
                        }
                        $indexColumnXml = $indexXml->addChild('index-column');
                        $indexColumnXml->addAttribute('name', $indexColumnName);
                        if(null !== $indexColumnSize){
                            $indexColumnXml->addAttribute('size', $indexColumnSize);
                        }
                    }
                }
            }
            
            if (isset($table[self::EXTRA_TOKEN . 'unique']) && is_array($table[self::EXTRA_TOKEN . 'unique'])) {
                foreach ($table[self::EXTRA_TOKEN . 'unique'] as $unique) {
                    $unique = (array) $unique;
                    $uniqueXml = $tableXml->addChild('unique');
                    foreach($unique as $uniqueColumn){
                        if(preg_match('/^(\w+)\((\d+)\)$/', $uniqueColumn, $match)){
                            $uniqueColumnName = $match[1];
                            $uniqueColumnSize = $match[2];
                        }else{
                            $uniqueColumnName = $uniqueColumn;
                            $uniqueColumnSize = null;
                        }
                        $uniqueColumnXml = $uniqueXml->addChild('unique-column');
                        $uniqueColumnXml->addAttribute('name', $uniqueColumnName);
                        if(null !== $uniqueColumnSize){
                            $uniqueColumnXml->addAttribute('size', $uniqueColumnSize);
                        }
                    }
                }
            }

            if (isset($table[self::EXTRA_TOKEN . 'foreign-key']) && is_array($table[self::EXTRA_TOKEN . 'foreign-key'])) {
                foreach ($table[self::EXTRA_TOKEN . 'foreign-key'] as $key) {
                    $keyXml = $tableXml->addChild('foreign-key');
                    $keyXml->addAttribute('foreignTable', isset($key['foreignTable']) ? $key['foreignTable'] : key($key));
                    $key = array_pop($key);
                    $keyReferenceXml = $keyXml->addChild('reference');
                    $keyReferenceXml->addAttribute('local', isset($key['local']) ? $key['local'] : '');
                    $keyReferenceXml->addAttribute('foreign', isset($key['foreign']) ? $key['foreign'] : '');

                    foreach($key as $keyParameterName => $keyParameterValue){
                        if(in_array($keyParameterName, ['foreignTable', 'local', 'foreign'])) continue;
                        $keyXml->addAttribute($keyParameterName, $keyParameterValue);
                    }
                }
            }

            foreach ($table as $columnName => $column) {
                if (in_array($columnName, [self::EXTRA_TOKEN, self::EXTRA_TOKEN . 'behavior', self::EXTRA_TOKEN . 'index', self::EXTRA_TOKEN . 'unique', self::EXTRA_TOKEN . 'foreign-key', self::EXTRA_TOKEN . 'vendor'])) continue;

                $columnXml = $tableXml->addChild('column');
                $columnXml->addAttribute('name', $columnName);

                if (is_array($column)) {
                    if(!isset($column['required'])){
                        $column['required'] = $configRequired;
                    }

                    foreach ($column as $columnParameterName => $columnParameter) {
                        $columnXml->addAttribute($columnParameterName, $columnParameter);
                    }

                } else if (preg_match('/^(?P<required>[\-\+]?) *(?P<type>[a-z]+)\(?(?:(?P<size>\d+)(?:\,(?P<scale>\d+))?)?\)?(?:\[(?P<valueSet>[^]]+)\])?(?: +=(?P<default>["\'\S ]+?))?(?: +(?P<autoIncrement>~)?(?P<pk>pk)| +(?P<key>key)\(?(?P<keySize>\d+)?\)?| +(?P<uniq>uniq)\(?(?P<uniqSize>\d+)?\)?)?(?: +> +(?P<foreignTable>[a-z_]+)\.(?P<foreignColumn>[a-z_]+)(?:\((?P<foreignOnDelete>(?:cascade|setnull|restrict|none))(?: +(?P<foreignOnUpdate>(?:cascade|setnull|restrict|none)))?\))?)?$/ui', $column, $match)) {

                    $columnXml->addAttribute('type', $this->convertType($match['type']));

                    $required = $configRequired;
                    if (!empty($match['required'])) {
                        $required = $match['required'] == '+';
                    }

                    if (!empty($match['size'])) {
                        $columnXml->addAttribute('size', $match['size']);
                    }

                    if (!empty($match['scale'])) {
                        $columnXml->addAttribute('scale', $match['scale']);
                    }

                    if (!empty($match['valueSet'])) {
                        $columnXml->addAttribute('valueSet', $match['valueSet']);
                    }

                    if (!empty($match['foreignTable'])) {
                        $foreignXml = $tableXml->addChild('foreign-key');
                        $foreignXml->addAttribute('name', "fk_{$tableName}_{$columnName}_{$match['foreignTable']}");
                        $foreignXml->addAttribute('foreignTable', $match['foreignTable']);
                        $foreignXml->addAttribute('onDelete', !empty($match['foreignOnDelete']) ? $match['foreignOnDelete'] : ($required ? 'cascade' : 'setnull'));
                        $foreignXml->addAttribute('onUpdate', !empty($match['foreignOnUpdate']) ? $match['foreignOnUpdate'] : ('cascade'));
                        $foreignReferenceXml = $foreignXml->addChild('reference');
                        $foreignReferenceXml->addAttribute('local', $columnName);
                        $foreignReferenceXml->addAttribute('foreign', $match['foreignColumn']);

                    }

                    if (!empty($match['uniq'])) {
                        $uniqueXml = $tableXml->addChild('unique');
                        $uniqueXml->addAttribute('name', 'u_' . $columnName);
                        $uniqueColumnXml = $uniqueXml->addChild('unique-column');
                        $uniqueColumnXml->addAttribute('name', $columnName);
                        if (isset($match['uniqSize'])) {
                            $uniqueColumnXml->addAttribute('size', $match['uniqSize']);
                        }
                    }

                    if (!empty($match['key'])) {
                        $keyXml = $tableXml->addChild('index');
                        $keyXml->addAttribute('name', 'i_' . $columnName);
                        $keyColumnXml = $keyXml->addChild('index-column');
                        $keyColumnXml->addAttribute('name', $columnName);
                        if (isset($match['keySize'])) {
                            $keyColumnXml->addAttribute('size', $match['keySize']);
                        }
                    }

                    if (!empty($match['pk'])) {
                        $columnXml->addAttribute('primaryKey', 1);
                    }

                    if (!empty($match['autoIncrement'])) {
                        $columnXml->addAttribute('autoIncrement', 1);
                    }

                    $columnXml->addAttribute('required', $required);

                    if (!empty($match['default'])) {
                        $columnXml->addAttribute('defaultValue', trim($match['default'], "\"' "));
                    }

                } else {
                    $columnXml->addAttribute('type', $column);
                }


            }

        }

        $databaseXml->saveXML($source->getPath() . '/' . $source->getBasename($source->getExtension()) . 'xml');
    }

    protected function convertType($type){
        switch($type){
            case 'int':
                return 'integer';
            case 'text':
                return 'longvarchar';
            case 'bool':
                return 'boolean';
            case 'datetime':
                return 'timestamp';
            default:
                return $type;
        }
    }

    private function appendBehavior($xml, $behavior){
        $behaviorXml = $xml->addChild('behavior');
        if(is_array($behavior)){
            $behaviorXml->addAttribute('name', $behaviorName = key($behavior));
            $behavior = array_pop($behavior);
            if(is_array($behavior)){
                foreach ($behavior as $key => $value) {
                    $behaviorParameterXml = $behaviorXml->addChild('parameter');
                    $behaviorParameterXml->addAttribute('name', $key);
                    $behaviorParameterXml->addAttribute('value', $value);
                }
            }else{
                switch($behaviorName){
                    case 'i18n':
                    case 'l10n':
                        $behaviorParameterName = 'i18n_columns';
                        break;
                    default:
                        $behaviorParameterName = 'parameter';
                }
                $behaviorParameterXml = $behaviorXml->addChild('parameter');
                $behaviorParameterXml->addAttribute('name', $behaviorParameterName);
                $behaviorParameterXml->addAttribute('value', $behavior);
            }

        }else{
            $behaviorXml->addAttribute('name', $behavior);
        }
    }

} 