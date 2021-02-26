<?php

namespace App\Http\Controllers\Admin;

use App\Http\Requests\QuestionRequest;
use App\Models\Question;
use Backpack\CRUD\app\Http\Controllers\CrudController;
use Backpack\CRUD\app\Library\CrudPanel\CrudPanelFacade as CRUD;

/**
 * Class QuestionCrudController
 * @package App\Http\Controllers\Admin
 * @property-read \Backpack\CRUD\app\Library\CrudPanel\CrudPanel $crud
 */
class QuestionCrudController extends CrudController
{
    use \Backpack\CRUD\app\Http\Controllers\Operations\ListOperation;
    use \Backpack\CRUD\app\Http\Controllers\Operations\CreateOperation;
    use \Backpack\CRUD\app\Http\Controllers\Operations\UpdateOperation;
    use \Backpack\CRUD\app\Http\Controllers\Operations\DeleteOperation;
    use \Backpack\CRUD\app\Http\Controllers\Operations\ShowOperation;

    /**
     * Configure the CrudPanel object. Apply settings to all operations.
     *
     * @return void
     */
    public function setup()
    {
        CRUD::setModel(\App\Models\Question::class);
        CRUD::setRoute(config('backpack.base.route_prefix') . '/question');
        CRUD::setEntityNameStrings('question', 'questions');

        CRUD::operation('list', function() {
            CRUD::removeButton('create');
            CRUD::removeButton('update');
        });
        CRUD::operation('show', function() {
            CRUD::removeButton('update');
            CRUD::removeButton('delete');
        });
        $this->crud->addFilter([
            'type'  => 'text',
            'name'  => 'id',
            'label' => 'Id'
        ],
            false,
            function($value) { // if the filter is active
                $this->crud->addClause('where', 'id', $value);
            });
        $this->crud->addFilter([
            'type'  => 'text',
            'name'  => 'question',
            'label' => 'Câu hỏi'
        ],
            false,
            function($value) { // if the filter is active
                $this->crud->addClause('where', 'question', 'LIKE', "%$value%");
            });

        $this->crud->addFilter([
            'name'  => 'status',
            'type'  => 'dropdown',
            'label' => 'Trạng thái'
        ], Question::getStatuses(), function($value) { // if the filter is active
            $this->crud->addClause('where', 'status', $value);
        });
        $this->crud->addFilter([
            'name'  => 'site',
            'type'  => 'dropdown',
            'label' => 'Site'
        ], Question::getSite(), function($value) { // if the filter is active
            $this->crud->addClause('where', 'site', $value);
        });

    }

    /**
     * Define what happens when the List operation is loaded.
     *
     * @see  https://backpackforlaravel.com/docs/crud-operation-list-entries
     * @return void
     */
    protected function setupListOperation()
    {
        //CRUD::setFromDb(); // columns
        $this->crud->removeButton('delete');
        $this->crud->addColumn([
            'name' => 'question',
            'type' => 'text',
            'label' => 'Câu hỏi',
        ]);
        $this->crud->addColumn([
            'name' => 'site',
            'type' => 'text',
            'label' => 'Site',
        ]);

        $this->crud->addColumn([
            'name'    => 'status',
            'label'   => 'Trạng thái',
            'type'    => 'radio',
            'options' =>  Question::getStatuses(), // optional
            'wrapper' => [
                'element' => 'span',
                'class' => function ($crud, $column, $entry, $related_key) {
                    if ($column['text'] == Question::getStatuses()[Question::STATUS_QUESTION_DEFAULT]) {
                        return 'badge badge-default';
                    }elseif ($column['text'] == Question::getStatuses()[Question::STATUS_QUESTION_UPLOAD]){
                        return 'badge badge-success';
                    }elseif ($column['text'] == Question::getStatuses()[Question::STATUS_QUESTION_ERROR]){
                        return 'badge badge-warning';
                    }else{
                        return 'badge badge-default';
                    }

                },
            ],
        ]);

        $this->crud->addColumn([
            'name'     => 'created_at',
            'label'    => 'Ngày crawl',
            'type'     => 'closure',
            'function' => function($entry) {
                return $entry->created_at;
            }
        ]);
        /**
         * Columns can be defined using the fluent syntax or array syntax:
         * - CRUD::column('price')->type('number');
         * - CRUD::addColumn(['name' => 'price', 'type' => 'number']);
         */
    }

    /**
     * Define what happens when the Create operation is loaded.
     *
     * @see https://backpackforlaravel.com/docs/crud-operation-create
     * @return void
     */
    protected function setupCreateOperation()
    {
        CRUD::setValidation(QuestionRequest::class);

        CRUD::setFromDb(); // fields

        /**
         * Fields can be defined using the fluent syntax or array syntax:
         * - CRUD::field('price')->type('number');
         * - CRUD::addField(['name' => 'price', 'type' => 'number']));
         */
    }

    /**
     * Define what happens when the Update operation is loaded.
     *
     * @see https://backpackforlaravel.com/docs/crud-operation-update
     * @return void
     */
    protected function setupUpdateOperation()
    {
        $this->setupCreateOperation();
    }
}
