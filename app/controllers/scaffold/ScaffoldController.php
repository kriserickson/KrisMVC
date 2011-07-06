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
            if ($pathInfo['extension'] == 'php' && substr($filename, -4) == 'View')
            {
                // Convert the filename like SomeTableView.php into "Some Table"
                $tableName = substr($filename, 0,  -4);
                $tables[$filename] = $tableName[0].preg_replace('/[A-Z]/', ' $0',substr($tableName,1));
            }
        }

        $this->view->set('tables', $tables);
        $this->view->set('table_width', (int)(100 / count($tables)));

        $this->base_href = KrisConfig::WEB_FOLDER.'/scaffold/';
        $this->view->set('display_base_href', $this->base_href.'index/');


    }

    /**
     * @param string $className
     * @internal string $sort = func_get_arg(1);
     * @internal string $ascending = func_get_arg(2)
     * @return void
     */
    public function Index($className = null)
    {
        if (isset($_POST['add']))
        {
            $this->Add($className);
            return;
        }

        if (!is_null($className ))
        {
            $class = $this->GenerateClass($className);

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
                if (isset($_POST['sort_field']))
                {
                    $sort =    $_POST['sort_field'];
                    $ascending = $_POST['sort_order'] == 'asc';
                }
                else
                {
                    $sort = $class->SortField ? $class->SortField : key($fields);
                }
            }

            $this->view->set('display_name', $class->DisplayName);
            $this->view->set('body', $this->GetIndexView($class, $ascending, $sort, $fields));
        }
        else
        {
            $this->view->set('display_name', 'Choose Table To Edit');
            $this->view->set('body', '');
        }

        $this->view->dump();
    }


    /**
     * @param string $className
     * @param int $id
     * @return void
     */
    public function View($className, $id)
    {
        if (isset($_POST['changeButton']))
        {
            $this->Change($className, $id);
        }
        else if (isset($_POST['cancelButton']))
        {
            $this->Index($className);
        }
        else
        {
            $class = $this->GenerateClass($className);
            $class->retrieve($id);

            $data = array('display_name' => $class->DisplayName, 'fields' => $class->GetDisplayAndDatabaseFields(), 'class' => $class,
                'form_href' => $this->base_href.'view/'.$className.'/'.$id,
                 'changeDeleteButton' => '<button id="changeButton">Change</button>');

            $this->view->set('display_name', 'View '.$class->DisplayName);
            $this->view->set('body', $this->view->fetch(KrisConfig::APP_PATH . 'views/scaffold/view.php', $data, false));

            $this->view->dump();
        }
    }

    /**
     * @param string $className
     * @param int $id
     * @return void
     */
    public function Delete($className, $id)
    {
        $class = $this->GenerateClass($className);
        $class->retrieve($id);

        if (isset($_POST['deleteButton']))
        {
            $class->delete();
            $this->Index($className);
        }
        else if (isset($_POST['cancelButton']))
        {
            $this->Index($className);
        }
        else
        {

            $data = array('display_name' => 'Delete '.$class->DisplayName, 'fields' => $class->GetDisplayAndDatabaseFields(), 'class' => $class,
                'form_href' => $this->base_href.'delete/'.$className.'/'.$id,
                 'changeDeleteButton' => '<button name="deleteButton" id="deleteButton">Delete</button>');

            $this->view->set('display_name', 'View '.$class->DisplayName);
            $this->view->set('body', $this->view->fetch(KrisConfig::APP_PATH . 'views/scaffold/view.php', $data, false));

            $this->view->dump();
        }
    }

    /**
     * @param $className
     * @param $id
     * @return void
     */
    public function Change($className, $id)
    {
        if (isset($_POST['cancelButton']))
        {
            // Show the tables index...
            $this->Index($className);
            return;
        }

        $class = $this->GenerateClass($className);
        $class->retrieve($id);

        if (isset($_POST['saveButton']))
        {
            $this->saveClass($class);
            $this->Index($className);
            return;
        }
        if (isset($_POST['applyButton']))
        {
            $this->saveClass($class);
        }

        $data = array('display_name' => 'Edit '.$class->DisplayName, 'fields' => $class->GetDisplayAndDatabaseFields(), 'class' => $class,
            'display_href' => $this->base_href.'index/'.$className, 'change_href' => $this->base_href.'change/'.$className.'/'.$id);

        $this->view->set('display_name', 'Edit '.$class->DisplayName);
        $this->view->set('body', $this->view->fetch(KrisConfig::APP_PATH . 'views/scaffold/edit.php', $data, false));

        $this->view->dump();
    }

    /**
     * @param $className
     * @return void
     */
    public function Add($className)
    {
        $class = $this->GenerateClass($className);

        if (isset($_POST['saveButton']))
        {
            $this->getUpdatedFields($class);
            $class->create();
        }

        $data = array('display_name' => 'Add '.$class->DisplayName, 'fields' => $class->GetDisplayAndDatabaseFields(), 'class' => $class,
            'display_href' => $this->base_href.'index/'.$className, 'change_href' => $this->base_href.'add/'.$className);

        $this->view->set('display_name', 'Add '.$class->DisplayName);
        $this->view->set('body', $this->view->fetch(KrisConfig::APP_PATH . 'views/scaffold/edit.php', $data, false));

        $this->view->dump();
    }


    /**
     * @throws Exception
     * @param string $className
     * @return null|KrisCrudModel
     */
    public function GenerateClass($className)
    { // Dynamically create the class...
        if (class_exists($className))
        {
            // Make sure it is valid and has a proper model...
            $class = new $className();
            if (!is_subclass_of($class, 'KrisCrudModel'))
            {
                throw new Exception('Invalid crud class: ' . get_class($class));
            }
            return $class;
        }
        else
        {
            throw new Exception('Invalid class: ' . $className);
        }


    }

    /**
     * SetupIndexView - Sets up the Index View
     *
     * @throws Exception
     * @param KrisCrudModel $class
     * @param bool $ascending
     * @param string $sort
     * @param array $fields
     * @return string
     */
    public function GetIndexView($class, $ascending, $sort, $fields)
    {
        // Get search, where and bindings from the post variables...
        list($bindings, $where, $search, $searchValues) = $this->getSearchFromPostVars($fields);

        // Get total records from the table...
        $totalRecords = $class->totalRecords($where, $bindings, true);

        $numPages = ceil($totalRecords / $this->pageSize);

        //  Get the current paging location from the post vars.
        $page = $this->getPageFromPostVars($numPages);

        $className = get_class($class);

        // Set variables in the view...
        $data = array('display_table' => true ,'display_name' => $class->DisplayName ,'columns' => $fields ,
            'sorted' => array(),'number_of_pages' => $numPages ,'total_records' => $totalRecords,'current_page' => $page,
            'sort_ascending' => $ascending ,'search' => $search, 'display_href' => $this->base_href.'index/'.$className,
            'view_href' => $this->base_href.'view/'.$className, 'change_href' => $this->base_href.'change/'.$className,
            'delete_href' => $this->base_href.'delete/'.$className, 'search_values' => $searchValues,
            'sort_field' => $sort, 'sort_order' => $ascending ? 'asc' : 'dec',
            'models'=> $class->retrieveMultiple($where, $bindings, true, $this->pageSize, $page * $this->pageSize, $sort, $ascending));

        return $this->view->fetch(KrisConfig::APP_PATH . 'views/scaffold/index.php', $data, false);
            
    }

    /**
     * Gets the search from Post Vars...
     *
     * @param $fields
     * @return array
     */
    protected function getSearchFromPostVars($fields)
    {
        $bindings = array();
        $where = array();
        $searchValues = array();

        if (isset($_POST['search']))
        {
            $search = true;
        }
        else
        {

            foreach (array_keys($fields) as $field_id)
            {
                $search_field = 'search_' . $field_id;
                if (isset($_POST[$search_field]) && strlen($_POST[$search_field]) > 0)
                {
                    $bindings[] = $searchValues[$field_id] = $_POST[$search_field];
                    $where[] = $field_id;
                }
            }
            $search = count($searchValues) > 0;
        }

        return array($bindings, $where, $search, $searchValues);

    }

    /**
     * Gets the current page from the post vars..
     *
     * @param int $numPages
     * @return int
     */
    protected function getPageFromPostVars($numPages)
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

    /**
     * @param $class KrisCrudModel
     * @return void
     */
    private function saveClass($class)
    {
        $updatedFields = $this->getUpdatedFields($class);

        if (count($updatedFields) > 0)
        {
            $class->UpdateSelectedFields($updatedFields);
        }

    }

    /**
     * @param $class KrisCrudModel
     * @return array
     */
    private function getUpdatedFields($class)
    {
        $fields = $class->GetDatabaseFields();
        $updatedFields = array();
        foreach ($fields as $field)
        {
            if (isset($_POST[$field]))
            {
                $postedVar = $_POST[$field];
                if ($class->get($field) != $postedVar)
                {
                    $class->set($field, $postedVar);
                    $updatedFields[$field] = $postedVar;
                }
            }
        }
        return $updatedFields;
    }
}
