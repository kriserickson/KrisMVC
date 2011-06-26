<?php

class MainController
{
    function __construct()
    {
        $this->view = new KrisView(KrisConfig::$APP_PATH . 'views/layouts/layout.php');
        
        KrisConfig::AddClass('DateHelpers', 'library/DateHelpers.php');
    }


    public function Index()
    {

        $content = new Content('main');

        $data = array();
        $data['body'] = $this->view->fetch(KrisConfig::$APP_PATH . 'views/main/MainView.php',
            array('body' => $content->Content), false);
        $data['page_list'] = $this->GetPageList('/');

        $this->view->dump($data);
    }
}
