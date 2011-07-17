<?php

/**
 *
 */
class MainController
{
    const DEFAULT_TITLE = 'KrisMCV Website';

    /**
     * Constructor
     */
    function __construct()
    {
        $this->view = new KrisView(KrisConfig::APP_PATH . 'views/layouts/layout.php');
    }


    /**
     * Default action
     * @return void
     */
    public function Index()
    {

        $data = array();
        $data['body'] = $this->view->fetch(KrisConfig::APP_PATH . 'views/main/MainView.php',
            array('content' => 'Hello World!'), false);
        $data['title'] = self::DEFAULT_TITLE;
        $this->view->dump($data);
    }


}