<?php

namespace himiklab\JqGridBundle\Tests;

class Entity
{
    public $attributePublic;

    private $attributePrivate;

    /** @var self */
    public $nestedEntity;

    public function getAttributePrivate()
    {
        return $this->attributePrivate;
    }

    public function setAttributePrivate($value)
    {
        $this->attributePrivate = $value;
    }
}
