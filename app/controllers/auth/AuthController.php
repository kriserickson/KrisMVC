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
 * Shows Login/SignUp/EditUser/AddUser/EditUser
 *
 * @throws Exception
 *
 */
class AuthController extends DefaultController
{

    protected $_useEmail = false;
    protected $_userPageSize = 20;
    protected $_authController;

    /**
     * @param Request $request
     */
    function __construct($request)
    {
        $this->_request = $request;
        $this->_authController = KrisConfig::WEB_FOLDER.'/'.KrisConfig::AUTH_CONTROLLER;
        $this->_data = array('page_list' => array(), 'second_menu' => '');
        $this->_view = new MustacheView(KrisConfig::APP_PATH . 'views/layouts/layout.php');
        $this->_auth = Auth::instance();
    }

    /**
     * Index is an alias for login...
     * @return null|\RouteRequest
     */
    public function Index()
    {
        return $this->Login();
    }

    /**
     * @return null|\RouteRequest
     */
    public function Login()
    {
        $error = '';
        $password = $this->_request->PostVar('password');
        $destinationUrl = $this->_request->PostVar('destination_url');

        if ($this->_request->IsPosted('sign_up_button'))
        {
            return $this->SignUp();
        }
        else if ($this->_request->IsPosted('email') || ($this->_request->IsPosted('user_name')))
        {
            $res = $this->LoginToSite($this->_request->PostVar('email'), $this->_request->PostVar('user_name'), $password, $destinationUrl);
            if (get_class($res) == 'RouteRequest')
            {
                return $res;
            }
            $error = $res;
        }

        $this->_data['body'] = $this->_view->fetch(KrisConfig::APP_PATH . 'views/auth/LoginView.php',
            array('user_name' => $this->_request->PostVar('user_name'), 'email' => $this->_request->PostVar('email'),
            'password' => $password, 'destination_url' => $destinationUrl, 'login_href' => $this->_authController.'/login',
            'error' => $error, 'use_email' => $this->_useEmail), false);
        $this->_data['title'] = 'Login';

        $this->_view->dump($this->_data);

        return null;
    }

    /**
     * @param string $email
     * @param string $loginName
     * @param string $password
     * @param string $destinationUrl
     * @return RouteRequest|string
     */
    private function LoginToSite($email, $loginName, $password, $destinationUrl)
    {

        if ($this->_useEmail)
        {

            $loginSuccess = $this->_auth->LoginWithEmail($email, $password);
        }
        else
        {

            $loginSuccess = $this->_auth->Login($loginName, $password);
        }

        if ($loginSuccess)
        {
            if ($destinationUrl)
            {
                return RouteRequest::CreateFromUri($destinationUrl);
            }
            else
            {
                return new RouteRequest(KrisConfig::DEFAULT_CONTROLLER, KrisConfig::DEFAULT_ACTION);
            }
        }
        else
        {
            if ($this->_auth->Error() == Auth::ERROR_INVALID_PASSWORD || $this->_auth->Error() == Auth::ERROR_INVALID_LOGIN)
            {
                $error =  'Invalid Username or Password';
            }
            else if ($this->_auth->Error() == Auth::ERROR_TOO_MANY_INVALID_LOGINS)
            {
                $error = 'Too many invalid logins, try again in an hour';
            }
            else
            {
                $error = 'Unknown error';
            }
            return $error;
        }
    }

    /**
     * @param string $action
     * @param int $user
     * @return null|RouteRequest
     */
    public function Admin($action = '', $user = -1)
    {
        if (!$this->_auth->IsLoggedIn())
        {
            $this->_request->SetPostVar('destination_url', $this->_request->Route());
            return new RouteRequest(KrisConfig::AUTH_CONTROLLER, KrisConfig::DEFAULT_ACTION, array());
        }
        if (!$this->_auth->User()->HasAclOrGreater(Auth::ACL_ADMIN))
        {
            return new RouteRequest(KrisConfig::DEFAULT_CONTROLLER, KrisConfig::DEFAULT_ACTION, array());
        }
        if ($this->_request->IsPosted('add'))
        {
            $action = 'add';
        }


        if ($action == '' || $action == 'list')
        {
            $this->DisplayUsers();
        }
        else if ($action == 'edit')
        {
            $this->EditUser($user);
        }
        else if ($action == 'delete')
        {
            $this->DeleteUser($user);
        }
        else if ($action == 'add')
        {
            $this->AddUser();
        }

        return null;
    }

    /**
     * @param int $user
     * @return void
     */
    private function EditUser($user)
    {

    }

    /**
     * @param int $user
     * @return void
     */
    private function DeleteUser($user)
    {
    }


    private function AddUser()
    {
    }


    private function DisplayUsers()
    {
        $searchField = $this->_request->PostVar('search_field');

        $searchTypes = array(Auth::SEARCH_TYPE_USERNAME => 'Username', Auth::SEARCH_TYPE_EMAIL => 'Email');
        $startPosition = $this->_request->PostVar('current_position', 0);
        if ($this->_request->IsPosted('previous_page'))
        {
            $startPosition -= $this->_userPageSize;
        }
        else if ($this->_request->IsPosted('next_page'))
        {
            $startPosition += $this->_userPageSize;
        }
        $startPosition = $startPosition < 0 ? 0 : $startPosition;

        $error = '';

        $searchType = $this->_request->PostVar('search_type', 0);
        $search_options = array();
        for ($i = 0; $i < count($searchTypes); $i++)
        {
            $search_options[] = array('value' => $i, 'display' => $searchTypes[$i], 'selected' => $i == $searchType);
        }

        $userArray = $this->GetUsers($searchType, $searchField, $startPosition, $this->_userPageSize);

        $totalUsers = Auth::instance()->TotalUsers($searchType, $searchField);

        $numberOfPages = ceil($totalUsers / $this->_userPageSize);

        $this->_data['body'] = $this->_view->fetch(KrisConfig::APP_PATH . 'views/auth/UserView.php',
            array ('error_string' => $error, 'user_view_href' => $this->_authController.'/admin',
            'search_field' => $searchField, 'users' => $userArray, 'search_options' => $search_options, 'prev_page_disabled' => $startPosition == 0,
            'next_page_disabled' => $startPosition >= $numberOfPages - 1, 'display_page' => $startPosition + 1, 'number_of_pages' => $numberOfPages,
            'total_users' => $totalUsers), false);

        $this->_data['title'] = 'Edit Users';

        $this->_view->dump($this->_data);
    }

    public function SignUp()
    {
        $error = '';

        if ($this->_request->IsPosted('email'))
        {
            $loginName = $this->_request->PostVar('user_name');
            $email = $this->_request->PostVar('email');
            $password = $this->_request->PostVar('password');

            if ($this->_auth->AddUser($loginName, $email, $password, $this->_request->PostVar('confirm_password'), '', !$this->_useEmail))
            {
                return $this->LoginToSite($email, $loginName, $password, $this->_request->PostVar('destination_url'));
            }

            switch ($this->_auth->Error())
            {
                case Auth::ERROR_PASSWORD_TOO_SHORT:
                    $error =  'Password too short!';
                    break;
                case Auth::ERROR_PASSWORD_MUST_INCLUDE_ONE_SYMBOL:
                    $error = "Password must include at least one symbol!";
                    break;
                case Auth::ERROR_PASSWORD_MUST_INCLUDE_ONE_NUMBER:
                    $error = "Password must include at least one number!";
                    break;
                case Auth::ERROR_PASSWORD_MUST_INCLUDE_ONE_LETTER:
                    $error = "Password must include at least one letter!";
                    break;
                case Auth::ERROR_PASSWORD_MUST_INCLUDE_ONE_CAPITAL_LETTER:
                    $error = "Password must include at least one CAPS!";
                    break;
                case Auth::ERROR_LOGIN_NAME_ALREADY_EXISTS:
                    $error = 'LoginName '.$loginName.' already exists';
                    break;
                case Auth::ERROR_EMAIL_ALREADY_EXISTS:
                    $error = 'Email '.$email.' has already been used';
                    break;
                case Auth::ERROR_CONFIRM_PASSWORD_DOES_NOT_MATCH_PASSWORD:
                    $error = 'Confirm Password does not match password';
                    break;
                case Auth::ERROR_INVALID_EMAIL:
                    $error = 'Invalid email address';
                    break;
                default:
                    $error = "Unknown sign up error";

            }



        }

        $this->_data['body'] = $this->_view->fetch(KrisConfig::APP_PATH . 'views/auth/SignupView.tpl',
            array('user_name' => $this->_request->PostVar('user_name'), 'email' => $this->_request->PostVar('email'),
                'destination_url' => $this->_request->PostVar('destination_url'), 'error' => $error,
                'signup_href' => $this->_authController.'/signup',  'use_email' => $this->_useEmail), false);
        $this->_data['title'] = 'Sign Up';

        $this->_view->dump($this->_data);

        return null;
    }

    public function Logout()
    {
        Auth::instance()->Logout();
        return new RouteRequest(KrisConfig::DEFAULT_CONTROLLER, KrisConfig::DEFAULT_ACTION);
    }

    private function GetUsers($searchType, $search, $startPosition, $pageSize)
    {
        if (strlen($search) == 0)
        {
            $users = Auth::instance()->GetUsers($startPosition, $pageSize);
        }
        else
        {
            $users = Auth::instance()->SearchUsers($searchType, $search, $startPosition, $pageSize);
        }

        $userList = array();        

        /** @var $user DBUserModel */
        foreach ($users as $user)
        {
            $aclStrings = $this->GetAclStringArray($user->Acl);            
            $userList[] = array('primary_key' => $user->UserId, 'login_name' => $user->LoginName,
                'email' => $user->Email, 'acl' => implode(', ', $aclStrings));
        }

        return $userList;
    }

    private function GetAclStringArray($acl)
    {
        $acl_array = array();
        if ($acl & Auth::ACL_ADMIN)
        {
            $acl_array[] = 'Administrator';
        }
        if ($acl & Auth::ACL_DEVELOPER)
        {
            $acl_array[] = 'Developer';
        }
        if ($acl & Auth::ACL_EDIT)
        {
            $acl_array[] = 'Edit';
        }
        if ($acl & Auth::ACL_GUEST)
        {
            $acl_array[] = 'Guest';
        }
        if ($acl & Auth::ACL_NONE)
        {
            $acl_array[] = 'None';
        }
        if ($acl & Auth::ACL_READ)
        {
            $acl_array[] = 'Read';
        }
        if ($acl & Auth::ACL_ROOT)
        {
            $acl_array[] = 'Root';
        }
        if ($acl & Auth::ACL_WRITE)
        {
            $acl_array[] = 'Write';
        }
        
        return $acl_array;
    }



}
