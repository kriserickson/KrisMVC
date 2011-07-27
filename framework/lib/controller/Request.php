<?php
/*
 * This file is part of the KrisMvc framework.
 *
 * (c) Kris Erickson 
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

class Request
{
    /**
     * @var string
     */
    private $_controller;

    /**
     * @var string
     */
    private $_action;

    /**
     * @var array
     */
    private $_params;

    /**
     * @var array
     */
    static $_post = null;

    const CONTENT_TYPE_XML = 'text/xml';
    const CONTENT_TYPE_CSS = 'text/css';
    const CONTENT_TYPE_HTML = 'text/html';
    const CONTENT_TYPE_JSON = 'application/json';
    const CONTENT_TYPE_JAVASCRIPT = 'application/javascript';
    const CONTENT_TYPE_BINARY = 'application/octet-stream';


    /**
     * @param string $controller
     * @param string $action
     * @param array $params
     */
    function __construct($controller, $action, $params)
    {
        $this->_action = $action;
        $this->_controller = $controller;
        $this->_params = $params;
        if (is_null(self::$_post))
        {
            self::$_post = $_POST;
        }
    }

    /**
     * @return string
     */
    public function Controller()
    {
        return $this->_controller;
    }

    /**
     * @return string
     */
    public function Action()
    {
        return $this->_controller;
    }

    /**
     * @param $index
     * @return string
     */
    public function Param($index)
    {
        return $this->_params[$index];
    }

    /**
     * @param $key
     * @return string
     */
    public function __get($key)
    {
        return $this->PostVar($key);
    }

    /**
     * @param $key
     * @param string $default
     * @return string
     */
    public function PostVar($key, $default = '')
    {
        if (isset(self::$_post[$key]))
        {
            return self::$_post[$key];
        }
        return $default;
    }


    /**
     * @param string $key
     * @return bool
     */
    public function IsPosted($key)
    {
        return isset(self::$_post[$key]);
    }

    /**
     * @param string $key
     * @param string $value
     * @return void
     */
    public function SetPostVar($key, $value)
    {
        self::$_post[$key] = $value;
    }

    /**
     * @throws Exception
     * @param int $code
     * @param string $message
     * @return void
     */
    public function SetError($code, $message)
    {
        switch ($code)
        {
            // 1xx Informational
            case 100 :
                $httpMessage = 'Continue';
                break;
            case 101 :
                $httpMessage = 'Switching Protocols';
                break;
            case 122  :
                $httpMessage = 'Request-URI too long';
                break;
            // 2xx Success
            case 200 :
                $httpMessage = 'OK';
                break;
            case 201 :
                $httpMessage = 'Created';
                break;
            case 202 :
                $httpMessage = 'Accepted';
                break;
            case 204 :
                $httpMessage = 'No Content';
                break;
            case 205 :
                $httpMessage = 'Reset Content';
                break;
            case 206 :
                $httpMessage = 'Partial Content';
                break;
            // 3xx Redirection
            case 301 :
                $httpMessage = 'Moved Permanently';
                break;
            case 302 :
                $httpMessage = 'Found';
                break;
            case 304 :
                $httpMessage = 'Not Modified';
                break;
            case 307 :
                $httpMessage = 'Temporary Redirect';
                break;
            // 4xx Client Error
            case 400 :
                $httpMessage = 'Bad Request';
                break;
            case 401 :
                $httpMessage = 'Unauthorized';
                break;
            case 403 :
                $httpMessage = 'Forbidden';
                break;
            case 404:
                $httpMessage = 'Not Found';
                break;
            case 405:
                $httpMessage = 'Method Not Allowed';
                break;
            case 406:
                $httpMessage = 'Not Acceptable';
                break;
            case 408 :
                $httpMessage = 'Request Timeout';
                break;
            case 409  :
                $httpMessage = 'Conflict';
                break;
            case 410 :
                $httpMessage = 'Gone';
                break;
            case 411 :
                $httpMessage = 'Length Required';
                break;
            case 413 :
                $httpMessage = 'Request Entity Too Large';
                break;
            case 414 :
                $httpMessage = 'Request-URI Too Long';
                break;
            case 444 :
                $httpMessage = 'No Response';
                break;
            case 450 :
                $httpMessage = 'Blocked by Windows Parental Controls';
                break;
            // 5xx Server Error
            case 500 :
                $httpMessage = 'Internal Server Error';
                break;
            case 501 :
                $httpMessage = 'Not Implemented';
                break;
            case 502 :
                $httpMessage = 'Bad Gateway';
                break;
            case 503 :
                $httpMessage = 'Service Unavailable';
                break;
            case 509 :
                $httpMessage = 'Bandwidth Limit Exceeded';
                break;
            default:
                throw new Exception('Invalid error code: ' . $code);
        }

        header('HTTP/1.0 ' . $code . ' ' . $httpMessage);

        echo $message;

    }

    /**
     * @param string $mimeType
     * @return void
     */
    public function SetContentType($mimeType)
    {
        header('Content-type: ' . $mimeType);
    }

    /**
     * @param string $filename
     * @return void
     */
    public function SetContentDisposition($filename)
    {
        header('Content-Disposition: attachment; filename="' . $filename . '"');
    }



}