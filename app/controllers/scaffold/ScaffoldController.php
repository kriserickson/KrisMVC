<?php
/*
 * This file is part of the KrisMvc framework.
 *
 * (c) Kris Erickson 
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */
 
class ScaffoldController
{

    protected $view;
    protected $pageSize = 20;

    function __construct($action, $params)
    {
        $this->view = new KrisView(KrisConfig::APP_PATH . 'views/layouts/scaffold.php');
        $this->view->set('displayHref', KrisConfig::WEB_FOLDER.'/scaffold/display/'.$params[0]);
        $this->view->set('viewHref', KrisConfig::WEB_FOLDER.'/scaffold/view/'.$params[0]);
        $this->view->set('changeHref', KrisConfig::WEB_FOLDER.'/scaffold/change/'.$params[0]);
        $this->view->set('deleteHref', KrisConfig::WEB_FOLDER.'/scaffold/delete/'.$params[0]);
    }

    public function Display($class)
    {
        if (class_exists($class))
        {
            $class = new $class();
            if (!is_subclass_of($class, 'KrisCrudModel'))
            {
                throw new Exception('Invalid crud class: '.get_class($class));
            }
        }
        else
        {
            throw new Exception('Invalid class: '.$class);
        }

        /** @var $class KrisCrudModel */
        $fields = $class->GetDisplayAndDatabaseFields();
        $totalRecords = $class->totalRecords();
        $numPages = ceil($totalRecords / $this->pageSize);

        if (func_num_args() > 1)
        {
            $sort = func_get_arg(1);
        }
        //$sortArray = isset($_POST['sort'])

        $page = $this->GetPageFromPostVars($numPages);

        $this->view->set('displayName',$class->DisplayName);
        $this->view->set('columns', $fields);
        $this->view->set('sorted', array());
        $this->view->set('number_of_pages', $numPages);
        $this->view->set('total_records', $totalRecords);
        $this->view->set('current_page', $page);


        $this->view->set('models', $class->retrieveMultiple(array(), array(), $this->pageSize, key($fields), $page * $this->pageSize));


        $this->view->dump();
    }

    public function GetPageFromPostVars($numPages)
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
