<?php

namespace think\annotation;

use Doctrine\Common\Annotations\Reader;
use PhpDocReader\PhpDocReader;
use ReflectionObject;
use think\App;

/**
 * Trait InteractsWithInject
 * @package think\annotation\traits
 * @property App    $app
 * @property Reader $reader
 */
trait InteractsWithInject
{
    protected $docReader = null;

    protected function getDocReader()
    {
        if (empty($this->docReader)) {
            $this->docReader = new PhpDocReader();
        }

        return $this->docReader;
    }

    protected function autoInject()
    {
        if ($this->app->config->get('annotation.inject.enable', true)) {
            $this->app->resolving(function ($object) {

                if ($this->isInjectClass(get_class($object))) {

                    $reader = $this->getDocReader();

                    $refObject = new ReflectionObject($object);

                    foreach ($refObject->getProperties() as $refProperty) {

                        if ($refProperty->isDefault() && !$refProperty->isStatic()) {

                            $annotation = $this->reader->getPropertyAnnotation($refProperty, Inject::class);

                            if ($annotation) {
                                //获取@var类名
                                $propertyClass = $reader->getPropertyClass($refProperty);

                                if ($propertyClass) {
                                    if (!$refProperty->isPublic()) {
                                        $refProperty->setAccessible(true);
                                    }
                                    $refProperty->setValue($object, $this->app->make($propertyClass));
                                }
                            }
                        }
                    }
                }
            });
        }
    }

    protected function isInjectClass($name)
    {
        $namespaces = ['app\\'] + $this->app->config->get('annotation.inject.namespaces', []);

        foreach ($namespaces as $namespace) {
            $namespace = rtrim($namespace, '\\') . '\\';

            if (0 === stripos(rtrim($name, '\\') . '\\', $namespace)) {
                return true;
            }
        }
    }
}
