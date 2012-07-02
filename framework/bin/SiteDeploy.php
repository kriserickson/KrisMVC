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
 *
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
     * @var string
     */
    private $_stageLive = '';

    /**
     * This is collection of files to be copied and hashes of those files...
     *
     * @var array
     */
    private $_files = array();

    /**
     * @var array
     */
    private $_validatedDirectories = array();

    /**
     * @var bool
     */
    private $_clearDatabase = false;

    const ActionCopy = 1;
    const ActionMerge = 2;
    const ActionCompress = 3;

    const DEPLOY_LIVE = 'live';
    const DEPLOY_STAGING = 'staging';


    /**
     * @param string $location
     * @param bool $isLive
     * @param string $deploymentDir     */
    public function __construct($location, $isLive, $deploymentDir = 'deploy')
    {
        $this->_location = $location;
        $this->_isLive = $isLive;
        $this->_stageLive = $isLive ? self::DEPLOY_LIVE : 'staging';
        $this->_appDir = CodeGenHelpers::BuildPath($location, 'app'); // TODO: Pull from config...
        $this->_deploymentDirectory = CodeGenHelpers::BuildPath($location, $deploymentDir);

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

    /**
     * @throws Exception
     * @param string $userName
     * @param string $password
     * @param string $host
     * @param string $destdir
     * @param string $temp
     * @param string $yuiLocation
     * @param int $port
     * @param bool $deploySql
     * @param bool $clearDatabase
     * @return void
     */
    public function Initialize($userName, $password, $host, $destdir, $temp, $yuiLocation, $port, $deploySql = false, $clearDatabase = false)
    {
        if ($temp)
        {
            $this->_deploymentConfig['temp_dir'] = $temp;
        }
        if ($userName)
        {
            $this->_deploymentConfig[$this->_stageLive]['username'] = $userName;
        }
        if ($password)
        {
            $this->_deploymentConfig[$this->_stageLive]['password'] = $password;
        }
        if ($host)
        {
            $this->_deploymentConfig[$this->_stageLive]['host'] = $host;
        }
        if ($port)
        {
            $this->_deploymentConfig[$this->_stageLive]['port'] = $port;
        }

        if ($destdir)
        {
            $this->_deploymentConfig[$this->_stageLive]['dest_dir'] = $destdir;
        }
        if ($yuiLocation)
        {
            $this->_deploymentConfig['yui-location'] = $yuiLocation;
        }
        $this->_clearDatabase = $clearDatabase;


        if (!isset($this->_deploymentConfig[$this->_stageLive]['username']))
        {
            throw new Exception('No username is set');
        }
        if (!isset($this->_deploymentConfig[$this->_stageLive]['password']))
        {
            throw new Exception('No password is set');
        }
        if (!isset($this->_deploymentConfig[$this->_stageLive]['host']))
        {
            throw new Exception('No host is set');
        }
        if (!isset($this->_deploymentConfig[$this->_stageLive]['port']))
        {
            $this->_deploymentConfig[$this->_stageLive]['port'] = 22;
        }
        if (!isset($this->_deploymentConfig[$this->_stageLive]['dest_dir']))
        {
            throw new Exception('Destination directory is not set');
        }
        if (!isset($this->_deploymentConfig['yui-location']) && !$deploySql)
        {
            throw new Exception('Yui-location is not set');
        }
        if (!isset($this->_deploymentConfig['temp_dir']))
        {
            $this->_deploymentConfig['temp_dir'] = CodeGenHelpers::BuildPath(sys_get_temp_dir(), basename($this->_location));
        }


    }

    /**
     * writes the deploy file which keeps track of which files are on the website...
     *
     * @return void
     */
    public function WriteDeployFile()
    {
        file_put_contents($this->_deployFilename, json_encode($this->_deploymentConfig));
    }

    /**
     * Deploys the site...
     *
     * @throws Exception
     * @param bool $validate
     * @param bool $mark
     * @return void
     */
    public function DeploySql($validate, $mark)
    {

        if ($this->_stageLive == self::DEPLOY_LIVE && $this->_clearDatabase)
        {
            throw new Exception('Refusing to clear the live database, clear can only be performed on the staging database');
        }

        $configDirectory = $this->createConfigDirectory();

        $connection = ssh2_connect($this->_deploymentConfig[$this->_stageLive]['host'], $this->_deploymentConfig[$this->_stageLive]['port']);
        if (!$connection)
        {
            throw new Exception('Invalid host:' . $this->_deploymentConfig[$this->_stageLive]['host'] . ' nothing listening on port: ' . $this->_deploymentConfig[$this->_stageLive]['port']);
        }

        if (!ssh2_auth_password($connection, $this->_deploymentConfig[$this->_stageLive]['username'], $this->_deploymentConfig[$this->_stageLive]['password']))
        {
            throw new Exception('Invalid username password for host:' . $this->_deploymentConfig[$this->_stageLive]['host']);
        }


        $sftp = ssh2_sftp($connection);

        $destDirectory = $this->_deploymentConfig[$this->_stageLive]['dest_dir'];


        $siteClosed = $this->CloseSiteForMaintenance($sftp, $connection, $destDirectory);

        $this->DeploySqlChanges($connection, CodeGenHelpers::BuildPath($destDirectory, 'app', true), CodeGenHelpers::BuildPath($destDirectory, 'bin', true),
            $configDirectory, $validate, $mark);

        if ($siteClosed)
       {
           $this->ReopenSite($sftp, $connection, $destDirectory);
       }



    }

    /**
     * @return string
     */
    private function createConfigDirectory() {
        $tmpDir = $this->_deploymentConfig['temp_dir'];

        echo 'Clearing out old site' . PHP_EOL;
        $this->CleanTempDirectory($tmpDir);

        $configDirectory = CodeGenHelpers::BuildPath($tmpDir, 'config');

        echo 'Copying Config' . PHP_EOL;
        $this->CopyDirectoryRecursive(CodeGenHelpers::BuildPath($this->_location, 'config'), $configDirectory);

        echo 'Merging Config Files' . PHP_EOL;
        $this->MergeConfigFiles($configDirectory, $tmpDir);
        return $configDirectory;
    }

    /**
     * Deploys the site...
     *
     * @throws Exception
     * @return void
     */
    public function Deploy()
    {
        if (!function_exists('ssh2_connect'))
        {
            throw new Exception('SSH2 extension is not installed.');
        }

        if ($this->_stageLive == self::DEPLOY_LIVE && $this->_clearDatabase)
        {
            throw new Exception('Refusing to clear the live database, clear can only be performed on the staging database');
        }

        // Create the temp directory...
        $tmpDir = $this->_deploymentConfig['temp_dir'];
        $configDirectory = CodeGenHelpers::BuildPath($tmpDir, 'config');
        $appTmpDir = CodeGenHelpers::BuildPath($tmpDir, basename($this->_appDir));

        echo 'Clearing out old site' . PHP_EOL;
        $this->CleanTempDirectory($tmpDir);

        // CopyDirectory
        echo 'Copy KrisMVC library' . PHP_EOL;
        $frameworkDirectory = dirname(dirname(__FILE__));
        $this->CopyDirectoryRecursive(CodeGenHelpers::BuildPath($frameworkDirectory, 'lib'), CodeGenHelpers::BuildPath($tmpDir, 'lib'));
        $this->CopyDirectoryRecursive(CodeGenHelpers::BuildPath($frameworkDirectory, 'bin'), CodeGenHelpers::BuildPath($tmpDir, 'bin'));

        echo 'Copying controllers' . PHP_EOL;
        $this->CopyDirectoryRecursive(CodeGenHelpers::BuildPath($this->_appDir, 'controllers'), CodeGenHelpers::BuildPath($appTmpDir, 'controllers'));

        echo 'Copying library' . PHP_EOL;
        $this->CopyDirectoryRecursive(CodeGenHelpers::BuildPath($this->_appDir, 'library'), CodeGenHelpers::BuildPath($appTmpDir, 'library'));

        echo 'Copying models' . PHP_EOL;
        $this->CopyDirectoryRecursive(CodeGenHelpers::BuildPath($this->_appDir, 'models'), CodeGenHelpers::BuildPath($appTmpDir, 'models'));

        echo 'Copying sql' . PHP_EOL;
        $this->CopyDirectoryRecursive(CodeGenHelpers::BuildPath($this->_appDir, 'sql'), CodeGenHelpers::BuildPath($appTmpDir, 'sql'));

        echo 'Copying vendor directory' . PHP_EOL;
        $this->CopyDirectoryRecursive(CodeGenHelpers::BuildPath($this->_appDir, 'vendor'), CodeGenHelpers::BuildPath($appTmpDir, 'vendor'));

        echo 'Copying images' . PHP_EOL;
        $this->CopyDirectoryRecursive(CodeGenHelpers::BuildPath($this->_location, 'images'), CodeGenHelpers::BuildPath($tmpDir, 'images'));

        echo 'Copying css' . PHP_EOL;
        $this->CopyDirectoryRecursive(CodeGenHelpers::BuildPath($this->_location, 'css'), CodeGenHelpers::BuildPath($tmpDir, 'css'));

        echo 'Copying js' . PHP_EOL;
        $this->CopyDirectoryRecursive(CodeGenHelpers::BuildPath($this->_location, 'js'), CodeGenHelpers::BuildPath($tmpDir, 'js'));

        echo 'Copying and merging views' . PHP_EOL;
        $this->CopyDirectoryRecursive(CodeGenHelpers::BuildPath($this->_appDir, 'views'), CodeGenHelpers::BuildPath($appTmpDir, 'views'), SiteDeploy::ActionMerge, $tmpDir);

        echo 'Compressing Javascript' . PHP_EOL;
        $this->CopyDirectoryRecursive(CodeGenHelpers::BuildPath($tmpDir, 'js'), '', SiteDeploy::ActionCompress);

        echo 'Compressing CSS' . PHP_EOL;
        $this->CopyDirectoryRecursive(CodeGenHelpers::BuildPath($tmpDir, 'css'), '', SiteDeploy::ActionCompress);

        echo 'Copying Config' . PHP_EOL;
        $this->CopyDirectoryRecursive(CodeGenHelpers::BuildPath($this->_location, 'config'), $configDirectory);

        echo 'Merging Config Files' . PHP_EOL;
        $this->MergeConfigFiles($configDirectory, $tmpDir);


        $destDirectory = $this->_deploymentConfig[$this->_stageLive]['dest_dir'];
        $this->CopySingleFile('index.php', $tmpDir);
        $this->CopySingleFile('maintenance.html', $tmpDir);
        $this->CopySingleFile('.htaccess', $tmpDir);
        $this->CopySingleFile('favicon.ico', $tmpDir);
        $this->FixHtaccessFile($tmpDir, $destDirectory);


        echo 'Connecting to server ' . $this->_deploymentConfig[$this->_stageLive]['host'] . PHP_EOL;

        $connection = ssh2_connect($this->_deploymentConfig[$this->_stageLive]['host'], $this->_deploymentConfig[$this->_stageLive]['port']);
        if (!$connection)
        {
            throw new Exception('Invalid host:' . $this->_deploymentConfig[$this->_stageLive]['host'] . ' nothing listening on port: ' . $this->_deploymentConfig[$this->_stageLive]['port']);
        }

        if (!ssh2_auth_password($connection, $this->_deploymentConfig[$this->_stageLive]['username'], $this->_deploymentConfig[$this->_stageLive]['password']))
        {
            throw new Exception('Invalid username password for host:' . $this->_deploymentConfig[$this->_stageLive]['host']);
        }


        $sftp = ssh2_sftp($connection);

        $this->GetDeploymentFiles($destDirectory, $sftp);

        $copyFiles = $this->GetFilesToDeploy();

        $siteClosed = $this->CloseSiteForMaintenance($sftp, $connection, $destDirectory);

        $totalCopyFiles = count($copyFiles);
        $currentCopyFile = 0;
        $lastPercent = -1;

        try
        {

            if (count($copyFiles) > 0)
            {
                echo 'Uploading '.count($copyFiles).' files to server'.PHP_EOL;
            }
            else
            {
                echo 'No files to upload to server'.PHP_EOL;
            }

            // Copy all files that have different hashes.
            foreach ($copyFiles as $file => $hash)
            {
                // Handle the special cases for index.php and maintenance.html if the site is closed...
                if ($siteClosed && $file == 'index.php')
                {
                    $destFile = CodeGenHelpers::BuildPath($destDirectory, 'index.php.tmp', true);
                }
                else if ($siteClosed && $file == 'maintenance.html')
                {
                    $destFile = CodeGenHelpers::BuildPath($destDirectory, 'index.php', true);
                }
                else
                {
                    $destFile = CodeGenHelpers::BuildPath($destDirectory, $file, true);
                }

                $destFile = CodeGenHelpers::UnixifyPath($destFile);

                if ($this->UploadFile(CodeGenHelpers::BuildPath($tmpDir, $file), $destFile, $connection, $sftp))
                {
                    $this->_deploymentConfig[$this->_stageLive]['files'][$file] = $hash;
                }
                else
                {
                    throw new Exception('Unable to scp file: ' . $file . ' to ' . $destFile);
                }

                $percent = (int)(($currentCopyFile++ / $totalCopyFiles) * 100);
                if ($percent != $lastPercent)
                {
                    echo 'Percent copied: ' . $percent . PHP_EOL;
                    $lastPercent = $percent;
                }
            }
        }
        catch (Exception $ex)
        {
            $this->SaveDeploymentFiles($tmpDir, $destDirectory, $connection, $sftp);
            throw $ex;
        }

        if (count($copyFiles) > 0)
        {
            echo 'Completed file transfer'.PHP_EOL;
        }

        echo 'Saving deployment status to the server'.PHP_EOL;

        $this->SaveDeploymentFiles($tmpDir, $destDirectory, $connection, $sftp);

        $this->DeploySqlChanges($connection, CodeGenHelpers::BuildPath($destDirectory, 'app', true), CodeGenHelpers::BuildPath($destDirectory, 'bin', true),
            $configDirectory, false, true);

        if ($siteClosed)
        {
            $this->ReopenSite($sftp, $connection, $destDirectory);
        }

    }


    /**
     * @throws Exception
     * @param string $file
     * @param string $destFile
     * @param resource $connection
     * @param resource $sftp
     * @return bool
     */
    private function UploadFile($file, $destFile, $connection, $sftp)
    {
        $destDir = dirname($destFile);

        // Make sure the directory exists...
        if (!isset($this->_validatedDirectories[$destDir]))
        {
            //$res = ssh2_sftp_stat($sftp, $destDir);
            if (!($this->RemoteDirectoryExists($sftp, $destDir)))
            {
                if (!ssh2_sftp_mkdir($sftp, $destDir, 0777, true))
                {
                    throw new Exception('Unable to mkdir for ' . $destDir);
                }
            }
            $this->_validatedDirectories[$destDir] = true;
        }

        return ssh2_scp_send($connection, $file, $destFile);

    }

    /**
     * @param string $sftp
     * @param string $destDir
     * @return bool
     */
    private function RemoteDirectoryExists($sftp, $destDir)
    {
        return file_exists('ssh2.sftp://' . $sftp . $destDir);
    }

    /**
     * @param resource $connection
     * @param string $destAppDirectory
     * @param string $binDirectory
     * @param string $configDirectory
     * @param bool $validate
     * @param bool $mark
     * @throws Exception
     */
    private function DeploySqlChanges($connection, $destAppDirectory, $binDirectory, $configDirectory, $validate = false, $mark = false)
    {
        $sqlDir = CodeGenHelpers::BuildPath($destAppDirectory, 'sql', true);
        $sqlDeployScript = CodeGenHelpers::BuildPath($binDirectory, 'SqlDeploy.php', true);
        $sqlConfig = $this->GetSqlConfigData($configDirectory);
        
        $cmd = 'php '.$sqlDeployScript.' '.($validate ? 'validate' : 'migrate').($mark ? ' --mark' : '').($this->_clearDatabase  ? ' --clear' : '').
                ' --verbose --directory="'.$sqlDir.'" --host=' . $sqlConfig['host'] . ' --user=' . $sqlConfig['user'] .
                ' --password=' . $sqlConfig['password'] . ' --database=' . $sqlConfig['database'];

        echo 'SQL DEPLOY '.$cmd.PHP_EOL;
        
        $outputString = '';
        $errorString = '';

        echo 'Deploying SQL'.PHP_EOL;

        $this->ExecuteSSHCommand($connection, $cmd, $outputString, $errorString);
        echo $outputString;
        if (strlen($errorString))
        {
            throw new Exception('Migrating SQL [' . trim($errorString) . ']');
        }

    }


    /**
     * @param resource $connection
     * @param string $cmd
     * @param string $outputString
     * @param string $errorString
     * @return bool
     */
    public function ExecuteSSHCommand($connection, $cmd, &$outputString, &$errorString)
    {
        $stream = ssh2_exec($connection, $cmd);
        $errorStream = ssh2_fetch_stream($stream, SSH2_STREAM_STDERR);


        // Enable blocking for both streams
        stream_set_blocking($errorStream, true);
        stream_set_blocking($stream, true);

        // Whichever of the two below commands is listed first will receive its appropriate output.  The second command receives nothing
        $outputString = stream_get_contents($stream);
        $errorString = stream_get_contents($errorStream);

        // Close the streams
        fclose($errorStream);
        fclose($stream);

        return strlen($errorString) == 0;
    }

    /**
     * Reopens the site after a site closure.
     *
     * @throws Exception
     * @param resource $sftp
     * @param resource $connection
     * @param string $destDirectory
     * @return void
     */
    private function ReopenSite($sftp, $connection, $destDirectory)
    {
        echo 'Reopening site' . PHP_EOL;

        $success = ssh2_sftp_unlink($sftp, $destDirectory . '/index.php');
        if ($success)
        {
            $success = ssh2_sftp_rename($sftp, $destDirectory . '/index.php.tmp', $destDirectory . '/index.php');
            if (!$success)
            {
                ssh2_exec($connection, 'cp ' . $destDirectory . '/maintenance.html ' . $destDirectory . '/index.php');
                throw new Exception('Could not reopen store, unable to move index.php');
            }
        }
        else
        {


            throw new Exception('Could not reopen store, unable to remove maintenance page');
        }

    }

    /**
     * Puts up a closed message while the site is being updated
     *
     * @throws Exception
     * @param resource $sftp
     * @param resource $connection
     * @param string $destDirectory
     * @return bool
     */
    private function CloseSiteForMaintenance($sftp, $connection, $destDirectory)
    {
        $closedIndexExists = file_exists('ssh2.sftp://' . $sftp . $destDirectory . '/index.php.tmp');

        if (isset($this->_deploymentConfig[$this->_stageLive]['files']['index.php']) &&
                isset($this->_deploymentConfig[$this->_stageLive]['files']['maintenance.html'])
        )
        {

            echo 'Closing site with maintenance page' . PHP_EOL;

            if (!$closedIndexExists)
            {
                $success = ssh2_sftp_rename($sftp, $destDirectory . '/index.php', $destDirectory . '/index.php.tmp');
                if (!$success)
                {
                    throw new Exception('Could not close store, unable to move index.php');
                }
            }

            $success = ssh2_exec($connection, 'cp ' . $destDirectory . '/maintenance.html ' . $destDirectory . '/index.php');
            if (!$success)
            {
                throw new Exception('Could not close store, unable to move maintenance page in place');
            }
            ssh2_exec($connection, 'chown 666 ' . $destDirectory . '/index.php');

            return true;
        }

        // If something went wrong last time we want to make sure we are closing the store...
        return $closedIndexExists;
    }

    /**
     * Gets a list of files that have changed since the last deployment...
     *
     * @return array
     */
    public function GetFilesToDeploy()
    {
        if (!isset($this->_deploymentConfig[$this->_stageLive]['files']))
        {
            $this->_deploymentConfig[$this->_stageLive]['files'] = array();
        }

        $copyFiles = array();

        foreach ($this->_files as $fileLocation => $md5)
        {
            if (!isset($this->_deploymentConfig[$this->_stageLive]['files'][$fileLocation]) || $this->_deploymentConfig[$this->_stageLive]['files'][$fileLocation] != $md5)
            {
                $copyFiles[$fileLocation] = $md5;
            }
        }
        return $copyFiles;
    }

    /**
     * Gets the values from the live or staging config and merges it into the main config
     *
     * @param string $configDirectory
     * @param string $basePath
     * @return void
     */
    public function MergeConfigFiles($configDirectory, $basePath)
    {
        // Load the original config contents...
        $configFilePath = CodeGenHelpers::BuildPath($configDirectory, 'KrisConfig.php');
        $configFile = file_get_contents($configFilePath);

        // Load the merged config contents...
        $configMerge = CodeGenHelpers::BuildPath($configDirectory, $this->_isLive ? 'KrisConfig.php.live' : 'KrisConfig.php.staging');
        $configLines = file($configMerge);

        // Merge the contents
        foreach ($configLines as $configLine)
        {
            if (preg_match('/^\s*([a-z0-9_ $]+)\s*=\s*(.*);/i', $configLine, $matches))
            {
                $configFile = preg_replace('/' . str_replace(array(' ', '$'), array('\s+', '\$'), $matches[1]) . '\s*=\s*(.*);/', $matches[1] . ' = ' . $matches[2] . ';', $configFile);
            }
        }

        // Write it to file....
        file_put_contents($configFilePath, $configFile);

        // Remove Live and Staging from the deploy files...
        $liveConfig = CodeGenHelpers::BuildPath($configDirectory, 'KrisConfig.php.live');
        unlink($liveConfig);
        $basePathLength = strlen($basePath) + 1;
        unset($this->_files[substr($liveConfig, $basePathLength)]);
        $stagingConfig = CodeGenHelpers::BuildPath($configDirectory, 'KrisConfig.php.staging');
        unlink($stagingConfig);
        unset($this->_files[substr($stagingConfig, $basePathLength)]);

        $configFilename = substr($configFilePath, $basePathLength);

        // Recompute the hash for config...
        $this->_files[$configFilename] = md5_file($configFilePath);

    }


    /**
     * @param string $source
     * @param string $destinationDirectory
     * @param int $process (really an enum of ActionCopy, ActionMerge, ActionCompress)
     * @param string $tmpDir
     * @return void
     */
    private function CopyDirectoryRecursive($source, $destinationDirectory, $process = SiteDeploy::ActionCopy, $tmpDir = '')
    {
        // Create an iterator to go through all of the release files...
        $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($source), RecursiveIteratorIterator::SELF_FIRST);
        $sourceLen = strlen($source) + 1;

        if (strlen($destinationDirectory) > 0)
        {
            mkdir($destinationDirectory, 0777, true);
        }

        $baseDirLength = strlen($this->_deploymentConfig['temp_dir']) + 1;

        foreach ($iterator as $path)
        {
            /** @var $path SplFileInfo */
            $sourceLocation = $path->__toString();

            // If is a special file (.svn), but not an htaccess file then ignore...
            if (strpos($sourceLocation, DIRECTORY_SEPARATOR . '.') > 0 && basename($sourceLocation) != '.htaccess')
            {
                continue;
            }

            $destinationPath = substr($sourceLocation, $sourceLen);
            $destLocation = CodeGenHelpers::BuildPath($destinationDirectory, $destinationPath);
            if ($path->isDir())
            {
                if (strlen($destinationDirectory) > 0)
                {
                    mkdir($destLocation);
                }
            }
            else
            {
                if ($process == SiteDeploy::ActionCopy)
                {
                    copy($sourceLocation, $destLocation);
                }
                else
                {
                    if ($process == SiteDeploy::ActionMerge)
                    {
                        $html = file_get_contents($sourceLocation);
                        $cssJsFileBase = $this->ConvertPathToLongName($destinationPath);
                        $html = $this->ProcessHtmlForCss($html, $cssJsFileBase, $tmpDir);
                        $html = $this->ProcessHtmlForJavascript($html, $cssJsFileBase, $tmpDir);
                        file_put_contents($destLocation, $html);
                    }
                    else
                    {
                        if ($process == SiteDeploy::ActionCompress)
                        {
                            $destLocation = '';
                            $ext = pathinfo($sourceLocation, PATHINFO_EXTENSION);
                            if (strpos($sourceLocation, '.min.js') === false && ($ext == 'js' || $ext == 'css'))
                            {
                                $output = '';
                                $error = '';
                                $res = $this->runExternal('java -jar ' . $this->_deploymentConfig['yui-location'] . ' -o "' . $sourceLocation . '.tmp" "' . $sourceLocation . '"', $output, $error);
                                if ($res == 0 && file_exists($sourceLocation . '.tmp'))
                                {
                                    unlink($sourceLocation);
                                    rename($sourceLocation . '.tmp', $sourceLocation);
                                    $destLocation = $sourceLocation;

                                    if (strlen($error) > 0)
                                    {
                                        echo 'Error compressing file: ' . $sourceLocation . ' ' . PHP_EOL . $error . PHP_EOL . PHP_EOL;
                                    }
                                }
                                else
                                {
                                    if (strlen($error) > 0)
                                    {
                                        echo 'Error compressing file: ' . $sourceLocation . ' ' . PHP_EOL . $error . PHP_EOL . PHP_EOL;
                                    }
                                    else
                                    {
                                        echo 'Cannot compress file: ' . $sourceLocation . '. unknown Error.' . PHP_EOL;
                                    }
                                }
                            }

                        }
                    }
                }
                if (strlen($destLocation) > 0)
                {
                    $this->_files[substr($destLocation, $baseDirLength)] = md5_file($destLocation);
                }
            }
        }
    }

    /**
     * Copies a single file
     *
     * @param string $file
     * @param string $tmpDir
     * @return void
     */
    private function CopySingleFile($file, $tmpDir)
    {
        $destLocation = CodeGenHelpers::BuildPath($tmpDir, $file);
        copy(CodeGenHelpers::BuildPath($this->_location, $file), $destLocation);
        $this->_files[$file] = md5_file($destLocation);
    }

    /**
     * Cleans up the temp directory...
     *
     * @param string $tmpDir
     * @return void
     */
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
                /** @var $path SplFileInfo */
                if ($path->isDir())
                {
                    rmdir($path->__toString());
                }
                else
                {
                    if ($path->isFile())
                    {
                        unlink($path->__toString());
                    }
                }
            }
        }
    }

    /**
     * Runs through an html file and pulls out all the javascript files that are local and merges them into a single file.
     * Speeds up a site by only having one JS file to download...
     *
     * @param string $html
     * @param string $jsFileBase
     * @param string $tmpDir
     * @return string
     */
    private function ProcessHtmlForJavascript($html, $jsFileBase, $tmpDir)
    {
        if (preg_match_all('/<script((\s+[a-z]+="[^"]*")*).*<\/script>/', $html, $matches, PREG_SET_ORDER))
        {
            $scripts = array();

            foreach ($matches as $script_match)
            {
                if (preg_match_all('/([a-z]+)="([^"]*)"/', $script_match[1], $attribute_match, PREG_SET_ORDER))
                {
                    $attributes = array('src' => '');

                    foreach ($attribute_match as $attribute)
                    {
                        $attributes[$attribute[1]] = $attribute[2];
                    }

                    if (preg_match('/^(<\?= +\$WEB_FOLDER;? +\?>|{{WEB_FOLDER}})/', $attributes['src'], $matchHref))
                    {
                        $scripts[] = array('script' => $script_match[0], 'location' => str_replace($matchHref[0], $this->_location, $attributes['src']),
                            'template' => substr($matchHref[1], 0, 2) == '<?' ? 'php' : 'mustache');
                    }
                }
            }

            if (count($scripts))
            {
                $javascript = '';
                $replace_script = '<script src="' . ($scripts[0]['template'] == 'php' ? '<?= $WEB_FOLDER ?>' : '{{WEB_FOLDER}}') .
                        '/js/' . $jsFileBase . 'Joined.js?'.time().'"></script>';

                $html = str_replace($scripts[0]['script'], $replace_script, $html);
                for ($i = 1; $i < count($scripts); $i++)
                {
                    $html = str_replace($scripts[$i]['script'], '', $html);
                }

                foreach ($scripts as $link)
                {
                    $javascript .= file_get_contents($link['location']).PHP_EOL;
                }

                $joinedJsLocation =  CodeGenHelpers::BuildPath('js', $jsFileBase . 'Joined.js');
                $destLocation = CodeGenHelpers::BuildPath($tmpDir, $joinedJsLocation);
                file_put_contents($destLocation, $javascript);
                $this->_files[$joinedJsLocation] = md5_file($destLocation);

            }
        }

        return $html;
    }

    /**
     * Runs through an html file and pulls out all the css files that are local and merges them into a single file.
     * Speeds up a site by only having one css file to download...
     *
     * @param string $html
     * @param string $cssFileBase
     * @param string $tmpDir
     * @return string
     */
    private function ProcessHtmlForCss($html, $cssFileBase, $tmpDir)
    {
        if (preg_match_all('/<link((\s+[a-z]+="[^"]*")*)\s*\/?>/', $html, $matches, PREG_SET_ORDER))
        {
            $links = array();

            foreach ($matches as $link_match)
            {
                if (preg_match_all('/([a-z]+)="([^"]*)"/', $link_match[1], $attribute_match, PREG_SET_ORDER))
                {
                    $attributes = array('rel' => '', 'media' => 'screen');
                    foreach ($attribute_match as $attribute)
                    {
                        $attributes[$attribute[1]] = $attribute[2];
                    }
                    if ($attributes['rel'] == 'stylesheet' && $attributes['media'] == 'screen' &&
                            preg_match('/^(<\?= +\$WEB_FOLDER;? +\?>|{{WEB_FOLDER}})/', $attributes['href'], $matchHref)
                    )
                    {
                        $links[] = array('link' => $link_match[0], 'location' => str_replace($matchHref[0], $this->_location, $attributes['href']),
                            'template' => substr($matchHref[1], 0, 2) == '<?' ? 'php' : 'mustache');
                    }
                }
            }

            if (count($links))
            {
                $stylesheet = '';
                $replace_stylesheet = '<link rel="stylesheet" href="' . ($links[0]['template'] == 'php' ? '<?= $WEB_FOLDER ?>' : '{{WEB_FOLDER}}') .
                        '/css/' . $cssFileBase . 'Joined.css?'.time().'" type="text/css" media="screen" />';

                $html = str_replace($links[0]['link'], $replace_stylesheet, $html);
                for ($i = 1; $i < count($links); $i++)
                {
                    $html = str_replace($links[$i]['link'], '', $html);
                }

                foreach ($links as $link)
                {
                    $stylesheet .= file_get_contents($link['location']);
                }

                $joinedCss = CodeGenHelpers::BuildPath('css', $cssFileBase . 'Joined.css');
                $destLocation = CodeGenHelpers::BuildPath($tmpDir, $joinedCss);
                file_put_contents($destLocation, $stylesheet);
                $this->_files[$joinedCss] = md5_file($destLocation);
            }


        }

        return $html;
    }

    /**
     * Used to generate merged CSS and JS path names
     *
     * @param string $destinationPath
     * @return mixed
     */
    private function ConvertPathToLongName($destinationPath)
    {
        $destinationPath = str_replace(' ', '', ucwords(str_replace(array('/', '\\'), ' ', $destinationPath)));
        return pathinfo($destinationPath, PATHINFO_FILENAME);
    }

    /**
     * Processes an external application and allows reading of stdError on both unix and windows...
     *
     * @param string $cmd
     * @param string $output (out)
     * @param string $errorOut (out)
     * @return bool
     */
    private function runExternal($cmd, &$output, &$errorOut)
    {
        $descriptors = array(
            0 => array("pipe", "r"), // stdin is a pipe that the child will read from
            1 => array("pipe", "w"), // stdout is a pipe that the child will write to
            2 => array("pipe", "w") // stderr is a file to write to
        );

        $pipes = array();
        $process = proc_open($cmd, $descriptors, $pipes);

        $output = "";
        $errorOut = "";

        if (!is_resource($process))
        {
            return false;
        }

        #close child's input immediately
        fclose($pipes[0]);

        stream_set_blocking($pipes[1], false);
        stream_set_blocking($pipes[2], false);

        while (true)
        {
            /** @var $stdout resource|bool */
            $stdout = false;
            /** @var $error resource|bool */
            $error = false;
            $read = array();

            if (!feof($pipes[1]))
            {
                $stdout = $pipes[1];
                $read[] = $stdout;
            }
            if (!feof($pipes[2]))
            {
                $error = $pipes[2];
                $read[] = $error;
            }

            if (!$read)
            {
                break;
            }

            $write = NULL;
            $ex = NULL;

            $ready = stream_select($read, $write, $ex, 2);

            if ($ready === false)
            {
                break; #should never happen - something died
            }

            if ($stdout)
            {
                $output .= fread($stdout, 1024);
            }
            if ($error)
            {
                $errorOut .= fread($error, 1024);
            }
        }

        fclose($pipes[1]);
        fclose($pipes[2]);

        return proc_close($process);

    }

    /**
     * @param string $configDirectory
     * @return array
     * @throws Exception
     */
    private function GetSqlConfigData($configDirectory)
    {
        $configFilename = CodeGenHelpers::BuildPath($configDirectory, 'KrisConfig.php');
        $configFile = file_get_contents($configFilename);

        $res = array('host' => '', 'user' => '', 'password' => '', 'database' => '');

        foreach (array_keys($res) as $key)
        {
            if (!preg_match('/const\s+DB_' . strtoupper($key) . '\s+=\s+[\'"]([^\'"]+)[\'"]/', $configFile, $match))
            {
                throw new Exception('Could not find SQL Config for ' . $key . ' in KrisConfig.php');
            }
            $res[$key] = $match[1];
        }


        return $res;

    }

    /**
     * @param string $tmpDir
     * @param string $destDirectory
     */
    private function FixHtaccessFile($tmpDir, $destDirectory)
    {
        $oldRewriteBase = basename($this->_location);
        $newRewriteBase = basename($destDirectory);
        $htaccessFileLocation = CodeGenHelpers::BuildPath($tmpDir, '.htaccess');
        $htaccess = file_get_contents($htaccessFileLocation);
        $htaccess = str_replace('/' . $oldRewriteBase . '/', '/' . $newRewriteBase . '/', $htaccess);
        file_put_contents($htaccessFileLocation, $htaccess);
        $this->_files['.htaccess'] = md5_file($htaccessFileLocation);
    }

    /**
     * @param string $jsonString
     * @return string
     */
    private function json_encode_pretty($jsonString)
    {
        $out = '';
        $indent = 0;
        $isText = false;

        for ($i = 0; $i < strlen($jsonString); $i++)
        {
            $character = $jsonString[$i];
            $breakBefore = $breakAfter = false;
            $charBefore = $charAfter = '';

            if ($character === '"' && ($i > 0 && substr($jsonString, $i - 1, 1) !== '\\'))
            {
                $isText = !$isText;
            }

            // toggle
            if (!$isText)
            {
                switch ($character)
                {
                    case '[':
                    /** @noinspection PhpMissingBreakStatementInspection */
                    case '{':
                        $indent++;
                    // no break - DO NOT PUT A BREAK HERE, the fall through is intentional
                    case ',':
                        $breakAfter = true;
                        break;
                    case ']':
                    case '}':
                        $indent--;
                        $breakBefore = true;
                        break;
                    case ':':
                        $charBefore = $charAfter = ' ';
                        break;
                }
            }
            $out .= ($breakBefore ? PHP_EOL . str_repeat(' ', $indent) : '')
                    . $charBefore . $character . $charAfter
                    . ($breakAfter ? PHP_EOL . str_repeat(' ', $indent) : '');
        }
        return $out;
    }

    /**
     * @param string $destPath
     * @param resource $sftp
     */
    private function GetDeploymentFiles($destPath, $sftp)
    {
        $destDir = CodeGenHelpers::BuildPath($destPath, 'deploy', true);
        if (!$this->RemoteDirectoryExists($sftp, $destDir))
        {
            ssh2_sftp_mkdir($sftp, $destDir, 0777, true);
            $fp = fopen('ssh2.sftp://' . $sftp . CodeGenHelpers::BuildPath($destDir, '.htaccess', true), 'w');
            fwrite($fp, 'deny from all');
            fclose($fp);

        }
        else
        {
            $fp = fopen('ssh2.sftp://' . $sftp . CodeGenHelpers::BuildPath($destDir, 'deployFiles.json', true), 'r');
            if ($fp)
            {
                $json = '';
                while (($buffer = fgets($fp, 4096)) !== false)
                {
                    $json .= $buffer;
                }
                fclose($fp);
                $this->_deploymentConfig[$this->_stageLive]['files'] = json_decode($json, true);
            }
        }
    }

    /**
     * @param string $tmpDir
     * @param string $destPath
     * @param resource $connection
     * @param resource $sftp
     */
    private function SaveDeploymentFiles($tmpDir, $destPath, $connection, $sftp)
    {
        $deployFileList = CodeGenHelpers::BuildPath($tmpDir, 'deployFileList.txt');
        file_put_contents($deployFileList, $this->json_encode_pretty(json_encode($this->_deploymentConfig[$this->_stageLive]['files'])));
        $this->UploadFile($deployFileList, CodeGenHelpers::BuildPath(CodeGenHelpers::BuildPath($destPath, 'deploy', true), 'deployFiles.json', true), $connection, $sftp);
    }

    /**
     * @param string $backupDir
     * @throws Exception
     */
    public function BackupSql($backupDir) {


        $connection = $this->GetConnection();

        $this->backupSqlFromConnection($connection, $backupDir);

    }

    /**
     * @param $backupDir
     */
    public function BackupAll($backupDir)
    {
        $connection = $this->GetConnection();

        $this->backupSqlFromConnection($connection, $backupDir);

        $this->backupUploadsFromConnection($connection, $backupDir);
    }

    /**
     * @param string $backupDir
     * @throws Exception
     */
    public function BackupUploads($backupDir) {


        $connection = $this->GetConnection();

        $this->backupUploadsFromConnection($connection, $backupDir);

    }

    /**
     * @param resource $connection
     * @param string $backupDir
     */
    private function backupSqlFromConnection($connection, $backupDir) {
        $configDirectory = $this->createConfigDirectory();

        $sqlBackupLocation = '/tmp/sql.dump.gz';

        $this->remoteBackupSql($connection, $configDirectory, $sqlBackupLocation);

        ssh2_scp_recv($connection, $sqlBackupLocation, CodeGenHelpers::BuildPath($backupDir, basename($this->_location) . date('-Y-m-d') . '.sql.gz'));

        $this->ExecuteSSHCommand($connection, 'rm '.$sqlBackupLocation, $outputString, $errorString);
    }

    /**
     * @return resource
     * @throws Exception
     */
    private function GetConnection() {
        $connection = ssh2_connect($this->_deploymentConfig[$this->_stageLive]['host'], $this->_deploymentConfig[$this->_stageLive]['port']);

        if (!$connection) {
            throw new Exception('Invalid host:' . $this->_deploymentConfig[$this->_stageLive]['host'] . ' nothing listening on port: ' . $this->_deploymentConfig[$this->_stageLive]['port']);
        }

        if (!ssh2_auth_password($connection, $this->_deploymentConfig[$this->_stageLive]['username'], $this->_deploymentConfig[$this->_stageLive]['password'])) {
            throw new Exception('Invalid username password for host:' . $this->_deploymentConfig[$this->_stageLive]['host']);
        }
        return $connection;
    }

    /**
     * @param resource $connection
     * @param string $configDirectory
     * @param $sqlBackupLocation
     * @throws Exception
     */
    private function remoteBackupSql($connection, $configDirectory, $sqlBackupLocation) {


        $sqlConfig = $this->GetSqlConfigData($configDirectory);

        $cmd = 'mysqldump --user='. $sqlConfig['user'].' --password='. $sqlConfig['password'].' --host='. $sqlConfig['host'].' --opt '. $sqlConfig['database'].' | gzip > '.$sqlBackupLocation;

        echo 'SQL Dump ' . $cmd . PHP_EOL;

        $outputString = '';
        $errorString = '';

        $this->ExecuteSSHCommand($connection, $cmd, $outputString, $errorString);

        echo $outputString;

        if (strlen($errorString)) {
            throw new Exception('SQL Dump [' . trim($errorString) . ']');
        }
    }

    /**
     * @param $connection
     * @param $backupDir
     * @throws Exception
     */
    private function backupUploadsFromConnection($connection, $backupDir) {
        $uploadBackupLocation = '/tmp/uploadsBackup.tgz';


        $cmd = 'tar --create --gzip --absolute-names --verbose --file='.$uploadBackupLocation.' --directory='. $this->_deploymentConfig[$this->_stageLive]['dest_dir'].' uploads';

        echo 'Backup up  "' . $cmd .'"'. PHP_EOL;

        $outputString = '';
        $errorString = '';

        $this->ExecuteSSHCommand($connection, $cmd, $outputString, $errorString);

        echo $outputString;

        if (strlen($errorString)) {
            throw new Exception('Taring uploads [' . trim($errorString) . ']');
        }

        ssh2_scp_recv($connection, $uploadBackupLocation, CodeGenHelpers::BuildPath($backupDir, basename($this->_location) . date('-Y-m-d') . '-uploads.tgz'));

        $this->ExecuteSSHCommand($connection, 'rm ' . $uploadBackupLocation, $outputString, $errorString);

        echo $outputString;

        if (strlen($errorString)) {
            throw new Exception('Removing backed up file [' . trim($errorString) . ']');
        }
    }

}
