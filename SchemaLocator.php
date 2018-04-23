<?php

namespace Creonit\PropelSchemaConverterBundle;

use Symfony\Component\Finder\Finder;
use Symfony\Component\Finder\SplFileInfo;
use Symfony\Component\HttpKernel\Bundle\BundleInterface;

class SchemaLocator extends \Propel\Bundle\PropelBundle\Service\SchemaLocator
{

    public function locateFromBundle(BundleInterface $bundle)
    {
        $finalSchemas = [];
        $paths = [];
        $cacheDir = __DIR__ . '/../../../var/propel/schema';
        $configPath = $bundle->getPath() . '/Resources/config';

        if (!is_dir($cacheDir)) {
            mkdir($cacheDir, 0777, true);
        }

        if (is_dir($configPath)) {
            $paths[] = $configPath;
        }

        if ('AppBundle' == $bundle->getName() and is_dir($bundle->getPath() . '/../../app/config')) {
            $paths[] = $bundle->getPath() . '/../../app/config';
        }

        if ('CreonitPropelSchemaConverterBundle' == $bundle->getName() and is_dir($bundle->getPath() . '/../../../config')) {
            $paths[] = $bundle->getPath() . '/../../../config';
        }

        if ($paths) {
            $converter = new SchemaConverter();

            $schemas = (new Finder)->files()->name('*schema.xml')->followLinks()->in($paths);
            if (iterator_count($schemas)) {
                foreach ($schemas as $schema) {
                    $logicalName = $this->transformToLogicalName($schema, $bundle);
                    $finalSchema = new \SplFileInfo($this->fileLocator->locate($logicalName));
                    $finalSchemas[(string)$finalSchema] = [$bundle, $finalSchema];
                }
            }

            $schemas = (new Finder)->files()->name('*schema.yml')->name('*schema.yaml')->followLinks()->in($paths);
            if (iterator_count($schemas)) {
                /** @var SplFileInfo $schema */
                foreach ($schemas as $schema) {
                    $target = $cacheDir . '/' . $bundle->getName() . '_' . $schema->getBasename($schema->getExtension()) . 'xml';
                    $converter->convert($schema, $target);
                    $finalSchema = new \SplFileInfo($this->fileLocator->locate($target));
                    $finalSchemas[(string)$finalSchema] = [$bundle, $finalSchema];
                }
            }
        }

        return $finalSchemas;
    }

}
