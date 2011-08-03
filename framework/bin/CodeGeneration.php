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
 * Used for code generation...
 * @package CodeGeneration
 */
class KrisCG extends KrisDB
{

    const UNDERSCORE_PLACEHOLDER = '+=+';

    private $_applicationDirectory;
    private $_baseModelDirectory;
    private $_generatedDirectory;
    private $_crudDirectory;
    private $_siteLocation;

    /**
     * Constructor
     * @param string $siteLocation
     * @return KrisCG
     */
    public function __construct($siteLocation)
    {
        $this->_siteLocation = $siteLocation;
    }

    /**
     * @return void
     */
    public function SetupDirectories()
    {
        $this->_applicationDirectory =  $this->_siteLocation . DIRECTORY_SEPARATOR . KrisConfig::APP_PATH;
        $this->_baseModelDirectory = $this->_applicationDirectory . 'models';
        $this->_generatedDirectory = $this->_baseModelDirectory . DIRECTORY_SEPARATOR . 'generated';
        $this->_crudDirectory = $this->_baseModelDirectory . DIRECTORY_SEPARATOR . 'crud';


    }

    /**
     * Includes the config file...
     *
     * @return void
     */
    public function IncludeConfigFile()
    {
        $configLocation = $this->_siteLocation.'/config/KrisConfig.php';

        if (!file_exists($configLocation))
        {
            die('Config file does not exist at '.$configLocation);
        }

        require_once($configLocation);

        if (!class_exists('KrisConfig'))
        {
            die('File located at '.$configLocation.' not a valid config file');
        }

        $factory = array('PDO' => create_function('$container', '$dsn = "mysql:host=".KrisConfig::DB_HOST.";dbname=".KrisConfig::DB_DATABASE;'.PHP_EOL.
            'return new PDO($dsn, KrisConfig::DB_USER, KrisConfig::DB_PASSWORD);'));

        AutoLoader::$Container = new BucketContainer($factory);

        $this->SetupDirectories();
    }

    /**
     * Creates a new site in location
     * @param string $site
     * @param string $host
     * @param string $database
     * @param string $user
     * @param string $password
     * @param string $databaseType     *
     * @param string $viewType
     * @param string $siteName
     * @return void
     */
    public function CreateSite($site, $host, $database, $user, $password, $databaseType, $viewType, $siteName)
    {

        $site = $this->EnsureSiteExists($site);
        $webFolder = '/'.basename($site);

        $configLocation = $this->_siteLocation . '/config/KrisConfig.php';
        if (file_exists($configLocation))
        {
            throw new Exception('Cannot create a site where one already exists');
        }

        if (!in_array($databaseType, array('MYSQL', 'MSSQL', 'SQLITE', 'POSTGRESQL')))
        {
            throw new Exception('Database type ('.$databaseType.') invalid, must be be one of MYSQL, MSSQL, SQLITE, POSTGRESQL');
        }

        $viewTypeLower = strtolower($viewType);
        if ($viewTypeLower == 'kris' || $viewTypeLower == 'krisview')
        {
            $templateExtension = 'php';
            $templateType = 'KrisView';
        }
        else if ($viewTypeLower == 'mustache' || $viewTypeLower == 'mustacheview')
        {
            $templateExtension = 'tpl';
            $templateType = 'MustacheView';
        }
        else
        {
            throw new Exception('Unsupported template type: '.$viewType);
        }

        $this->CreateDirectoryOrDie($this->_siteLocation.DIRECTORY_SEPARATOR.'config');

        $m = new Mustache();
        $configContents = $m->render(file_get_contents(__DIR__.'/assets/CodeTemplates/KrisConfig.template'),
            array('framework_dir' => dirname(__DIR__), 'web_folder' => $webFolder, 'site_location' => $this->_siteLocation,
                'db_host' => $host, 'db_database' => $database, 'db_user' => $user, 'db_password' => $password,
                'db_type' => 'KrisConfig::DB_TYPE_'.$databaseType));

        file_put_contents($configLocation, $configContents);

        $this->IncludeConfigFile();

        // Create the rest of the directories..
        $requiredDirectories = array($this->_applicationDirectory, $this->_applicationDirectory.'/controllers/main', $this->_applicationDirectory.'/library',
            $this->_applicationDirectory.'/views/layouts', $this->_applicationDirectory.'/views/main', $this->_baseModelDirectory, $this->_siteLocation.'/css',
            $this->_siteLocation.'/images', $this->_siteLocation.'/js');

        foreach ($requiredDirectories as $requiredDirectory)
        {
            $this->CreateDirectoryOrDie($requiredDirectory);
        }

        // Create the blocking htaccess files...
        $htaccessDeny = 'deny from all';
        file_put_contents($this->_siteLocation.'/config/.htaccess', $htaccessDeny);
        file_put_contents($this->_applicationDirectory.'/.htaccess', $htaccessDeny);

        // Create the index file and .htaccess
        $htaccessContents = file_get_contents(__DIR__.'/assets/.htaccess');
        $htaccessContents = str_replace('@@WEB_FOLDER@@', $webFolder, $htaccessContents);
        file_put_contents($this->_siteLocation.'/.htaccess', $htaccessContents);

        copy(__DIR__.'/assets/index.php', $this->_siteLocation.'/index.php');

        $mainControllerContents = $m->render(file_get_contents(__DIR__.'/assets/CodeTemplates/MainController.template'),
            array('layout_template' => 'layout.'.$templateExtension, 'main_template' => 'MainView.'.$templateExtension,
                'sitename' => $siteName, 'template_type' => $templateType));
        file_put_contents($this->_applicationDirectory.'/controllers/main/MainController.php', $mainControllerContents);

        copy(__DIR__.'/assets/DefaultView'.$templateType.'/layout.'.$templateExtension, $this->_applicationDirectory.'/views/layouts/layout.'.$templateExtension);
        copy(__DIR__.'/assets/DefaultView'.$templateType.'/MainView.'.$templateExtension, $this->_applicationDirectory.'/views/main/MainView.'.$templateExtension);
        copy(__DIR__.'/assets/css/style.css', $this->_siteLocation.'/css/style.css');

        echo 'Site created at '.$this->_siteLocation.PHP_EOL;

    }

    /**
     * @throws Exception
     * @param string $site
     * @return string
     */
    public function EnsureSiteExists($site)
    {
        if (strtolower(substr($site, 0, 7)) != 'http://')
        {
            $site = 'http://' . $site;
        }
        $fp = @fopen($site, 'r');
        if ($fp === false)
        {
            $site .= '/';
            $fp = @fopen($site, 'r');
            if ($fp === false)
            {
                throw new Exception('Could not access ' . $site . '  Please make the site accessible before starting...');
            }
        }
        fclose($fp);
        return $site;
    }


    /**
     * Generates a model based on a table name
     * @param string $tableName
     * @return void
     */
    public function GenerateModel($tableName)
    {
        if (!file_exists($this->_generatedDirectory))
        {
            mkdir($this->_generatedDirectory);
        }
        if (!file_exists($this->_crudDirectory))
        {
            mkdir($this->_crudDirectory);
        }

        $tableName = strtolower($tableName);

        if (!$this->TableExists($tableName))
        {
            throw new Exception('ERROR: Table '.$tableName.' does not exist');
        }

        $columnNames = $this->GetColumnMetadata($tableName);

        $foreignKeys = $this->GetForeignKeys($tableName, array_keys($columnNames));

        $className = $this->convertDBKeyToClassKey($tableName);

        echo 'Generating class '.$className.PHP_EOL;

        $safeClassName = $className.'View';


        list($properties, $primaryKey, $initializeFields, $fakeFields, $fieldTypes) = $this->GetPropertiesPrimaryKey($columnNames, $foreignKeys);

        $foreignKeyString = $this->GetForeignKeyString($foreignKeys);

        $filename = $safeClassName . '.php';
        
        $this->GenerateBaseClass($tableName, $className, $filename, $properties, $foreignKeyString, $initializeFields, $fakeFields, $primaryKey, $fieldTypes);

        // Don't overwrite a class that has changes....
        if (!file_exists($this->_crudDirectory . DIRECTORY_SEPARATOR . $filename))
        {
            $this->GenerateDerivedClass($filename, $safeClassName, $className);
        }
    }

    

    /**
     * @param $columnNames
     * @param $foreignKeys
     * @return array ($properties, $primaryKey, $initializeFields, $fakeFields, $fieldTypes)
     */
    private function GetPropertiesPrimaryKey($columnNames, $foreignKeys)
    {
        $properties = '';
        $primaryKey = '';
        $initializeFields = '';
        $fakeFields = '';
        $fieldTypes = '';

        foreach ($columnNames as $columnName => $columnData)
        {
            $dbKey = $this->convertDBKeyToClassKey($columnName);
            $properties .= '* @property ' . $columnData['type'] . ' $' . $dbKey . PHP_EOL;
            $initializeFields .= (strlen($initializeFields) > 0 ? ', ' : '')."'$dbKey'";
            $fieldTypes .= (strlen($fieldTypes) > 0 ? ', ' : '')."'$dbKey' => '".$columnData['type']."'";

            if ($columnData['primary'])
            {
                $primaryKey = $columnName;
            }
        }

        foreach ($foreignKeys as $foreignKeyId => $foreignKeyData)
        {
            $dbKey = $this->convertDBKeyToClassKey($foreignKeyData['alias']);
            $properties .= '* @property string $' . $dbKey . PHP_EOL;
            $initializeFields .= (strlen($initializeFields) > 0 ? ', ' : '')."'$dbKey'";
            $fakeFields .= (strlen($fakeFields) > 0 ? ', ' : '')."'$dbKey' => '$foreignKeyId'";
        }

        $initializeFields = '$this->initializeRecordSet(array('.$initializeFields.'));'.PHP_EOL;
        if (strlen($fakeFields) > 0)
        {
            $fakeFields = 'protected $_fakeFields = array('.$fakeFields.');';
        }
        return array($properties, $primaryKey, $initializeFields, $fakeFields, $fieldTypes);
    }

    /**
     * Returns all the foreign keys...
     *
     * @param $foreignKeys
     * @return string
     */
    private function GetForeignKeyString($foreignKeys)
    {
        if (count($foreignKeys) > 0)
        {
            $foreignKeyString = 'protected $_foreignKeys = array(';
            $first = true;
            foreach ($foreignKeys as $foreignKey => $foreignKeyProperties)
            {
                if (!$first)
                {
                    $foreignKeyString .= ', ' . PHP_EOL . '       ';
                }
                $foreignKeyString .= "'$foreignKey' => array('table' => '" . $foreignKeyProperties['table'] . "', ".
                    "'field' => '" . $foreignKeyProperties['field'] . "', 'display' => '".$foreignKeyProperties['display']."', ".
                    "'alias' => '" . $foreignKeyProperties['alias'] . "')";
                $first = false;
            }
            $foreignKeyString .= ');' . PHP_EOL;
            return $foreignKeyString;
        }
        return '';
    }


    /**
     * Outputs the base class to a file..
     *
     * @param $tableName
     * @param $className
     * @param $filename
     * @param $properties
     * @param $foreignKeyString
     * @param $initializeFields
     * @param $fakeFields
     * @param $primaryKey
     * @param $fieldTypes
     * @return void
     */
    private function GenerateBaseClass($tableName, $className, $filename, $properties, $foreignKeyString, $initializeFields,
        $fakeFields, $primaryKey, $fieldTypes)
    {

        $m = new Mustache();
        $output = $m->render(file_get_contents($this->BuildPath($this->BuildPath($this->BuildPath(__DIR__, 'assets'), 'CodeTemplates'), 'ModelGenerated.template')),
            array('BaseModelDirectory' => $this->_baseModelDirectory, 'tableName' => $tableName, 'className' => $className, 'filename' => $filename,
            'properties' => $properties, 'foreignKeyString' => $foreignKeyString, 'initializeFields' => $initializeFields, 'fakeFields' => $fakeFields,
            'primaryKey' => $primaryKey, 'fieldTypes' => $fieldTypes));

        file_put_contents($this->BuildPath($this->_generatedDirectory, $className . 'Model' . '.php'), $output);
    }

    /**
     * @param $filename
     * @param $safeClassName
     * @param $className
     * @return void
     */
    private function GenerateDerivedClass($filename, $safeClassName, $className)
    {
        $m = new Mustache();
        $output = $m->render(file_get_contents(__DIR__.'/assets/CodeTemplates/ModelCrud.template'), array('filename' => $filename, 'safeClassName' => $safeClassName, 'className' => $className));
        file_put_contents($this->_crudDirectory . DIRECTORY_SEPARATOR . $filename, $output);
    }

    /**
     * @param string $controllerLocation
     * @param string $controllerName
     * @param string $viewType
     * @param string $viewLocation
     * @return void
     */
    public function CreateScaffold($controllerLocation, $controllerName, $viewType, $viewLocation)
    {
        $m = new Mustache();

        $controllerDirectory = $this->BuildPath($this->BuildPath($this->_applicationDirectory,'controllers'), $controllerLocation);

        $assetDir = $this->BuildPath(__DIR__, 'assets');

        if ($viewType == 'KrisView' || $viewType == 'Kris')
        {
            $viewView = 'ViewView.php';
            $editView = 'EditView.php';
            $indexView = 'IndexView.php';
            $scaffoldMainLayout = 'Scaffold.php';
            $viewFolder = 'ScaffoldViewKrisView';
            $viewType = 'KrisView';
        }
        else if ($viewType == 'Mustache' || $viewType == 'MustacheView')
        {
            $viewView = 'ViewView.tpl';
            $editView = 'EditView.tpl';
            $indexView = 'IndexView.tpl';
            $scaffoldMainLayout = 'Scaffold.tpl';
            $viewFolder = 'ScaffoldViewMustacheView';
            $viewType = 'MustacheView';
        }
        else
        {
            throw new InvalidArgumentException('View type: '.$viewType.' is not supported');
        }

        $templateDir = $this->BuildPath($assetDir, $viewFolder);

        $this->CreateDirectoryOrDie($controllerDirectory);

        $output = $m->render(file_get_contents($this->BuildPath($this->BuildPath($assetDir,'CodeTemplates') ,'ScaffoldController.template')),
            array('ControllerLocation' => $controllerLocation, 'ControllerName' => $controllerName, 'ScaffoldMainLayout' => $scaffoldMainLayout,
                'ViewLocation' => $viewLocation, 'ViewView' => $viewView, 'EditView' => $editView, 'IndexView' => $indexView, 'ViewType' => $viewType));

        file_put_contents($this->BuildPath($controllerDirectory, ucfirst($controllerLocation).'Controller.php'), $output);

        copy($this->BuildPath($templateDir,$scaffoldMainLayout), $this->BuildPath($this->BuildPath($this->BuildPath($this->_applicationDirectory, 'views'), 'layouts'), $scaffoldMainLayout));
        
        $this->CreateDirectoryOrDie($this->BuildPath($this->BuildPath($this->_applicationDirectory, 'views'), $viewLocation));

        copy($this->BuildPath($templateDir, $viewView), $this->BuildPath($this->BuildPath($this->BuildPath($this->_applicationDirectory, 'views') , $viewLocation), $viewView));
        copy($this->BuildPath($templateDir, $editView), $this->BuildPath($this->BuildPath($this->BuildPath($this->_applicationDirectory, 'views'), $viewLocation), $editView));
        copy($this->BuildPath($templateDir, $indexView), $this->BuildPath($this->BuildPath($this->BuildPath($this->_applicationDirectory, 'views'), $viewLocation), $indexView));

        // TODO: Add the ability to add different css's 
        copy($this->BuildPath($this->BuildPath($assetDir, 'css'), 'scaffold.css'), $this->BuildPath($this->BuildPath($this->_siteLocation, 'css'), 'scaffold.css'));
        $this->CreateDirectoryOrDie($this->BuildPath($this->BuildPath($this->_siteLocation, 'images'), 'scaffold'));


        $imageSource = $this->BuildPath($assetDir, 'ScaffoldImages');
        $d = dir($imageSource);

        $scaffoldImagesDir = $this->BuildPath($this->BuildPath($this->_siteLocation, 'images'), 'scaffold');
        $this->CreateDirectoryOrDie($scaffoldImagesDir);

        while($res = $d->read())
        {
            if ($res != '.' && $res != '..')
            {
                copy($this->BuildPath($imageSource, $res), $this->BuildPath($scaffoldImagesDir, $res));
            }
        }
    }

    /**
     * @param $table
     * @return array
     */
    private function GetColumnMetadata($table)
    {
        $dbh = $this->getDatabaseHandle();

        $stmt = $dbh->prepare("select COLUMN_NAME, DATA_TYPE, COLUMN_KEY from INFORMATION_SCHEMA.COLUMNS where TABLE_NAME = ? AND TABLE_SCHEMA = ?");

        $columnNames = array();

        if ($stmt->execute(array($table, KrisConfig::DB_DATABASE)))
        {
            $raw_column_data = $stmt->fetchAll(PDO::FETCH_ASSOC);

            foreach ($raw_column_data as $column_data)
            {
                $columnNames[$column_data['COLUMN_NAME']] = array('type' => $this->GetTypeFromDataType($column_data['DATA_TYPE']),
                    'displayType' => $column_data['DATA_TYPE'], 'primary' => $column_data['COLUMN_KEY'] == 'PRI');
            }
        }

        return $columnNames;

    }

    /**
     * @param $table
     * @param $usedColumnNames
     * @return array
     */
    private function GetForeignKeys($table, $usedColumnNames)
    {
        $dbh = $this->getDatabaseHandle();

        $stmt = $dbh->prepare("SELECT COLUMN_NAME, REFERENCED_TABLE_NAME, REFERENCED_COLUMN_NAME FROM information_schema.TABLE_CONSTRAINTS c
            INNER JOIN INFORMATION_SCHEMA.KEY_COLUMN_USAGE cu ON
            (c.CONSTRAINT_NAME = cu.CONSTRAINT_NAME AND cu.TABLE_NAME = c.TABLE_NAME AND cu.TABLE_SCHEMA = c.TABLE_SCHEMA)
            WHERE c.CONSTRAINT_TYPE = ? AND c.TABLE_SCHEMA = ? AND c.TABLE_NAME = ?");

        $foreignKeys = array();

        if ($stmt->execute(array('FOREIGN KEY', KrisConfig::DB_DATABASE, $table)))
        {
            $this->ValidateStatement($stmt);

            $foreign_key_data = $stmt->fetchAll(PDO::FETCH_ASSOC);

            foreach ($foreign_key_data as $foreign_key)
            {
                $foreignKeys[$foreign_key['COLUMN_NAME']] = array('table' => $foreign_key['REFERENCED_TABLE_NAME'],
                    'field' => $foreign_key['REFERENCED_COLUMN_NAME']);

            }
        }



        foreach ($foreignKeys as $column => $colData)
        {
            $referencedTableColumns = $this->GetColumnMetadata($colData['table']);
            $alias = '';
            $aliasCount = 1;
            foreach ($referencedTableColumns as $columnName => $columnData)
            {
                $foreignKeys[$column]['display'] = $columnName;
                while (isset($usedColumnNames[$columnName.$alias]))
                {
                    $alias = '_c'.$aliasCount++;
                }
                $foreignKeys[$column]['alias'] = $columnName.$alias;
                if (!$columnData['primary'] && $columnData['type'] == 'string')
                {
                    $usedColumnNames[$columnName.$alias] = true;
                    break;
                }

            }
        }

        return $foreignKeys;
    }

    /**
     * @param $type
     * @return string
     */
    private function GetTypeFromDataType($type)
    {
        // TODO: Make this work with non-mysql types...
        switch (strtolower($type))
        {
            case 'varchar' : case 'char': case 'set':
                return 'string';
            case 'mediumblob': case 'blob': case 'longblob':
                return 'blob';
            case 'text': case 'mediumtext': case 'tinytext':
                return 'text';
            case 'time': case 'timestamp': case 'datetime': case 'date': case 'enum':
                return $type;
            case 'bigint': case 'longtext': case 'int': case 'mediumint': case 'smallint':
                return 'int';
            case 'tinyint':
                return 'bool';

            case 'decimal': case 'float': case 'double':
                return 'float';

            default:
                return 'mixed'; // Really unknown...

        }
    }

    /**
     * Returns whether or not a table exists..
     *
     * @param $tableName
     * @return bool
     */
    private function TableExists($tableName)
    {
        $dbh = $this->getDatabaseHandle();

        $stmt = $dbh->prepare("SELECT TABLE_NAME FROM information_schema.TABLES WHERE TABLE_SCHEMA = ? AND TABLE_NAME = ?");

        if ($stmt->execute(array(KrisConfig::DB_DATABASE, $tableName)))
        {
            $this->ValidateStatement($stmt);

            return $stmt->rowCount() > 0;
        }

        return false;
    }

    /**
     * @param string $directory
     * @return void
     */
    private function CreateDirectoryOrDie($directory)
    {
        if (!FileHelpers::EnsureDirectoryExists($directory))
        {
            throw new Exception('Failed to create directory: '.$directory);
        }
    }

    /**
     * Returns a path combined
     *
     * @param string $path1
     * @param string $path2
     * @return string
     */
    private function BuildPath($path1, $path2)
    {
        if (substr($path1,-1) != '/' && substr($path1,-1) != '\\')
        {
            $path1 .= DIRECTORY_SEPARATOR;
        }
        return $path1.$path2;
    }


}

?>
