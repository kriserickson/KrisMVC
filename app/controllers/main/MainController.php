<?php

/**
 *
 */
class MainController
{
    const DEFAULT_TITLE = 'Ammara Dance Company - Bellydance, Yoga, Steel Drums';

    const PAYPAL_EMAIL = 'lisa.raks@gmail.com';
    const PAYPAL_URL = 'www.paypal.com';
    const WEBSITE_URL = 'ammara.ca';

    /**
     * Constructor
     */
    function __construct()
    {
        $this->view = new KrisView(KrisConfig::APP_PATH . 'views/layouts/layout.php');
        
        KrisConfig::AddClass('DateHelpers', 'library/DateHelpers.php');
    }


    /**
     * Default action
     * @return void
     */
    public function Index()
    {

        $content = new ContentSimple('main');

        $data = array();
        $data['body'] = $this->view->fetch(KrisConfig::APP_PATH . 'views/main/MainView.php',
            array('content' => $content->Content), false);
        $data['title'] = self::DEFAULT_TITLE;
        $data['page_list'] = $this->GetPageList('/');

        $this->view->dump($data);
    }

    /**
     * About Action
     * @return void
     */
    public function About()
    {
        $data = array();
        $data['body'] = $this->view->fetch(KrisConfig::APP_PATH . 'views/main/AboutView.php');
        $data['title'] = self::DEFAULT_TITLE;
        $data['page_list'] = $this->GetPageList('/about');

        $this->view->dump($data);
    }

    /**
     * Show classes...
     * @return void
     */
    public function Classes()
    {
        $classCategory = new ClassCategory();
        $categories = $classCategory->getActiveCategories();

        $data = array();
        $data['title'] = self::DEFAULT_TITLE;
        $data['body'] = $this->view->fetch(KrisConfig::APP_PATH . 'views/main/ClassesView.php', array('categories' => $categories), false);
        $data['page_list'] = $this->GetPageList('/classes');

        $this->view->dump($data);
    }

    /**
     * Look at individual classes..
     *
     * @param string $style - passed in auto-magically by the controller
     * @return void
     */
    public function AmmaraClasses($style)
    {
        $data = array();

        $classDetails = new ClassDetail();
        $classDetails->loadByStyle($style);

        $data['title'] = ucwords(str_replace('_', ' ', $style));
        $data['body'] = $this->view->fetch(KrisConfig::APP_PATH . 'views/main/AmmaraClass.php',
            array('paypal_email_address' => self::PAYPAL_EMAIL, 'paypal_url' => self::PAYPAL_URL,
            'ammara_url' => self::WEBSITE_URL, 'classes' => $classDetails->ClassDetails), false);
        $data['page_list'] = $this->GetPageList('/classes');

        $this->view->dump($data);
    }

    /**
     * Show single class
     *
     * @param int $classId
     * @return void
     */
    public function AmmaraClass($classId)
    {
        $data = array();

        $classDetails = new ClassDetail();
        $classDetails->loadByClassId($classId);

        $data['title'] = $classDetails->ClassName;
        $data['body'] = $this->view->fetch(KrisConfig::APP_PATH . 'views/main/AmmaraClass.php',
            array('paypal_email_address' => self::PAYPAL_EMAIL, 'paypal_url' => self::PAYPAL_URL,
            'ammara_url' => self::WEBSITE_URL, 'classes' => array($classDetails)), false);
        $data['page_list'] = $this->GetPageList('/classes');

        $this->view->dump($data);
    }

    /**
     * Show all instructors...
     *
     * @param int $instructor_id
     * @return void
     */
    public function Instructors($instructor_id = 0)
    {
        $classDetails = new ClassDetail();
        $instructors = new InstructorWithClass();
        if (intval($instructor_id) > 0)
        {
            $classDetails->loadByInstructor($instructor_id);
            $instructors->loadByInstructor($instructor_id);
        }
        else
        {
            $classDetails->loadAllOffered();
            $instructors->loadAllWithOfferedClasses();
        }


        for ($index = 0; $index < count($instructors->Instructors); $index++)
        {
            $instructors->Instructors[$index]->Classes = $classDetails->GetClassesByInstructorId($instructors->Instructors[$index]->InstructorId);
        }

        $data['title'] = self::DEFAULT_TITLE;
        $data['body'] = $this->view->fetch(KrisConfig::APP_PATH . 'views/main/InstructorsView.php', array('instructors' => $instructors->Instructors), false);
        $data['page_list'] = $this->GetPageList('/instructors');

        $this->view->dump($data);

    }

    /**
     * Show store
     * @return void
     */
    public function Store()
    {
        $storeItem = new StoreItem();
        $storeItem->loadOffered();
        
        $data = array();
        $data['body'] = $this->view->fetch(KrisConfig::APP_PATH . 'views/main/StoreView.php',
            array('paypal_email_address' => self::PAYPAL_EMAIL, 'paypal_url' => self::PAYPAL_URL,
            'ammara_url' => self::WEBSITE_URL, 'items' => $storeItem->StoreItems), false);
        $data['title'] = 'Ammara Shop';
        $data['page_list'] = $this->GetPageList('/store');

        $this->view->dump($data);
    }

    /**
     * Show transit
     *
     * @return void
     */
    public function Transit()
    {
        $data = array();
        $data['body'] = $this->view->fetch(KrisConfig::APP_PATH . 'views/main/TransitView.php');
        $data['title'] = self::DEFAULT_TITLE;
        $data['page_list'] = $this->GetPageList('/about');

        $this->view->dump($data);
    }

    /**
     * Show subscribe
     *
     * @return void
     */
    public function Subscribe()
    {
        $data = array();
        $data['body'] = $this->view->fetch(KrisConfig::APP_PATH . 'views/main/SubscribeView.php');
        $data['title'] = self::DEFAULT_TITLE;
        $data['page_list'] = $this->GetPageList('/about');

        $this->view->dump($data);
    }

    /**
     * Show links
     *
     * @return void
     */
    public function Links()
    {

        $content = new ContentSimple('links');

        $data = array();
        $data['body'] = $content->Content;
        $data['title'] = self::DEFAULT_TITLE;
        $data['page_list'] = $this->GetPageList('/links');

        $this->view->dump($data);
    }

    /**
     * Show news
     *
     * @return void
     */
    public function News()
    {

        $content = new ContentSimple('news');

        $data = array();
        $data['body'] = $content->Content;
        $data['title'] = self::DEFAULT_TITLE;
        $data['page_list'] = $this->GetPageList('/news');

        $this->view->dump($data);
    }

    /**
     * Get the page list with the selected link...
     *
     * @param string $activeLink
     * @return string
     */
    private function GetPageList($activeLink)
    {
        $links = array('/' => 'Home', '/about' => 'About Us', '/classes' => 'Classes',
            '/instructors' => 'Instructors', '/store' => 'Shop', '/links' => 'Links', '/news' => 'News');

        $retValue = '';

        foreach ($links as $link => $pageName)
        {
            $retValue .= '<li'.($link == $activeLink ? ' class="home"' : '><a href="'.KrisConfig::WEB_FOLDER.'/main'.$link.'"').'>'.$pageName.
                ($link == $activeLink ? '' : '</a>').'</li>'.PHP_EOL;
        }
        return $retValue;
    }


}