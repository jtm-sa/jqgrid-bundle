<?php
/**
 * @link https://github.com/himiklab/jqgrid-bundle
 * @copyright Copyright (c) 2018 HimikLab
 * @license http://opensource.org/licenses/MIT MIT
 */

namespace himiklab\JqGridBundle\Twig;

use Twig_Environment;
use Twig_Extension;
use Twig_Extension_GlobalsInterface;
use Twig_Function;

class HimiklabJqGridExtension extends Twig_Extension implements Twig_Extension_GlobalsInterface
{
    private $himiklabJqgridDateFormat;

    public function __construct($himiklabJqgridDateFormat)
    {
        $this->himiklabJqgridDateFormat = $himiklabJqgridDateFormat;
    }

    public function getFunctions(): array
    {
        return [
            new Twig_Function(
                'jqgrid_colmodel',
                [$this, 'prepareColmodel'],
                ['needs_environment' => true]
            ),
        ];
    }

    public function prepareColmodel(Twig_Environment $environment, array $columns, $columnsIsVisible = [],
                                    $columnsIsEditable = []): string
    {
        return $environment->render('@HimiklabJqGrid/colmodel.js.twig', [
            'columns' => $columns,
            'columnsIsVisible' => $columnsIsVisible,
            'columnsIsEditable' => $columnsIsEditable,
        ]);
    }

    public function getGlobals()
    {
        return ['himiklab_jqgrid_date_format' => $this->himiklabJqgridDateFormat];
    }
}
