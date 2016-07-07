<?php

namespace Creonit\PropelSchemaConverterBundle;

use Symfony\Component\Finder\Finder;
use Symfony\Component\HttpKernel\Bundle\BundleInterface;

class SchemaLocator extends \Propel\Bundle\PropelBundle\Service\SchemaLocator
{

    public function locateFromBundles(array $bundles)
    {
        $converter = new SchemaConverter();

        foreach ($bundles as $bundle) {
            foreach ($this->locateSourcesFromBundle($bundle) as $source) {
                $converter->convert($source);
            }
        }

        return parent::locateFromBundles($bundles);
    }

    protected function locateSourcesFromBundle(BundleInterface $bundle){
        $finder = new Finder;
        $sources = [];

        if (is_dir($path = $bundle->getPath().'/Resources/config')) {
            $schemas = $finder->files()->name('*schema.yml')->followLinks()->in($path);

            if (iterator_count($schemas)) {
                foreach ($schemas as $schema) {
                    $logicalName = $this->transformToLogicalName($schema, $bundle);
                    $sources[] = new \SplFileInfo($this->fileLocator->locate($logicalName));
                }
            }
        }

        return $sources;
    }

}
