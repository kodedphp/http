<?php

namespace Tests\Koded\Http;

trait AssertionTestSupportTrait
{
    /**
     * @param object|string $objectOrString
     * @param string        $propertyName
     *
     * @return mixed
     * @throws \ReflectionException
     */
    private function getObjectProperty(
        object|string $objectOrString,
        string $propertyName): mixed
    {
        try {
            $proto    = new \ReflectionClass($objectOrString);
            $property = $proto->getProperty($propertyName);
            $property->setAccessible(true);
            return $property->getValue($objectOrString);
        } catch (\ReflectionException $e) {
            $this->markTestSkipped('[Reflection Error: ' . $e->getMessage());
        }
    }

    /**
     * @param object|string $objectOrString
     * @param array         $propertyNames List of property names, or empty for all properties
     *
     * @return array
     */
    private function getObjectProperties(
        object|string $objectOrString,
        array $propertyNames = []): array
    {
        try {
            $properties = (new \ReflectionClass($objectOrString))->getProperties();

            if (count($propertyNames) > 0) {
                $properties = array_filter($properties, function(\ReflectionProperty $property) use ($propertyNames) {
                    return in_array($property->getName(), $propertyNames);
                });
            }

            $propertyKeys = array_map(function(\ReflectionProperty $property) {
                return $property->getName();
            }, $properties);

            return array_combine($propertyKeys, array_map(function(\ReflectionProperty $property) use($objectOrString) {
                $property->setAccessible(true);
                return $property->getValue($objectOrString);
            }, $properties));

        } catch (\ReflectionException $e) {
            $this->markTestSkipped('[Reflection Error: ' . $e->getMessage());
        }
    }
}
