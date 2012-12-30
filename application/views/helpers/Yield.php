<?php
/**
 * Helper for making easy links and getting urls that depend on the routes and router
 *
 * @package    Zend_View
 * @subpackage Helper
 * @copyright  Copyright (c) 2005-2010 Zend Technologies USA Inc. (http://www.zend.com)
 * @license    http://framework.zend.com/license/new-bsd     New BSD License
 */
class Elm_View_Helper_Yield extends Zend_View_Helper_Url
{
	//private $_plot = null;

	//private $_yields = null;

    public function Yield()
	{
		return $this;
	}

	public function plotHasYields($plot)
	{
		$yield = Elm::getModel('yield')->fetchByPlot($plot);
		return count($yield) > 0;
	}

	public function getYieldCount($plotCrop)
	{
		$totalYield = 0;
		$yields = Elm::getModel('yield')->fetchByPlotCrop($plotCrop);
		foreach ($yields as $y) {
			$totalYield += (int) $y->getQuantity();
		}

		return $totalYield;
	}

	public function formatUnits($units, $qty)
	{
		switch ($units) {
			case 'individual':
				$units = 'individual item';
				if ($qty > 0) {
					$units = 'individual items';
				}
				break;
			case 'bundle':
			default:
				$units = 'bundle';
				if ($qty > 0) {
					$units = 'bundles';
				}
				break;
		}
		return $units;
	}
}