<?php
/**
 * @link https://github.com/himiklab/jqgrid-bundle
 * @copyright Copyright (c) 2018-2019 HimikLab
 * @license http://opensource.org/licenses/MIT MIT
 */

namespace himiklab\JqGridBundle\Util;

use Symfony\Component\Serializer\Encoder\JsonDecode;
use Symfony\Component\Serializer\Encoder\JsonEncoder;

class SearchFiltersDecoder
{
    public function decode(string $filtersData): array
    {
        return (new JsonDecode())->decode($filtersData, JsonEncoder::FORMAT, ['json_decode_associative' => true]);
    }
}
