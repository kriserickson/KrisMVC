<?php
/*
 * This file is part of the KrisMvc framework.
 *
 * (c) Kris Erickson 
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */
 
/**
 * Shows scaffolding
 *
 * @throws Exception
 *
 */
class ScaffoldController
{

    /**
     * @var KrisView
     */
    protected $view;

    /**
     * @var int
     */
    protected $pageSize = 20;

    /**
     * @var bool
     */
    private $displayTables = false;

    /**
     * @param string $action 
     * @param array $params
     */
    function __construct($action, $params)
    {
        $this->view = new KrisView(KrisConfig::APP_PATH . 'views/layouts/scaffold.php');

        $tables = array();

        $dir = dir(KrisConfig::APP_PATH.'/models/crud');

        while (false !== ($entry = $dir->read()) )
        {
            $pathInfo = pathinfo($entry);
            $filename = $pathInfo['filename'];
            if ($pathInfo['extension'] == 'php' && substr($filename, -4) == 'View');
            {
                $tables[$filename] = substr($filename, 0,  -4);
            }
        }

        $this->view->set('tables', $tables);



        if (count($params) > 0)
        {
            $base_href = KrisConfig::WEB_FOLDER.'/scaffold/';
            $this->view->set('display_base_href', $base_href.'index/');
            $this->view->set('display_href', $base_href.'index/'.$params[0]);
            $this->view->set('view_href', $base_href.'view/'.$params[0]);
            $this->view->set('change_href', $base_href.'change/'.$params[0]);
            $this->view->set('delete_href', $base_href.'delete/'.$params[0]);
            $this->displayTables = true;
        }

    }

    /**
     * @param string $class
     * @internal string $sort = func_get_arg(1);
     * @internal string $ascending = func_get_arg(2)
     * @return void
     */
    public function Index($class)
    {

        if ($this->displayTables)
        {

             // Dynamically create the class...
            if (class_exists($class))
            {
                // Make sure it is valid and has a proper model...
                $class = new $class();
                if (!is_subclass_of($class, 'KrisCrudModel'))
                {
                    throw new Exception('Invalid crud class: ' . get_class($class));
                }
            }
            else
            {
                throw new Exception('Invalid class: ' . $class);
            }

            /** @var $class KrisCrudModel */

            $fields = $class->GetDisplayAndDatabaseFields();

             // Get sort and sort order
            $ascending = true;
            if (func_num_args() > 1)
            {
                $sort = func_get_arg(1);
                if (func_num_args() > 2)
                {
                    $ascending = func_get_arg(2) == 'asc';
                }
            }
            else
            {
                $sort = key($fields);
            }

            $this->SetupIndexView($class, $ascending, $sort, $fields);


        }

        $this->view->dump();
    }

    /**
     * SetupIndexView - Sets up the Index View
     *
     * @throws Exception
     * @param KrisCrudModel $class
     * @param bool $ascending
     * @param string $sort
     * @param array $fields
     * @return void
     */
    public function SetupIndexView($class, $ascending, $sort, $fields)
    {


        // Get search, where and bindings from the post variables...
        list($bindings, $where, $search) = $this->GetSearchFromPostVars($fields);

        // Get total records from the table...
        $totalRecords = $class->totalRecords($where, $bindings, true);

        $numPages = ceil($totalRecords / $this->pageSize);

        //  Get the current paging location from the post vars.
        $page = $this->GetPageFromPostVars($numPages);


        // Set variables in the view...
        $this->view->set('display_table', true);
        $this->view->set('display_name', $class->DisplayName);
        $this->view->set('columns', $fields);
        $this->view->set('sorted', array());
        $this->view->set('number_of_pages', $numPages);
        $this->view->set('total_records', $totalRecords);
        $this->view->set('current_page', $page);
        $this->view->set('sort_ascending', $ascending);
        $this->view->set('search', $search);


        $this->view->set('models', $class->retrieveMultiple($where, $bindings, true, $this->pageSize, $page * $this->pageSize, $sort, $ascending));
    }

    /**
     * @return void
     */
    public function View()
    {

    }

    /**
     * Gets the search from Post Vars...
     *
     * @param $fields
     * @return array
     */
    protected function GetSearchFromPostVars($fields)
    {
        $bindings = array();
        $where = array();
        if (isset($_POST['search']))
        {
            $search = true;
            return array($bindings, $where, $search);
        }
        else if (isset($_POST['query']))
        {
            $search = array();
            foreach (array_keys($fields) as $field_id)
            {
                $search_field = 'search_' . $field_id;
                if (strlen($_POST[$search_field]) > 0)
                {
                    $bindings[] = $search[$field_id] = $_POST[$search_field];
                    $where[] = $field_id;
                }
            }
            return array($bindings, $where, $search);
        }
        else
        {
            $search = false;
            return array($bindings, $where, $search);
        }
    }

    /**
     * Gets the current page from the post vars..
     *
     * @param int $numPages
     * @return int
     */
    protected function GetPageFromPostVars($numPages)
    {
        $page = isset($_POST['current_page']) ? $_POST['current_page'] : 0;

        if (isset($_POST['next_page']))
        {
            $page++;
        }
        else if (isset($_POST['first_page']))
        {
            $page = 0;
        }
        else if (isset($_POST['prev_page']))
        {
            $page--;
        }
        else if (isset($_POST['last_page']))
        {
            $page = $numPages - 1;
        }
        else if (isset($_POST['goto']))
        {
            $page = $_POST['goto'];

        }
        return $page;
    }
}
