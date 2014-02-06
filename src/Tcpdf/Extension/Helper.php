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
}
