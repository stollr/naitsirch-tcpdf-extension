<?php

namespace Tcpdf\Extension\Attribute;

/**
 * Tcpdf\Extension\Attribute\BackgroundAttribute
 *
 * @author naitsirch
 */
class BackgroundAttribute extends AbstractAttribute
{
    private $color;
    private $image;
    private $dpi;
    private $formatter;

    public function getColor()
    {
        return $this->color;
    }

    /**
     * Get the background image.
     *
     * @return null|array <pre>null or array(
     *      'image' => Absolute filename of the image, binary file content or \SplFileInfo object
     *      'info' => array()
     * )</pre>
     */
    public function getImage()
    {
        return $this->image;
    }

    public function getDpi()
    {
        return $this->dpi;
    }

    public function getFormatter()
    {
        return $this->formatter;
    }

    /**
     * Set the background color.
     *
     * $cell->setBackgroundColor('#ffffff'); // hexadecimal CSS notation
     * $cell->setBackgroundColor(array(255, 255, 255));
     * $cell->setBackgroundColor(null); // this means transparent
     *
     * @param string|array|null $backgroundColor
     * @return element
     */
    public function setColor($color)
    {
        $this->color = $color;
        return $this;
    }

    /**
     * Set the background image.
     *
     * @param string|\SplFileInfo $backgroundImage Absolute filename of the image, binary file content or \SplFileInfo object
     * @param array $info
     * @return element
     */
    public function setImage($image)
    {
        $this->image = $image;
        return $this;
    }

    public function setDpi($dpi)
    {
        $this->dpi = $dpi;
        return $this;
    }

    /**
     * Set a formatter callable for the background. This allows you to
     * modify options of the image on the run.
     *
     * @param callable $formatter
     * @return element
     */
    public function setFormatter($formatter)
    {
        $this->formatter = $formatter;
        return $this;
    }
}
