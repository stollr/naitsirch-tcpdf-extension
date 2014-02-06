<?php

namespace Tcpdf\Extension\Attribute;

/**
 * Tcpdf\Extension\Attribute\BackgroundFormatterOptions
 *
 * @author naitsirch
 */
class BackgroundFormatterOptions
{
    private $image;
    private $dpi;
    private $maxHeight;
    private $maxWidth;
    private $height;
    private $width;

    public function __construct($image, $maxWidth, $maxHeight)
    {
        $this->image = $image;
        $this->maxHeight = $this->height = $maxHeight;
        $this->maxWidth = $this->width = $maxWidth;
    }

    public function getImage()
    {
        return $this->image;
    }

    public function getMaxHeight()
    {
        return $this->maxHeight;
    }

    public function getMaxWidth()
    {
        return $this->maxWidth;
    }

    public function getHeight()
    {
        return $this->height;
    }

    public function getWidth()
    {
        return $this->width;
    }

    public function setImage($image)
    {
        $this->image = $image;
        return $this;
    }

    public function setHeight($height)
    {
        $this->height = $height;
        return $this;
    }

    public function setWidth($width)
    {
        $this->width = $width;
        return $this;
    }

    public function getDpi()
    {
        return $this->dpi;
    }

    public function setDpi($dpi)
    {
        $this->dpi = $dpi;
        return $this;
    }
}
