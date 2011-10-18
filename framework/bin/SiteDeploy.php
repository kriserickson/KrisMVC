<?php
/*
 * This file is part of the KrisMvc framework.
 *
 * (c) Kris Erickson 
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

class SiteDeploy
{
    /**
     * @var string
     */
    private $_location;

    /**
     * @var bool
     */
    private $_isLive;

    /**
     * @var string
     */
    private $_appDir;


    /**
     * @var string
     */
    private $_deploymentDirectory;

    /**
     * @var string
     */
    private $_deployFilename;

    /**
     * @var array
     */
    private $_deploymentConfig = array();

    /**
     * This is collection of files to be copied and hashes of those files...
     *
     * @var array
     */
    private $_files = array();

    /**
     * @param string $location
     * @param bool $isLive
     * @param string $deploymentDir     */
    public function __construct($location, $isLive, $deploymentDir = 'deploy')
    {
        $this->_location = $location;
        $this->_isLive = $isLive;
        $this->_appDir = CodeGenHelpers::BuildPath($location, 'app'); // TODO: Pull from config...
        $this->_deploymentDirectory = CodeGenHelpers::BuildPath($this->_appDir, $deploymentDir);

        if (!is_dir($this->_deploymentDirectory))
        {
            mkdir($this->_deploymentDirectory);
        }

        $this->_deployFilename = CodeGenHelpers::BuildPath($this->_deploymentDirectory, 'deploy.config');
        if (file_exists($this->_deployFilename))
        {
            $this->_deploymentConfig = json_decode(file_get_contents($this->_deployFilename), true);
        }
    }


    public function Initialize($userName, $password, $host, $destdir, $temp, $yuiLocation)
    {
        if ($userName)
        {
            $this->_deploymentConfig['username'] = $userName;
        }
        if ($password)
        {
            $this->_deploymentConfig['password'] = $password;
        }
        if ($host)
        {
            $this->_deploymentConfig['host'] = $host;
        }
        if ($temp)
        {
            $this->_deploymentConfig['temp_dir'] = $temp;
        }
        if ($destdir)
        {
            $this->_deploymentConfig['dest_dir'] = $destdir;
        }
        if ($yuiLocation)
        {
            $this->_deploymentConfig['yui-location'] = $yuiLocation;
        }

        if (!isset($this->_deploymentConfig['username']))
        {
            throw new Exception('No username is set');
        }
        if (!isset($this->_deploymentConfig['password']))
        {
            throw new Exception('No password is set');
        }
        if (!isset($this->_deploymentConfig['host']))
        {
            throw new Exception('No host is set');
        }
        if (!isset($this->_deploymentConfig['temp_dir']))
        {
            $this->_deploymentConfig['temp_dir'] = CodeGenHelpers::BuildPath(sys_get_temp_dir(), basename($this->_location));
        }
    }

    public function WriteDeployFile()
    {
        file_put_contents($this->_deployFilename, json_encode($this->_deploymentConfig));
    }

    public function Deploy()
    {
        // Create the temp directory...
        $tmpDir = $this->_deploymentConfig['temp_dir'];

        $this->CleanTempDirectory($tmpDir);

        // CopyDirectory
        $this->CopyDirectoryRecursive(CodeGenHelpers::BuildPath(dirname(__DIR__), 'lib'), CodeGenHelpers::BuildPath($tmpDir, 'lib'));
        $appTmpDir = CodeGenHelpers::BuildPath($tmpDir, basename($this->_appDir));
        $this->CopyDirectoryRecursive(CodeGenHelpers::BuildPath($this->_appDir, 'controllers'), CodeGenHelpers::BuildPath($appTmpDir, 'controllers'));
        $this->CopyDirectoryRecursive(CodeGenHelpers::BuildPath($this->_appDir, 'library'), CodeGenHelpers::BuildPath($appTmpDir, 'library'));
        $this->CopyDirectoryRecursive(CodeGenHelpers::BuildPath($this->_appDir, 'models'), CodeGenHelpers::BuildPath($appTmpDir, 'models'));
        $this->CopyDirectoryRecursive(CodeGenHelpers::BuildPath($this->_appDir, 'vendor'), CodeGenHelpers::BuildPath($appTmpDir, 'vendor'));
        $this->CopyDirectoryRecursive(CodeGenHelpers::BuildPath($this->_location, 'images'), CodeGenHelpers::BuildPath($tmpDir, 'images'));

        //  Copy and combine javascript into a different temp directory.
        //  YUI Minimize Javascript that javascript...

        //  Copy and combine css into a different temp directory.
        //  YUI Minimize Javascript that javascript...

        // Create config based on whether we are live or not...

        // Get all the hashes from the server

        // Put a site closed message up...

        // Run any SQL transformations through SCP.

        // Copy all files that have different hashes.

        // Move the site live again...

    }

    private function CopyDirectoryRecursive($source, $dest)
    {
        $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($source), RecursiveIteratorIterator::SELF_FIRST);
        $sourceLen = strlen($source);

        mkdir($dest, 0777, true);

        foreach ($iterator as $path)
        {
            $sourceLocation = $path->__toString();
            if (strpos($sourceLocation, DIRECTORY_SEPARATOR.'.') > 0)
            {
                continue;
            }
            $destLocation = $dest . substr($sourceLocation, $sourceLen);
            if ($path->isDir())
            {
                mkdir($destLocation);
            }
            else
            {
                copy($sourceLocation, $destLocation);
                $this->_files[$destLocation] = md5_file($destLocation);
            }
        }
    }

    private function CleanTempDirectory($tmpDir)
    {
        if (!is_dir($tmpDir))
        {
            mkdir($tmpDir);
        }
        else
        {
            $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($tmpDir), RecursiveIteratorIterator::CHILD_FIRST);
            foreach ($iterator as $path)
            {
                if ($path->isDir())
                {
                    rmdir($path->__toString());
                }
                else
                {
                    unlink($path->__toString());
                }
            }
        }
    }


}
