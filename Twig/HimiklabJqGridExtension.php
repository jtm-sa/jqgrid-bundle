<?php
/**
 * @link https://github.com/himiklab/jqgrid-bundle
 * @copyright Copyright (c) 2018-2019 HimikLab
 * @license http://opensource.org/licenses/MIT MIT
 */

namespace himiklab\JqGridBundle\Twig;

use Twig\Environment;
use Twig\Extension\AbstractExtension;
use Twig\Extension\GlobalsInterface;
use Twig\TwigFunction;

class HimiklabJqGridExtension extends AbstractExtension implements GlobalsInterface
{
    private $himiklabJqgridDateFormat;

    public function __construct($himiklabJqgridDateFormat)
    {
        $this->himiklabJqgridDateFormat = $himiklabJqgridDateFormat;
    }

    public function getFunctions(): array
    {
        return [
            new TwigFunction(
                'jqgrid_colmodel',
                [$this, 'prepareColmodel'],
                ['needs_environment' => true]
            ),
        ];
    }

    public function prepareColmodel(Environment $environment, array $columns, array $columnsIsVisible = [],
                                    array $columnsIsEditable = []): string
    {
        return $environment->render('@HimiklabJqGrid/colmodel.js.twig', [
            'columns' => $columns,
            'columnsIsVisible' => $columnsIsVisible,
            'columnsIsEditable' => $columnsIsEditable,
        ]);
    }

    public function getGlobals(): array
    {
        return ['himiklab_jqgrid_date_format' => $this->himiklabJqgridDateFormat];
    }
}
