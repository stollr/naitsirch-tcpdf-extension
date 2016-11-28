<?php

namespace Tcpdf\Extension;

/**
 * Tcpdf\Extension\Helper
 *
 * @author naitsirch
 */
class Helper
{

    /**
     * Converts a hex RGB color string to a decimal RGB color array. If the given
     * value is empty or the string 'transparent' it will return NULL.
     *
     * @example convertColor('#ff00ff') => array(255, 0, 255)
     * @example convertColor('#aaa') => array(170, 170, 170)
     * @example convertColor(array(120, 50, 20)) => array(120, 50, 20)
     * @example convertColor('transparent') => null
     *
     * @param string|array $color
     * 
     * @return array|null
     */
    public static function convertColor($color)
    {
        if (empty($color) || 'transparent' === $color) {
            return null;
        }

        if (is_string($color)) {
            $color = ltrim($color, '#');
            while (strlen($color) < 6) {
                $color .= substr($color, -1);
            }
            return array_map('hexdec', str_split($color, 2));
        }

        return $color;
    }

    public static function getSizeInPixel($widthInUserUnit, $unit, $dpi)
    {
        $unit = strtolower($unit);
		switch ($unit) {
			case 'px':
			case 'pt': {
				return $widthInUserUnit;
			}
			case 'mm': {
				$inch = $widthInUserUnit / 25.4;
				break;
			}
			case 'cm': {
				$inch = $widthInUserUnit / 2.54;
				break;
			}
			case 'in': {
				$inch = 1;
				break;
			}
			default : {
				throw new \InvalidArgumentException('Invalid unit "'.$unit.'".');
			}
		}
        return $inch * $dpi;
    }

    /**
     * Returns the remaining page space from the given Y coordinate.
     *
     * @param \TCPDF $pdf
     * @param int $page
     * @param float $y
     * @return float Remaining Y space in user unit.
     */
    public static function getRemainingYPageSpace(\TCPDF $pdf, $page, $y)
    {
        // get total height of the page in user units
        $totalHeight = $pdf->getPageHeight($page) / $pdf->getScaleFactor();
        $margin = $pdf->getMargins();

        return $totalHeight - $margin['bottom'] - $y;
    }

    /**
     * Returns the usable page height, which is the page height without top and bottom margin.
     *
     * @param \TCPDF $pdf
     * @param int $page
     * @return float
     */
    public static function getPageContentHeight(\TCPDF $pdf, $page)
    {
        // get total height of the page in user units
        $totalHeight = $pdf->getPageHeight($page) / $pdf->getScaleFactor();
        $margin = $pdf->getMargins();

        return $totalHeight - $margin['bottom'] - $margin['top'];
    }
}
