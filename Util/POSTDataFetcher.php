<?php
/**
 * @link https://github.com/himiklab/jqgrid-bundle
 * @copyright Copyright (c) 2018-2019 HimikLab
 * @license http://opensource.org/licenses/MIT MIT
 */

namespace himiklab\JqGridBundle\Util;

class POSTDataFetcher
{
    public function getPOSTData(): string
    {
        return \file_get_contents('php://input');
    }
}
