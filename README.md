jqGrid Bundle for Symfony
========================
Symfony bundle for a powerful ajax-enabled grid - [jqGrid](https://github.com/tonytomov/jqGrid) or [free jqGrid](https://github.com/free-jqgrid/jqGrid).

[![Packagist](https://img.shields.io/packagist/dt/jtmsa/jqgrid-bundle.svg)]() [![Packagist](https://img.shields.io/packagist/v/jtmsa/jqgrid-bundle.svg)]()  [![license](https://img.shields.io/badge/License-MIT-yellow.svg)]()

Installation
------------
The preferred way to install this extension is through [composer](http://getcomposer.org/download/).

* Either run

```
php composer.phar require --prefer-dist "himiklab/jqgrid-bundle" "*"
```

or add

```json
"himiklab/jqgrid-bundle" : "*"
```

to the require section of your application's `composer.json` file. And registered `HimiklabJqGridBundle` in you application config.

* Assets

reqired: `jqgrid` or `free-jqgrid` with `jquery` of course
optional: `@fortawesome/fontawesome-free` or `jquery-ui`, `bootstrap`

* Template

```twig
{% block stylesheets %}
    {{ encore_entry_link_tags('jqgrid') }}
{% endblock %}
{% block body %}
    <table id="jqGrid-grid"></table>
    <div id="jqGrid-pager"></div>
{% endblock %}
{% block javascripts %}
    {{ encore_entry_script_tags('jqgrid') }}
    <script>
        $(document).ready(function () {
            $("#jqGrid-grid").jqGrid({
                //"guiStyle": "bootstrap4",
                "url": "{{ path('customer_jqgrid_read') }}",
                "datatype": "json",
                "mtype": "post",
                "pager": "#jqGrid-pager",
                "colNames": ["ID", "Name", "Surname", "Birthplace", "Birthdate"],
                "colModel": [
                    {{ jqgrid_colmodel(columns, columnsIsVisible, columnsIsEditable)|raw }}
                ],
                "rowNum": 30,
                "rowList": [30, 60],
                "multiselect": true,
                "multiSort": true,
                "viewrecords": true
                //"iconSet": "fontAwesomeSolid"
            })
                .navGrid('#jqGrid-pager', {
                        "edit": true,
                        "add": true,
                        "del": true,
                        "search": true,
                        "view": true
                    },
                    {
                        "url": "{{ path('customer_jqgrid_update') }}",
                        "afterSubmit": function (response) {
                            return [response.responseText === "", response.responseText, null];
                        }
                    },
                    {
                        "url": "{{ path('customer_jqgrid_create') }}",
                        "afterSubmit": function (response) {
                            return [response.responseText === "", response.responseText, null];
                        }
                    },
                    {"url": "{{ path('customer_jqgrid_delete') }}"},
                    {
                        "multipleSearch": true,
                        "multipleGroup": true,
                        "closeAfterSearch": true,
                        "showQuery": true,
                    },
                    {}
                )
                .filterToolbar({"stringResult": true});
        });
    </script>
{% endblock %}
```

* Controller
```php
use himiklab\JqGridBundle\JqGrid;

class CustomerController extends AbstractController
{
    private $jqgrid;

    public function __construct(JqGrid $jqgrid)
    {
        $this->jqgrid = $jqgrid
            ->setEntityName(Customer::class);
    }

    /**
     * @Route("/jqgrid", methods={"GET"})
     */
    public function index(): Response
    {
        $columns = [
            'id' => ['type' => 'int',],
            'fullName.name', 'fullName.surname', 'birthplace',
            'birthdate' => ['type' => 'date']
        ];
        $columnsIsVisible = ['id', 'fullName.name', 'fullName.surname', 'birthplace', 'birthdate'];
        $columnsIsEditable = ['fullName.name', 'fullName.surname', 'birthplace', 'birthdate'];

        return $this->render(
            'incoming/index.html.twig',
            ['columns' => $columns, 'columnsIsVisible' => $columnsIsVisible, 'columnsIsEditable' => $columnsIsEditable]
        );
    }

    /**
     * @Route("/jqgrid/read", methods={"POST"}, name="customer_jqgrid_read")
     */
    public function read(Request $request): Response
    {
        return $this->jqgrid->handleRead($request);
    }

    /**
     * @Route("/jqgrid/create", methods={"POST"}, name="customer_jqgrid_create")
     */
    public function create(Request $request): Response
    {
        return $this->jqgrid->handleCreate($request) ?: new Response();
    }

    /**
     * @Route("/jqgrid/update", methods={"POST"}, name="customer_jqgrid_update")
     */
    public function update(Request $request): Response
    {
        return $this->jqgrid->handleUpdate($request) ?: new Response();
    }

    /**
     * @Route("/jqgrid/delete", methods={"POST"}, name="customer_jqgrid_delete")
     */
    public function delete(Request $request): Response
    {
        $this->jqgrid->handleDelete($request);
        return new Response();
    }
}
```
Advices
------------
Non Camel Case is not supported

