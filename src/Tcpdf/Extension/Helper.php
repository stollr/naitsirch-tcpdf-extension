<?php

namespace Tcpdf\Extension;

/**
 * Tcpdf\Extension\Helper
 *
 * @author naitsirch
 */
class Helper
{
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
