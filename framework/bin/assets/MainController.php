<?php

/**
 *
 */
class MainController extends DefaultController
{
    const DEFAULT_TITLE = 'KrisMCV Website';

    /**
     * Constructor
     */
    function __construct()
    {
        $this->_view = new KrisView(KrisConfig::APP_PATH . 'views/layouts/layout.php');
    }


    /**
     * Default action
     * @return void
     */
    public function Index()
    {

        $data = array();
        $data['body'] = $this->_view->fetch(KrisConfig::APP_PATH . 'views/main/MainView.php',
            array('content' => 'Hello World!'), false);
        $data['title'] = self::DEFAULT_TITLE;
        $this->_view->dump($data);
    }


}