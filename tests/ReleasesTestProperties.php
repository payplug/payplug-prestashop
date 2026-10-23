<?php

namespace PayPlug\tests;

/**
 * PHPUnit keeps every test case instance until the end of the run: releasing the
 * fixtures once a test is done keeps the suite's memory usage flat.
 */
trait ReleasesTestProperties
{
    protected function releaseTestProperties()
    {
        foreach ((new \ReflectionObject($this))->getProperties() as $property) {
            if ($property->isStatic() || 0 === strpos($property->getDeclaringClass()->getName(), 'PHPUnit\\')) {
                continue;
            }
            $property->setAccessible(true);
            $property->setValue($this, null);
        }
    }
}
