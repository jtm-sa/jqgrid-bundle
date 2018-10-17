<?php
/**
 * @link https://github.com/himiklab/jqgrid-bundle
 * @copyright Copyright (c) 2018 HimikLab
 * @license http://opensource.org/licenses/MIT MIT
 */

namespace himiklab\JqGridBundle\Util;

use Symfony\Component\HttpFoundation\Request;

class RequestHandler
{
    public function getRequestData(Request $request): array
    {
        if ($request->isMethod('post')) {
            return $this->getRealPOSTData();
        }
        if ($request->isMethod('get')) {
            return $request->query->all();
        }

        throw new \LogicException('Unsupported request method.');
    }

    private function getRealPOSTData(): array
    {
        $pairs = \explode('&', $this->getPOSTData());
        $vars = [];
        foreach ($pairs as $pair) {
            $pairParts = \explode('=', $pair);
            $name = \urldecode($pairParts[0]);

            $value = \urldecode($pairParts[1]);
            if (\preg_match('/(.+)\[\]$/', $name, $nameParts)) {
                $vars[$nameParts[1]][] = $value;
            } else {
                $vars[$name] = $value;
            }
        }

        return $vars;
    }

    protected function getPOSTData(): string
    {
        return \file_get_contents('php://input');
    }
}
