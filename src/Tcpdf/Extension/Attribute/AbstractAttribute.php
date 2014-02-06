<?php

namespace Tcpdf\Extension\Attribute;

/**
 * Tcpdf\Extension\Attribute\AbstractAttribute
 *
 * @author naitsirch
 */
abstract class AbstractAttribute
{
    private $element;

    public function __construct($element)
    {
        $this->element = $element;
    }

    public function end()
    {
        return $this->element;
    }
}
