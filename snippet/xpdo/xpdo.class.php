<?php
/*
 * OpenExpedio ("xPDO") is an ultra-light, PHP 4.3+ compatible ORB (Object-
 * Relational Bridge) library based around PDO (http://php.net/pdo/).  It uses
 * native PDO if available or provides a subset implementation for use with PHP
 * 4 on platforms that do not include the native PDO extensions.
 *
 * Copyright 2006, 2007, 2008, 2009 by  Jason Coward <xpdo@opengeek.com>
 *
 * This file is part of xPDO.
 *
 * xPDO is free software; you can redistribute it and/or modify it under the
 * terms of the GNU General Public License as published by the Free Software
 * Foundation; either version 2 of the License, or (at your option) any later
 * version.
 *
 * xPDO is distributed in the hope that it will be useful, but WITHOUT ANY
 * WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR
 * A PARTICULAR PURPOSE. See the GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License along with
 * xPDO; if not, write to the Free Software Foundation, Inc., 59 Temple Place,
 * Suite 330, Boston, MA 02111-1307 USA
 */

/**
 * This is the main file to include in your scripts to use xPDO.
 *
 * It defines PHP 4 and 5-compatible constants along with the core classes used
 * throughout the framework. The constants should be available regardless if you
 * are using native PDO or the OpenExpedio PDO implementation for PHP 4.3+.
 * It is recommended that you use these constants instead of the STATIC class
 * variables in PDO when PHP 4 portability is a concern for your application, as
 * these constants mimic the native PDO static vars which are unusable in PHP 4.
 *
 * @author Jason Coward <xpdo@opengeek.com>
 * @copyright Copyright (C) 2006-2008, Jason Coward
 * @license http://opensource.org/licenses/gpl-2.0.php GNU Public License v2
 * @package xpdo
 */
if (!function_exists('array_combine')) {
    /**
     * Emulates PHP5 array_combine function for PHP 4.
     *
     * @see http:/php.net/function.array_combine
     * @todo Move this to a compatibility include.
     */
    function array_combine($keys, $values) {
        $keys= array_values((array) $keys);
        $values= array_values((array) $values);
        $n= max(count($keys), count($values));
        $r= array ();
        for ($i= 0; $i < $n; $i++) {
            $r[$keys[$i]]= $values[$i];
        }
        return $r;
    }
}
if (!defined('XPDO_PHP_VERSION')) {
    /**
     * Defines the PHP version string xPDO is running under.
     */
    define('XPDO_PHP_VERSION', phpversion());
}
if (!defined('XPDO_PHP4_MODE')) {
    if (version_compare(XPDO_PHP_VERSION, '5.0.0') < 0) {
        /**
         * This constant defines if xPDO is operating under PHP 4.
         */
        define('XPDO_PHP4_MODE', true);
    } else {
        /** @ignore */
        define('XPDO_PHP4_MODE', false);
    }
}
if (!defined('XPDO_CLI_MODE')) {
    if (php_sapi_name() == 'cli') {
        /**
         * This constant defines if xPDO is operating from the CLI.
         */
        define('XPDO_CLI_MODE', true);
    } else {
        /** @ignore */
        define('XPDO_CLI_MODE', false);
    }
}
if (!defined('XPDO_CORE_PATH')) {
    /**
     * @internal This global variable is only used to set the {@link
     * XPDO_CORE_PATH} value upon initial include of this file.  Not meant for
     * external use.
     * @var string
     * @access private
     */
    $xpdo_core_path= strtr(realpath(dirname(__FILE__)), '\\', '/') . '/';
    /**
     * @var string The full path to the xPDO root directory.
     *
     * Use of this constant is recommended for use when building any path in
     * your xPDO code.
     *
     * WARNING: DO NOT undefine XPDO_CORE_PATH at any point or any additional
     * attempts to include this file will fail as the code tries to redefine the
     * additional XPDO_ and PDO_ constants, causing a fatal PHP parser error.
     *
     * @access public
     */
    define('XPDO_CORE_PATH', $xpdo_core_path);

    /**#@+
     * @var string
     * @access public
     */
    define('XPDO_OPT_BASE_CLASSES', 'base_classes');
    define('XPDO_OPT_BASE_PACKAGES', 'base_packages');
    define('XPDO_OPT_CACHE_COMPRESS', 'cache_compress');
    define('XPDO_OPT_CACHE_DB', 'cache_db');
    define('XPDO_OPT_CACHE_DB_COLLECTIONS', 'cache_db_collections');
    define('XPDO_OPT_CACHE_DB_OBJECTS_BY_PK', 'cache_db_objects_by_pk');
    define('XPDO_OPT_CACHE_DB_EXPIRES', 'cache_db_expires');
    define('XPDO_OPT_CACHE_DB_HANDLER', 'cache_db_handler');
    define('XPDO_OPT_CACHE_EXPIRES', 'cache_expires');
    define('XPDO_OPT_CACHE_HANDLER', 'cache_handler');
    define('XPDO_OPT_CACHE_KEY', 'cache_key');
    define('XPDO_OPT_CACHE_PATH', 'cache_path');
    define('XPDO_OPT_CALLBACK_ON_REMOVE', 'callback_on_remove');
    define('XPDO_OPT_CALLBACK_ON_SAVE', 'callback_on_save');
    define('XPDO_OPT_HYDRATE_FIELDS', 'hydrate_fields');
    define('XPDO_OPT_HYDRATE_ADHOC_FIELDS', 'hydrate_adhoc_fields');
    define('XPDO_OPT_HYDRATE_RELATED_OBJECTS', 'hydrate_related_objects');
    define('XPDO_OPT_LOADER_CLASSES', 'loader_classes');
    define('XPDO_OPT_ON_SET_STRIPSLASHES', 'on_set_stripslashes');
    define('XPDO_OPT_TABLE_PREFIX', 'table_prefix');
    define('XPDO_OPT_VALIDATE_ON_SAVE', 'validate_on_save');
    define('XPDO_OPT_VALIDATOR_CLASS', 'validator_class');
    /**#@-*/

    /**#@+
     * @var integer
     * @access public
     */
    define('XPDO_MODE_NATIVE', 1);
    define('XPDO_MODE_EMULATED', 2);
    define('XPDO_LOG_LEVEL_FATAL', 0);
    define('XPDO_LOG_LEVEL_ERROR', 1);
    define('XPDO_LOG_LEVEL_WARN', 2);
    define('XPDO_LOG_LEVEL_INFO', 3);
    define('XPDO_LOG_LEVEL_DEBUG', 4);
    define('PDO_PARAM_BOOL', 5);
    define('PDO_PARAM_NULL', 0);
    define('PDO_PARAM_INT', 1);
    define('PDO_PARAM_STR', 2);
    define('PDO_PARAM_LOB', 3);
    define('PDO_PARAM_STMT', 4);
    define('PDO_PARAM_INPUT_OUTPUT', -2147483648);
    define('PDO_ATTR_AUTOCOMMIT', 0);
    define('PDO_ATTR_PREFETCH', 1);
    define('PDO_ATTR_TIMEOUT', 2);
    define('PDO_ATTR_ERRMODE', 3);
    define('PDO_ATTR_SERVER_VERSION', 4);
    define('PDO_ATTR_CLIENT_VERSION', 5);
    define('PDO_ATTR_SERVER_INFO', 6);
    define('PDO_ATTR_CONNECTION_STATUS', 7);
    define('PDO_ATTR_CASE', 8);
    define('PDO_ATTR_CURSOR_NAME', 9);
    define('PDO_ATTR_CURSOR', 10);
    define('PDO_ATTR_ORACLE_NULLS', 11);
    define('PDO_ATTR_PERSISTENT', 12);
    define('PDO_ATTR_STATEMENT_CLASS', 13);
    define('PDO_ATTR_FETCH_TABLE_NAMES', 14);
    define('PDO_ATTR_FETCH_CATALOG_NAMES', 15);
    define('PDO_ATTR_DRIVER_NAME', 16);
    define('PDO_ATTR_STRINGIFY_FETCHES', 17);
    define('PDO_ATTR_MAX_COLUMN_LEN', 18);
    define('PDO_ATTR_EMULATE_PREPARES', 19);
    define('PDO_FETCH_LAZY', 1);
    define('PDO_FETCH_ASSOC', 2);
    define('PDO_FETCH_NUM', 3);
    define('PDO_FETCH_BOTH', 4);
    define('PDO_FETCH_OBJ', 5);
    define('PDO_FETCH_BOUND', 6);
    define('PDO_FETCH_COLUMN', 7);
    define('PDO_FETCH_CLASS', 8);
    define('PDO_FETCH_INTO', 9);
    define('PDO_FETCH_FUNC', 10);
    define('PDO_FETCH_GROUP', 65536);
    define('PDO_FETCH_UNIQUE', 196608);
    define('PDO_FETCH_CLASSTYPE', 262144);
    define('PDO_FETCH_SERIALIZE', 524288);
    define('PDO_FETCH_NAMED', 11);
    define('PDO_ERRMODE_SILENT', 0);
    define('PDO_ERRMODE_WARNING', 1);
    define('PDO_ERRMODE_EXCEPTION', 2);
    define('PDO_CASE_NATURAL', 0);
    define('PDO_CASE_LOWER', 2);
    define('PDO_CASE_UPPER', 1);
    define('PDO_NULL_NATURAL', 0);
    define('PDO_NULL_EMPTY_STRING', 1);
    define('PDO_NULL_TO_STRING', 2);
    define('PDO_ERR_NONE', '00000');
    define('PDO_FETCH_ORI_NEXT', 0);
    define('PDO_FETCH_ORI_PRIOR', 1);
    define('PDO_FETCH_ORI_FIRST', 2);
    define('PDO_FETCH_ORI_LAST', 3);
    define('PDO_FETCH_ORI_ABS', 4);
    define('PDO_FETCH_ORI_REL', 5);
    define('PDO_CURSOR_FWDONLY', 0);
    define('PDO_CURSOR_SCROLL', 1);
    define('PDO_MYSQL_ATTR_USE_BUFFERED_QUERY', 1000);
    define('PDO_MYSQL_ATTR_LOCAL_INFILE', 1001);
    define('PDO_MYSQL_ATTR_INIT_COMMAND', 1002);
    define('PDO_MYSQL_ATTR_READ_DEFAULT_FILE', 1003);
    define('PDO_MYSQL_ATTR_READ_DEFAULT_GROUP', 1004);
    define('PDO_MYSQL_ATTR_MAX_BUFFER_SIZE', 1005);
    define('PDO_MYSQL_ATTR_DIRECT_QUERY', 1006);
    define('PDO_PGSQL_ATTR_DISABLE_NATIVE_PREPARED_STATEMENT', 1000);
    /**#@-*/
}

if (!defined('XPDO_MODE')) {
    if (XPDO_PHP4_MODE) {
        /** @ignore */
        define('XPDO_MODE', XPDO_MODE_EMULATED);
    }
    elseif (!class_exists('PDO')) {
        /** @ignore */
        define('XPDO_MODE', XPDO_MODE_EMULATED);
    } else {
        /**
         * This constant defines if xPDO is using native xPDO or the emulator.
         *
         * @var integer Values should be XPDO_MODE_NATIVE or XPDO_MODE_EMULATED.
         */
        define('XPDO_MODE', XPDO_MODE_NATIVE);
    }
} elseif (XPDO_MODE != XPDO_MODE_NATIVE && XPDO_MODE != XPDO_MODE_EMULATED) {
    die('XPDO_MODE was predefined, and is not valid!  Please define with a value of 1 (use native PDO) or 2 (use emulated PDO) or remove the constant so xPDO can determine the proper mode.');
}

/**
 * A wrapper for PDO that powers an object-relational data model.
 *
 * xPDO provides centralized data access via a simple object-oriented API, to
 * a defined data structure. It provides the de facto methods for connecting
 * to a data source, getting persistence metadata for any class extended from
 * the {@link xPDOObject} class (core or custom), loading data source managers
 * when needed to manage table structures, and retrieving instances (or rows) of
 * any object in the model.
 *
 * Through various extensions, you can also reverse and forward engineer classes
 * and metadata maps for xPDO, have classes, models, and properties maintain
 * their own containers (databases, tables, columns, etc.) or changes to them,
 * and much more.
 *
 * @package xpdo
 */
class xPDO {
    /**
     * A PDO instance used by xPDO for database access.
     * @var PDO
     * @access public
     */
    var $pdo= null;
    /**
     * A array of xPDO configuration attributes.
     * @var array
     * @access public
     */
    var $config= null;
    /**
     * A map of data source meta data for all loaded classes.
     * @var array
     * @access public
     */
    var $map= array ();
    /**
     * A default package for specifying classes by name.
     * @var string
     * @access public
     */
    var $package= '';
    /**
     * An array storing packages and package-specific information.
     * @var array
     * @access public
     */
    var $packages= array ();
    /**
     * {@link xPDOManager} instance, loaded only if needed to manage datasource
     * containers, data structures, etc.
     * @var xPDOManager
     * @access public
     */
    var $manager= null;
    /**
     * @var xPDOCacheManager The cache service provider registered for this xPDO
     * instance.
     */
    var $cacheManager= null;
    /**
     * @var string A root path for file-based caching services to use.
     */
    var $cachePath= null;
    /**
     * @var float Start time of the request, initialized when the constructor is
     * called.
     */
    var $startTime= 0;
    /**
     * @var int The number of direct DB queries executed during a request.
     */
    var $executedQueries= 0;
    /**
     * @var int The amount of request handling time spent with DB queries.
     */
    var $queryTime= 0;

    /**
     * @var integer The logging level for the XPDO instance.
     */
    var $logLevel= XPDO_LOG_LEVEL_FATAL;

    /**
     * @var string The default logging target for the XPDO instance.
     */
    var $logTarget= 'ECHO';

    /**
     * Indicates the debug state of this instance.
     * @var boolean Default is false.
     * @access protected
     */
    var $_debug= false;
    /**
     * Indicates if this isntance is running in native PDO mode.
     * @var boolean
     * @access protected
     */
    var $_nativeMode= true;
    /**
     * A global cache flag that can be used to enable/disable all xPDO caching.
     * @var boolean All caching is disabled by default.
     * @access protected
     */
    var $_cacheEnabled= false;
    /**
     * Indicates the escape character used for a particular database engine.
     * @var string
     * @access protected
     */
    var $_escapeChar= '';

    /**#@+
     * The xPDO Constructor.
     *
     * This method is used to create a new xPDO object with a connection to a
     * specific database container.
     *
     * @param mixed $dsn A valid DSN connection string.
     * @param string $username The database username with proper permissions.
     * @param string $password The password for the database user.
     * @param array|string $options An array of xPDO options. For compatibility with previous
     * releases, this can also be a single string representing a prefix to be applied to all
     * database container (i. e. table) names, to isolate multiple installations or conflicting
     * table names that might need to coexist in a single database container. It is preferrable to
     * include the table_prefix option in the array for future compatibility.
     * @param mixed $driverOptions Driver-specific PDO options.
     * @return xPDO A unique xPDO instance.
     */
    function xPDO($dsn, $username= '', $password= '', $options= array(), $driverOptions= null) {
        $this->__construct($dsn, $username, $password, $options, $driverOptions);
    }
    /** @ignore */
    function __construct($dsn, $username= '', $password= '', $options= array(), $driverOptions= null) {
        if (is_string($options)) $options= array(XPDO_OPT_TABLE_PREFIX => $options);
        if (!is_array($options)) $options= array(XPDO_OPT_TABLE_PREFIX => '');
        if (!isset($options[XPDO_OPT_TABLE_PREFIX])) $options[XPDO_OPT_TABLE_PREFIX]= '';
        $this->_nativeMode= (XPDO_MODE === XPDO_MODE_NATIVE);
        $this->config= array_merge($options, $this->parseDSN($dsn));
        $this->config['dsn']= $dsn;
        $this->config['username']= $username;
        $this->config['password']= $password;
        $this->config['driverOptions']= $driverOptions;
        switch ($this->config['dbtype']) {
            case 'sqlite':
                $this->_escapeChar= "";
                break;
            case 'sqlite2':
                $this->_escapeChar= "";
                $this->config['dbtype']= 'sqlite';
                break;
            default:
                $this->_escapeChar= "`";
                break;
        }
        $this->setPackage('om', XPDO_CORE_PATH);
        if (isset($this->config[XPDO_OPT_BASE_PACKAGES]) && ($basePackages= explode(',', $this->config[XPDO_OPT_BASE_PACKAGES]))) {
            foreach ($basePackages as $basePackage) {
                $exploded= explode(':', $basePackage);
                if ($exploded && count($exploded) == 2) {
                    $this->addPackage($exploded[0], $exploded[1]);
                }
            }
        }
        $this->loadClass('xPDOObject');
        $this->loadClass('xPDOSimpleObject');
        if (isset($this->config[XPDO_OPT_BASE_CLASSES])) {
            foreach (array_keys($this->config[XPDO_OPT_BASE_CLASSES]) as $baseClass) {
                $this->loadClass($baseClass);
            }
        }
        if (isset($this->config[XPDO_OPT_CACHE_PATH])) {
            $this->cachePath = $this->config[XPDO_OPT_CACHE_PATH];
        }
    }
    /**#@-*/

    /**
     * Creates a PDO database connection for use by xPDO.
     *
     * @uses xpdo.connect.inc.php For PHP 5 execution only.
     * @param array $driverOptions An array of PDO driver options; overrides any
     * set in the constructor.
     * @return boolean Indicates if the connection was created successfully.
     */
    function connect($driverOptions= array ()) {
        $connected= false;
        if ($this->pdo == null) {
            $pdo_classname= 'PDO_';
            $loaded= false;
            if (XPDO_PHP4_MODE || !$this->_nativeMode) {
                $loaded= include_once (XPDO_CORE_PATH . 'pdo.class.php');
            } else {
                $loaded= class_exists('PDO');
                $pdo_classname= 'PDO';
            }
            if ($loaded) {
                if (!empty ($driverOptions)) {
                    $this->config['driverOptions']= array_merge($this->config['driverOptions'], $driverOptions);
                }
                if (XPDO_PHP4_MODE || !$this->_nativeMode) {
                    $this->pdo= new $pdo_classname($this->config['dsn'], $this->config['username'], $this->config['password'], $this->config['driverOptions']);
                    if (is_object ($this->pdo)) {
                        $errorCode= $this->pdo->errorCode();
                        $connected= (empty($errorCode) || $errorCode === PDO_ERR_NONE);
                    }
                } else {
                    $connected= include (XPDO_CORE_PATH . 'xpdo.connect.inc.php');
                }
                if ($connected) {
                    if ($this->config['dbtype'] === null) {
                        $this->config['dbtype']= $this->getAttribute(PDO_ATTR_DRIVER_NAME);
                    }
                    $connectFile = XPDO_CORE_PATH . 'om/' . $this->config['dbtype'] . '/connect.inc.php';
                    if (!empty($this->config['connect_file']) && file_exists($this->config['connect_file'])) {
                        $connectFile = $this->config['connect_file'];
                    }
                    include ($connectFile);
                }
            }
            if (!$connected) {
                $this->pdo= null;
                if ($this->_nativeMode) {
                    $this->_nativeMode= false;
                    $connected= $this->connect($driverOptions);
                }
            }
        }
        $connected= is_object($this->pdo);
        return $connected;
    }

    /**
     * Sets a specific model package to use when looking up classes.
     *
     * This package is of the form package.subpackage.subsubpackage and will be
     * added to the beginning of every xPDOObject class that is referenced in
     * xPDO methods such as {@link xPDO::loadClass()}, {@link xPDO::getObject()},
     * {@link xPDO::getCollection()}, {@link xPDOObject::getOne()}, {@link
     * xPDOObject::addOne()}, etc.
     *
     * @param string $pkg A package name to use when looking up classes in xPDO.
     * @param string $path The root path for looking up classes in this package.
     */
    function setPackage($pkg= '', $path= '') {
        $set= false;
        if (empty($path) && isset($this->packages[$pkg])) {
            $path= $this->packages[$pkg];
        }
        $set= $this->addPackage($pkg, $path);
        $this->package= $set == true ? $pkg : '';
        return $set;
    }

    /**
     * Adds a model package and base class path for including classes and/or maps from.
     *
     * @param string $pkg A package name to use when looking up classes/maps in xPDO.
     * @param string $path The root path for looking up classes in this package.
     */
    function addPackage($pkg= '', $path= '') {
        $added= false;
        if (is_string($pkg) && !empty($pkg)) {
            if (!is_string($path) || empty($path)) {
                $this->log(XPDO_LOG_LEVEL_ERROR, "Invalid path specified for package: {$pkg}; using default xpdo model path: " . XPDO_CORE_PATH . 'om/');
                $path= XPDO_CORE_PATH . 'om/';
            }
            if (!is_dir($path)) {
                $this->log(XPDO_LOG_LEVEL_ERROR, "Path specified for package {$pkg} is not a valid or accessible directory: {$path}");
            } else {
                $this->packages[$pkg]= $path;
                $added= true;
            }
        } else {
            $this->log(XPDO_LOG_LEVEL_ERROR, 'addPackage called with a valid package name.');
        }
        return $added;
    }

    /**
     * Load a class by fully qualified name.
     *
     * The $fqn should in the format:
     *
     *    dir_a.dir_b.dir_c.classname
     *
     * which will translate to:
     *
     *    XPDO_CORE_PATH/om/dir_a/dir_b/dir_c/dbtype/classname.class.php
     *
     * @param string $fqn The fully-qualified name of the class to load.
     * @return string|boolean The actual classname if successful, or false if
     * not.
     */
    function loadClass($fqn, $path= '', $ignorePkg= false, $transient= false) {
        if (empty($fqn)) {
            $this->log(XPDO_LOG_LEVEL_ERROR, "No class specified for loadClass");
            return false;
        }
        if (!$transient) {
            $typePos= strrpos($fqn, '_' . $this->config['dbtype']);
            if ($typePos !== false) {
                $fqn= substr($fqn, 0, $typePos);
            }
        }
        $pos= strrpos($fqn, '.');
        if ($pos === false) {
            $class= $fqn;
            if ($transient) {
                $fqn= strtolower($class);
            } else {
                $fqn= $this->config['dbtype'] . '.' . strtolower($class);
            }
        } else {
            $class= substr($fqn, $pos +1);
            if ($transient) {
                $fqn= substr($fqn, 0, $pos) . '.' . strtolower($class);
            } else {
                $fqn= substr($fqn, 0, $pos) . '.' . $this->config['dbtype'] . '.' . strtolower($class);
            }
        }
        if (!$transient && isset ($this->map[$class])) return $class;
        if (XPDO_PHP4_MODE) {
            $included= class_exists($class);
        } else {
            $included= class_exists($class, false);
        }
        if ($included) {
            if ($transient || (!$transient && isset ($this->map[$class]))) {
                return $class;
            }
        }
        $classname= $class;
        if (!empty($path) || $ignorePkg) {
            $class= $this->_loadClass($class, $fqn, $included, $path, $transient);
        } elseif (isset ($this->packages[$this->package])) {
            $pqn= $this->package . '.' . $fqn;
            if (!$pkgClass= $this->_loadClass($class, $pqn, $included, $this->packages[$this->package], $transient)) {
                if ($otherPkgs= array_diff_assoc($this->packages, array($this->package => $this->packages[$this->package]))) {
                    foreach ($otherPkgs as $pkg => $pkgPath) {
                        $pqn= $pkg . '.' . $fqn;
                        if ($pkgClass= $this->_loadClass($class, $pqn, $included, $pkgPath, $transient)) {
                            break;
                        }
                    }
                }
            }
            $class= $pkgClass;
        } else {
            $class= false;
        }
        if ($class === false) {
            $this->log(XPDO_LOG_LEVEL_ERROR, "Could not load class: {$classname} from {$fqn}.");
        }
        return $class;
    }

    function _loadClass($class, $fqn, $included= false, $path= '', $transient= false) {
        if (empty($path)) $path= XPDO_CORE_PATH;
        if (!$included) {
            /* turn to filesystem path and enforce all lower-case paths and filenames */
            $fqcn= str_replace('.', '/', $fqn) . '.class.php';
            /* include class */
            if (!file_exists($path . $fqcn)) return false;
            if (!$rt= include_once ($path . $fqcn)) {
                $this->log(XPDO_LOG_LEVEL_WARN, "Could not load class: {$class} from {$path}{$fqcn}");
                $class= false;
            }
        }
        if ($class && !$transient && !isset ($this->map[$class])) {
            $mapfile= strtr($fqn, '.', '/') . '.map.inc.php';
            if (file_exists($path . $mapfile)) {
                $xpdo_meta_map= & $this->map;
                if (!$rt= include ($path . $mapfile)) {
                    $this->log(XPDO_LOG_LEVEL_WARN, "Could not load metadata map {$mapfile} for class {$class} from {$fqn}");
                }
            }
        }
        return $class;
    }

    /**
     * Get an xPDO configuration option value by key.
     *
     * @param string $key The option key.
     * @param array $options A set of options to override those from xPDO.
     * @param mixed $default An optional default value to return if no value is found.
     * @return mixed The configuration option value.
     */
    function getOption($key, $options = null, $default = null) {
        $option= $default;
        if (is_array($key)) {
            if (!is_array($option)) {
                $default= $option;
                $option= array();
            }
            foreach ($key as $k) {
                $option[$k]= $this->getOption($k, $options, $default);
            }
        } elseif (is_string($key) && is_array($options) && array_key_exists($key, $options)) {
            $option= $options[$key];
        } elseif (is_string($key) && is_array($this->config) && array_key_exists($key, $this->config)) {
            $option= $this->config[$key];
        }
        return $option;
    }

    /**
     * Sets an xPDO configuration option value.
     *
     * @param string $key The option key.
     * @param mixed $value A value to set for the given option key.
     */
    function setOption($key, $value) {
        $this->config[$key]= $value;
    }

    /**
     * Creates a new instance of a specified class.
     *
     * All new objects created with this method are transient until {@link
     * xPDOObject::save()} is called the first time and is reflected by the
     * {@link xPDOObject::$_new} property.
     *
     * @param string $className Name of the class to get a new instance of.
     * @param array $fields An associated array of field names/values to
     * populate the object with.
     * @return object|null A new instance of the specified class, or null if a
     * new object could not be instantiated.
     */
    function newObject($className, $fields= array ()) {
        $instance= null;
        if ($className= $this->loadClass($className)) {
            $className .=  '_' . $this->config['dbtype'];
            if ($instance= new $className ($this)) {
                if (is_array($fields) && !empty ($fields)) {
                    $instance->fromArray($fields);
                }
            }
        }
        return $instance;
    }

    /**
     * Finds the class responsible for loading instances of the specified class.
     *
     * @access protected
     * @param string $className The name of the class to find a loader for.
     * @param string $method Indicates the specific loader method to use,
     * loadCollection or loadObject.
     * @return callable A callable loader function.
     */
    function getObjectLoader($className, $method) {
        $loader = false;
        if (isset($this->config[XPDO_OPT_LOADER_CLASSES]) && is_array($this->config[XPDO_OPT_LOADER_CLASSES])) {
            if ($ancestry = $this->getAncestry($className, true)) {
                if ($callbacks = array_intersect($ancestry, $this->config[XPDO_OPT_LOADER_CLASSES])) {
                    if ($loaderClass = reset($callbacks)) {
                        $loader = array($loaderClass, $method);
                        while (!is_callable($loader) && $loaderClass = next($callbacks)) {
                            $loader = array($loaderClass, $method);
                        }
                    }
                }
            }
        }
        if (!is_callable($loader)) {
            $loader = array('xPDOObject', $method);
        }
        return $loader;
    }

    /**
     * Retrieves a single object instance by the specified criteria.
     *
     * The criteria can be a primary key value, and array of primary key values
     * (for multiple primary key objects) or an {@link xPDOCriteria} object. If
     * no $criteria parameter is specified, no class is found, or an object
     * cannot be located by the supplied criteria, null is returned.
     *
     * @uses xPDOObject::load()
     * @param string $className Name of the class to get an instance of.
     * @param mixed $criteria Primary key of the record or a xPDOCriteria object.
     * @param mixed $cacheFlag If an integer value is provided, this specifies
     * the time to live in the object cache; if cacheFlag === false, caching is
     * ignored for the object and if cacheFlag === true, the object will live in
     * cache indefinitely.
     * @return object|null An instance of the class, or null if it could not be
     * instantiated.
    */
    function getObject($className, $criteria= null, $cacheFlag= true) {
        $instance= null;
        if ($criteria !== null) {
            $loader = $this->getObjectLoader($className, 'load');
            $instance = call_user_func_array($loader, array(& $this, $className, $criteria, $cacheFlag));
        }
        return $instance;
    }

    /**
     * Retrieves a collection of xPDOObjects by the specified xPDOCriteria.
     *
     * @uses xPDOObject::loadCollection()
     * @param string $className Name of the class to search for instances of.
     * @param object|array|string $criteria An xPDOCriteria object or an array
     * search expression.
     * @param mixed $cacheFlag If an integer value is provided, this specifies
     * the time to live in the result set cache; if cacheFlag === false, caching
     * is ignored for the collection and if cacheFlag === true, the objects will
     * live in cache until flushed by another process.
     * @return array|null An array of class instances retrieved.
    */
    function getCollection($className, $criteria= null, $cacheFlag= true) {
        $objCollection= array ();
        $loader = $this->getObjectLoader($className, 'loadCollection');
        $objCollection= call_user_func_array($loader, array(& $this, $className, $criteria, $cacheFlag));
        return $objCollection;
    }

    /**
     * Remove an instance of the specified className by a supplied criteria.
     *
     * @param string $className The name of the class to remove an instance of.
     * @param mixed $criteria Valid xPDO criteria for selecting an instance.
     * @return boolean True if the instance is successfully removed.
     */
    function removeObject($className, $criteria) {
        $removed= false;
        if ($this->getCount($className, $criteria) === 1) {
            if ($query= $this->newQuery($className)) {
                $query->command('DELETE');
                $query->where($criteria);
                if ($query->prepare()) {
                    if ($this->exec($query->toSQL()) !== 1) {
                        $this->log(XPDO_LOG_LEVEL_ERROR, "xPDO->removeObject - Error deleting {$className} instance using query " . $query->toSQL());
                    } else {
                        $removed= true;
                        if ($this->getOption(XPDO_OPT_CACHE_DB)) {
                            $this->cacheManager->delete(XPDO_CACHE_DIR . $query->_alias, array('multiple_object_delete' => true));
                        }
                        $callback = $this->getOption(XPDO_OPT_CALLBACK_ON_REMOVE);
                        if ($callback && is_callable($callback)) {
                            call_user_func($callback, array('className' => $className, 'criteria' => $query));
                        }
                    }
                }
            }
        } else {
            $this->log(XPDO_LOG_LEVEL_WARN, "xPDO->removeObject - {$className} instance to remove not found!");
            if ($this->getDebug() === true) $this->log(XPDO_LOG_LEVEL_DEBUG, "xPDO->removeObject - {$className} instance to remove not found using criteria " . print_r($criteria, true));
        }
        return $removed;
    }

    /**
     * Remove a collection of instances by the supplied className and criteria.
     *
     * @param string $className The name of the class to remove a collection of.
     * @param mixed $criteria Valid xPDO criteria for selecting a collection.
     * @return boolean True if the collection is successfully removed.
     */
    function removeCollection($className, $criteria) {
        $removed= false;
        if ($query= $this->newQuery($className)) {
            $query->command('DELETE');
            $query->where($criteria);
            if ($query->prepare()) {
                $removed= $this->exec($query->toSQL());
                if ($removed === false) {
                    $this->log(XPDO_LOG_LEVEL_ERROR, "xPDO->removeCollection - Error deleting {$className} instances using query " . $query->toSQL());
                } else {
                    if ($this->getOption(XPDO_OPT_CACHE_DB)) {
                        $this->cacheManager->delete(XPDO_CACHE_DIR . $query->_alias, array('multiple_object_delete' => true));
                    }
                    $callback = $this->getOption(XPDO_OPT_CALLBACK_ON_REMOVE);
                    if ($callback && is_callable($callback)) {
                        call_user_func($callback, array('className' => $className, 'criteria' => $query));
                    }
                }
            }
        }
        return $removed;
    }

    /**
     * Retrieves a count of xPDOObjects by the specified xPDOCriteria.
     *
     * @param string $className Class of xPDOObject to count instances of.
     * @param mixed $criteria Any valid xPDOCriteria object or expression.
     * @return integer The number of instances found by the criteria.
     */
    function getCount($className, $criteria= null) {
        $count= 0;
        if ($query= $this->newQuery($className, $criteria)) {
            $expr= '*';
            if ($pk= $this->getPK($className)) {
                if (!is_array($pk)) {
                    $pk= array ($pk);
                }
                $expr= $this->getSelectColumns($className, $className, '', $pk);
            }
            $query->select(array ("COUNT(DISTINCT {$expr})"));
            if ($stmt= $query->prepare()) {
                if ($stmt->execute()) {
                    if ($results= $stmt->fetchAll(PDO_FETCH_COLUMN)) {
                        $count= reset($results);
                        $count= intval($count);
                    }
                }
            }
        }
        return $count;
    }

    /**
     * Retrieves an xPDOObject instance with specified related objects.
     *
     * @uses xPDO::getCollectionGraph()
     * @param string $className The name of the class to return an instance of.
     * @param string|array $graph A related object graph in array or JSON
     * format, e.g. array('relationAlias'=>array('subRelationAlias'=>array()))
     * or {"relationAlias":{"subRelationAlias":{}}}.  Note that the empty arrays
     * are necessary in order for the relation to be recognized.
     * @param mixed $criteria A valid xPDOCriteria instance or expression.
     * @param boolean|integer $cacheFlag Indicates if the result set should be
     * cached, and optionally for how many seconds.
     * @return object The object instance with related objects from the graph
     * hydrated, or null if no instance can be located by the criteria.
     */
    function getObjectGraph($className, $graph, $criteria= null, $cacheFlag= true) {
        $object= null;
        if ($collection= $this->getCollectionGraph($className, $graph, $criteria, $cacheFlag)) {
            if (!count($collection) === 1) {
                $this->log(XPDO_LOG_LEVEL_WARN, 'getObjectGraph criteria returned more than one instance.');
            }
            $object= reset($collection);
        }
        return $object;
    }

    /**
     * Retrieves a collection of xPDOObject instances with related objects.
     *
     * @uses xPDOQuery::bindGraph()
     * @param string $className The name of the class to return a collection of.
     * @param string|array $graph A related object graph in array or JSON
     * format, e.g. array('relationAlias'=>array('subRelationAlias'=>array()))
     * or {"relationAlias":{"subRelationAlias":{}}}.  Note that the empty arrays
     * are necessary in order for the relation to be recognized.
     * @param mixed $criteria A valid xPDOCriteria instance or condition string.
     * @param boolean $cacheFlag Indicates if the result set should be cached.
     * @return array An array of instances matching the criteria with related
     * objects from the graph hydrated.  An empty array is returned when no
     * matches are found.
     */
    function getCollectionGraph($className, $graph, $criteria= null, $cacheFlag= true) {
        $objCollection= array ();
        $loader = $this->getObjectLoader($className, 'loadCollectionGraph');
        $objCollection= call_user_func_array($loader, array(& $this, $className, $graph, $criteria, $cacheFlag));
        return $objCollection;
    }

    /**
     * Gets criteria pre-defined in an {@link xPDOObject} class metadata definition.
     *
     * @todo Define callback functions as an alternative to retreiving criteria
     * sql and/or bindings from the metadata.
     *
     * @param string $className The class to get predefined criteria for.
     * @param string $type The type of criteria to get (you can define any
     * type you want, but 'object' and 'collection' are the typical criteria
     * for retrieving single and multiple instances of an object).
     * @param boolean|integer $cacheFlag Indicates if the result is cached and
     * optionally for how many seconds.
     * @return xPDOCriteria A criteria object or null if not found.
     */
    function getCriteria($className, $type= null, $cacheFlag= true) {
        $criteria= null;
        if ($criteria= $this->newQuery($className, $type, $cacheFlag)) {
            if (!$criteria->construct()) {
                $this->log(XPDO_LOG_LEVEL_ERROR, "Could not get criteria object for class {$className}");
            }
        }
        return $criteria;
    }

    /**
     * Gets the package name from a specified class name.
     *
     * @param string $className The name of the class to lookup the package for.
     * @return string The package the class belongs to.
     */
    function getPackage($className) {
        $package= '';
        if ($className= $this->loadClass($className)) {
            if (isset($this->map[$className]['package'])) {
                $package= $this->map[$className]['package'];
            }
            if (!$package && $ancestry= $this->getAncestry($className, false)) {
                foreach ($ancestry as $ancestor) {
                    if (isset ($this->map[$ancestor]['package']) && ($package= $this->map[$ancestor]['package'])) {
                        break;
                    }
                }
            }
        }
        return $package;
    }

    /**
     * Gets the actual run-time table name from a specified class name.
     *
     * @param string $className The name of the class to lookup a table name
     * for.
     * @param boolean $includeDb Qualify the table name with the database name.
     * @return string The table name for the class, or null if unsuccessful.
     */
    function getTableName($className, $includeDb= false) {
        $table= null;
        if ($className= $this->loadClass($className)) {
            if (isset ($this->map[$className]['table'])) {
                $table= $this->map[$className]['table'];
            }
            if (!$table && $ancestry= $this->getAncestry($className, false)) {
                foreach ($ancestry as $ancestor) {
                    if (isset ($this->map[$ancestor]['table']) && $table= $this->map[$ancestor]['table']) {
                        break;
                    }
                }
            }
        }
        if ($table) {
            $table= $this->_getFullTableName($table, $includeDb);
            if ($this->getDebug() === true) $this->log(XPDO_LOG_LEVEL_DEBUG, 'Returning table name: ' . $table . ' for class: ' . $className);
        } else {
            $this->log(XPDO_LOG_LEVEL_ERROR, 'Could not get table name for class: ' . $className);
        }
        return $table;
    }

    /**
     * Gets the actual run-time table metadata from a specified class name.
     *
     * @param string $className The name of the class to lookup a table name
     * for.
     * @return string The table meta data for the class, or null if
     * unsuccessful.
     */
    function getTableMeta($className) {
        $tableMeta= null;
        if ($className= $this->loadClass($className)) {
            if (isset ($this->map[$className]['tableMeta'])) {
                $tableMeta= $this->map[$className]['tableMeta'];
            }
            if (!$tableMeta && $ancestry= $this->getAncestry($className)) {
                foreach ($ancestry as $ancestor) {
                    if (isset ($this->map[$ancestor]['tableMeta'])) {
                        if ($tableMeta= $this->map[$ancestor]['tableMeta']) {
                            break;
                        }
                    }
                }
            }
        }
        return $tableMeta;
    }

    /**
     * Gets a list of fields (or columns) for an object by class name.
     *
     * This includes default values for each field and is used by the objects
     * themselves to build their initial attributes based on class inheritence.
     *
     * @param string $className The name of the class to lookup fields for.
     * @return array An array featuring field names as the array keys, and
     * default field values as the array values; empty array is returned if
     * unsuccessful.
     */
    function getFields($className) {
        $fields= array ();
        if ($className= $this->loadClass($className)) {
            if ($ancestry= $this->getAncestry($className)) {
                for ($i= count($ancestry) - 1; $i >= 0; $i--) {
                    if (isset ($this->map[$ancestry[$i]]['fields'])) {
                        $fields= array_merge($fields, $this->map[$ancestry[$i]]['fields']);
                    }
                }
            }
        }
        return $fields;
    }

    /**
     * Gets a list of field (or column) definitions for an object by class name.
     *
     * These definitions are used by the objects themselves to build their
     * own meta data based on class inheritence.
     *
     * @param string $className The name of the class to lookup fields meta data
     * for.
     * @return array An array featuring field names as the array keys, and
     * arrays of metadata information as the array values; empty array is
     * returned if unsuccessful.
     */
    function getFieldMeta($className) {
        $fieldMeta= array ();
        if ($className= $this->loadClass($className)) {
            if ($ancestry= $this->getAncestry($className)) {
                for ($i= count($ancestry) - 1; $i >= 0; $i--) {
                    if (isset ($this->map[$ancestry[$i]]['fieldMeta'])) {
                        $fieldMeta= array_merge($fieldMeta, $this->map[$ancestry[$i]]['fieldMeta']);
                    }
                }
            }
        }
        return $fieldMeta;
    }

    /**
     * Gets a set of validation rules defined for an object by class name.
     *
     * @param string $className The name of the class to lookup validation rules
     * for.
     * @return array An array featuring field names as the array keys, and
     * arrays of validation rule information as the array values; empty array is
     * returned if unsuccessful.
     */
    function getValidationRules($className) {
        $rules= array();
        if ($className= $this->loadClass($className)) {
            if ($ancestry= $this->getAncestry($className)) {
                for ($i= count($ancestry) - 1; $i >= 0; $i--) {
                    if (isset($this->map[$ancestry[$i]]['validation']['rules'])) {
                        $rules= array_merge($rules, $this->map[$ancestry[$i]]['validation']['rules']);
                    }
                }
                if ($this->getDebug() === true) {
                    $this->log(XPDO_LOG_LEVEL_DEBUG, "Returning validation rules: " . print_r($rules, true));
                }
            }
        }
        return $rules;
    }

    /**
     * Gets the primary key field(s) for a class.
     *
     * @param string $className The name of the class to lookup the primary key
     * for.
     * @return mixed The name of the field representing a class instance primary
     * key, an array of key names for compound primary keys, or null if no
     * primary key is found or defined for the class.
     */
    function getPK($className) {
        $pk= null;
        if (strcasecmp($className, 'xPDOObject') !== 0) {
            if ($actualClassName= $this->loadClass($className)) {
                if (isset ($this->map[$actualClassName]['fieldMeta'])) {
                    foreach ($this->map[$actualClassName]['fieldMeta'] as $k => $v) {
                        if (isset ($v['index']) && isset ($v['phptype']) && $v['index'] == 'pk') {
                            $pk[$k]= $k;
                        }
                    }
                }
                if ($ancestry= $this->getAncestry($actualClassName)) {
                    foreach ($ancestry as $ancestor) {
                        if ($ancestorClassName= $this->loadClass($ancestor)) {
                            if (isset ($this->map[$ancestorClassName]['fieldMeta'])) {
                                foreach ($this->map[$ancestorClassName]['fieldMeta'] as $k => $v) {
                                    if (isset ($v['index']) && isset ($v['phptype']) && $v['index'] == 'pk') {
                                        $pk[$k]= $k;
                                    }
                                }
                            }
                        }
                    }
                }
                if ($pk && count($pk) === 1) {
                    $pk= current($pk);
                }
            } else {
                $this->log(XPDO_LOG_LEVEL_ERROR, "Could not load class {$className}");
            }
        }
        return $pk;
    }

    /**
     * Gets the type of primary key field for a class.
     *
     * @param string className The name of the class to lookup the primary key
     * type for.
     * @return string The type of the field representing a class instance primary
     * key, or null if no primary key is found or defined for the class.
     * @todo Refactor method to return array of types rather than compound!
     */
    function getPKType($className, $pk= false) {
        $pktype= null;
        if ($actualClassName= $this->loadClass($className)) {
            if (!$pk)
                $pk= $this->getPK($actualClassName);
            if (!is_array($pk))
                $pk= array($pk);
            $ancestry= $this->getAncestry($actualClassName, true);
            foreach ($pk as $_pk) {
                foreach ($ancestry as $parentClass) {
                    if (isset ($this->map[$parentClass]['fieldMeta'][$_pk]['phptype'])) {
                        $pktype[$_pk]= $this->map[$parentClass]['fieldMeta'][$_pk]['phptype'];
                        break;
                    }
                }
            }
            if (is_array($pktype) && count($pktype) == 1) {
                $pktype= reset($pktype);
            }
            elseif (empty($pktype)) {
                $pktype= null;
            }
        } else {
            $this->log(XPDO_LOG_LEVEL_ERROR, "Could not load class {$className}!");
        }
        return $pktype;
    }

    /**
     * Gets a collection of aggregate foreign key relationship definitions.
     *
     * @param string $className The fully-qualified name of the class.
     * @return array An array of aggregate foreign key relationship definitions.
     */
    function getAggregates($className) {
        $aggregates= array ();
        if ($className= $this->loadClass($className)) {
            if ($ancestry= $this->getAncestry($className)) {
                for ($i= count($ancestry) - 1; $i >= 0; $i--) {
                    if (isset ($this->map[$ancestry[$i]]['aggregates'])) {
                        $aggregates= array_merge($aggregates, $this->map[$ancestry[$i]]['aggregates']);
                    }
                }
            }
        }
        return $aggregates;
    }

    /**
     * Gets a collection of composite foreign key relationship definitions.
     *
     * @param string $className The fully-qualified name of the class.
     * @return array An array of composite foreign key relationship definitions.
     */
    function getComposites($className) {
        $composites= array ();
        if ($className= $this->loadClass($className)) {
            if ($ancestry= $this->getAncestry($className)) {
                for ($i= count($ancestry) - 1; $i >= 0; $i--) {
                    if (isset ($this->map[$ancestry[$i]]['composites'])) {
                        $composites= array_merge($composites, $this->map[$ancestry[$i]]['composites']);
                    }
                }
            }
        }
        return $composites;
    }

    /**
     * Retrieves the complete ancestry for a class.
     *
     * @param string className The name of the class.
     * @param boolean includeSelf Determines if the specified class should be
     * included in the resulting array.
     * @return array An array of string class names representing the class
     * hierarchy, or an empty array if unsuccessful.
     */
    function getAncestry($className, $includeSelf= true) {
        $ancestry= array ();
        if ($actualClassName= $this->loadClass($className)) {
            $ancestor= $actualClassName;
            if ($includeSelf) {
                $ancestry[]= $actualClassName;
            }
            while ($ancestor= get_parent_class($ancestor)) {
                $ancestry[]= $ancestor;
            }
            if ($this->getDebug() === true) {
                $this->log(XPDO_LOG_LEVEL_DEBUG, "Returning ancestry for {$className}: " . print_r($ancestry, 1));
            }
        }
        return $ancestry;
    }

    /**
     * Gets select columns from a specific class for building a query.
     *
     * @uses xPDOObject::getSelectColumns()
     * @param string $className The name of the class to build the column list
     * from.
     * @param string $tableAlias An optional alias for the class table, to be
     * used in complex queries with multiple tables.
     * @param string $columnPrefix An optional string with which to prefix the
     * columns returned, to avoid name collisions in return columns.
     * @param array $columns An optional array of columns to include.
     * @param boolean $exclude If true, will exclude columns in the previous
     * parameter, instead of including them.
     * @return string A valid SQL string of column names for a SELECT statement.
     */
    function getSelectColumns($className, $tableAlias= '', $columnPrefix= '', $columns= array (), $exclude= false) {
        return xPDOObject :: getSelectColumns($this, $className, $tableAlias, $columnPrefix, $columns, $exclude);
    }

    /**
     * Gets an aggregate or composite relation definition from a class.
     *
     * @param string $parentClass The class from which the relation is defined.
     * @param string $alias The alias identifying the related class.
     * @return array The aggregate or composite definition details in an array
     * or null if no definition is found.
     */
    function getFKDefinition($parentClass, $alias) {
        $def= null;
        $parentClass= $this->loadClass($parentClass);
        if ($parentClass && $alias) {
            if ($aggregates= $this->getAggregates($parentClass)) {
//                if (XPDO_PHP4_MODE) $aggregates= array_change_key_case($aggregates);
                if (isset ($aggregates[$alias])) {
                    $def= $aggregates[$alias];
                    $def['type']= 'aggregate';
                }
            }
            if ($composites= $this->getComposites($parentClass)) {
//                if (XPDO_PHP4_MODE) $composites= array_change_key_case($composites);
                if (isset ($composites[$alias])) {
                    $def= $composites[$alias];
                    $def['type']= 'composite';
                }
            }
        }
        if ($def === null) {
            $this->log(XPDO_LOG_LEVEL_ERROR, 'No foreign key definition for parentClass: ' . $parentClass . ' using relation alias: ' . $alias);
        }
        return $def;
    }

    /**
     * Gets the manager class for this xPDO connection.
     *
     * The manager class can perform operations such as creating or altering
     * table structures, creating data containers, generating custom persistence
     * classes, and other advanced operations that do not need to be loaded
     * frequently.
     *
     * @uses xPDOManager
     * @return object|null A manager instance for the XPDO connection, or null
     * if a manager class can not be instantiated.
     */
    function getManager() {
        if ($this->manager === null || !is_a($this->manager, 'xPDOManager')) {
            if ($managerClass= $this->loadClass($this->config['dbtype'] . '.xPDOManager', '', false, true)) {
                $managerClass.= '_' . $this->config['dbtype'];
                $this->manager= new $managerClass ($this);
            }
            if (!$this->manager) {
                $this->log(XPDO_LOG_LEVEL_ERROR, "Could not load xPDOManager class.");
            }
        }
        return $this->manager;
    }

    /**
     * Gets the absolute path to the cache directory.
     *
     * @return string The full cache directory path.
     */
    function getCachePath() {
        if (!$this->cachePath) {
            if ($this->getCacheManager()) {
                $this->cachePath= $this->cacheManager->getCachePath();
            }
        }
        return $this->cachePath;
    }

    /**
     * Gets the xPDOCacheManager instance.
     *
     * This class is responsible for handling all types of caching operations for the xPDO core.
     *
     * @uses xPDOCacheManager
     * @param string $class Optional name of a derivative xPDOCacheManager class.
     * @param string $path Optional root path for looking up the $class.
     * @param boolean $ignorePkg If false and you do not specify a path, you can look up custom
     * xPDOCacheManager derivatives in declared packages.
     * @return object The xPDOCacheManager for this xPDO instance.
     */
    function getCacheManager($class= 'cache.xPDOCacheManager', $options = array('path' => XPDO_CORE_PATH, 'ignorePkg' => true)) {
        $actualClass = $this->loadClass($class, $options['path'], $options['ignorePkg'], true);
        if ($this->cacheManager === null || !is_object($this->cacheManager) || !is_a($this->cacheManager, $actualClass)) {
            if ($this->cacheManager= new $actualClass($this, $options)) {
                $this->_cacheEnabled= true;
            }
        }
        return $this->cacheManager;
    }

    /**
     * Returns the debug state for the XPDO connection.
     *
     * @return boolean The current debug state for the connection, true for on,
     * false for off.
     */
    function getDebug() {
        return $this->_debug;
    }

    /**
     * Sets the debug state for the XPDO connection.
     *
     * @param boolean $v The debug status, true for on, false for off.
     */
    function setDebug($v= true) {
        $this->_debug= $v;
    }

    /**
     * Sets the logging level state for the XPDO instance.
     *
     * @param integer $level The logging level to switch to.
     * @return integer The previous log level.
     */
    function setLogLevel($level= XPDO_LOG_LEVEL_FATAL) {
        $oldLevel = $this->logLevel;
        $this->logLevel= intval($level);
        return $this->logLevel;
    }

    /**
     * Sets the log target for xPDO::_log() calls.
     *
     * Valid target values include:
     * <ul>
     * <li>'ECHO': Returns output to the STDOUT.</li>
     * <li>'HTML': Returns output to the STDOUT with HTML formatting.</li>
     * <li>'FILE': Sends output to a log file.</li>
     * <li>An array with at least one element with key 'target' matching
     * one of the valid log targets listed above. For 'target' => 'FILE'
     * you can specify a second element with key 'options' with another
     * associative array with one or both of the elements 'filename' and
     * 'filepath'</li>
     * </ul>
     *
     * @param string $target An identifier indicating the target of the logging.
     * @param mixed The previous log target.
     */
    function setLogTarget($target= 'ECHO') {
        $oldTarget = $this->logTarget;
        $this->logTarget= $target;
        return $oldTarget;
    }

    /**
     * Log a message with details about where and when an event occurs.
     *
     * @param integer $level The level of the logged message.
     * @param string $msg The message to log.
     * @param string $target The logging target.
     * @param string $def The name of a defining structure (such as a class) to
     * help identify the message source.
     * @param string $file A filename in which the log event occured.
     * @param string $line A line number to help locate the source of the event
     * within the indicated file.
     */
    function log($level, $msg, $target= '', $def= '', $file= '', $line= '') {
        $this->_log($level, $msg, $target, $def, $file, $line);
    }

    /**
     * Log a message as appropriate for the level and target.
     *
     * @param integer $level The level of the logged message.
     * @param string $msg The message to log.
     * @param string $target The logging target.
     * @param string $def The name of a defining structure (such as a class) to
     * help identify the log event source.
     * @param string $file A filename in which the log event occured.
     * @param string $line A line number to help locate the source of the event
     * within the indicated file.
     */
    function _log($level, $msg, $target= '', $def= '', $file= '', $line= '') {
        if (empty ($target)) {
            $target= $this->logTarget;
        }
        $targetOptions = array();
        if (is_array($target)) {
            if (isset($target['options'])) $targetOptions = $target['options'];
            $target = isset($target['target']) ? $target['target'] : 'ECHO';
        }
        if (!XPDO_CLI_MODE && empty ($file)) {
            $file= (isset ($_SERVER['PHP_SELF']) || $this->logTarget == 'ECHO') ? $_SERVER['PHP_SELF'] : $_SERVER['SCRIPT_FILENAME'];
        }
        if ($level === XPDO_LOG_LEVEL_FATAL) {
            while (@ob_end_flush()) {}
            exit ('[' . strftime('%Y-%m-%d %H:%M:%S') . '] (' . $this->_getLogLevel($level) . $def . $file . $line . ') ' . $msg . "\n" . ($this->getDebug() === true ? '<pre>' . "\n" . print_r(debug_backtrace(), true) . "\n" . '</pre>' : ''));
        }
        if ($this->_debug === true || $level <= $this->logLevel) {
            @ob_start();
            if (!empty ($def)) {
                $def= " in {$def}";
            }
            if (!empty ($file)) {
                $file= " @ {$file}";
            }
            if (!empty ($line)) {
                $line= " : {$line}";
            }
            switch ($target) {
                case 'HTML' :
                    echo '<h5>[' . strftime('%Y-%m-%d %H:%M:%S') . '] (' . $this->_getLogLevel($level) . $def . $file . $line . ')</h5><pre>' . $msg . '</pre>' . "\n";
                    break;
                default :
                    echo '[' . strftime('%Y-%m-%d %H:%M:%S') . '] (' . $this->_getLogLevel($level) . $def . $file . $line . ') ' . $msg . "\n";
            }
            $content= @ob_get_contents();
            @ob_end_clean();
            if ($target=='FILE' && $this->getCacheManager()) {
                $filename = isset($targetOptions['filename']) ? $targetOptions['filename'] : 'error.log';
                $filepath = isset($targetOptions['filepath']) ? $targetOptions['filepath'] : $this->getCachePath() . XPDO_LOG_DIR;
                $this->cacheManager->writeFile($filepath . $filename, $content, 'a');
            }
            else {
                echo $content;
            }
        }
    }

    /**
     * Returns an abbreviated backtrace of debugging information.
     *
     * This function returns just the fields returned via xPDOObject::toArray()
     * on xPDOObject instances, and simply the classname for other objects, to
     * reduce the amount of unnecessary information returned.
     *
     * @return array The abbreviated backtrace.
     */
    function getDebugBacktrace() {
        $backtrace= array ();
        foreach (debug_backtrace() as $levelKey => $levelElement) {
            foreach ($levelElement as $traceKey => $traceElement) {
                if ($traceKey == 'object' && is_a($traceElement, 'xPDOObject')) {
                    $backtrace[$levelKey][$traceKey]= $traceElement->toArray('', true);
                } elseif ($traceKey == 'object') {
                    $backtrace[$levelKey][$traceKey]= get_class($traceElement);
                } else {
                    $backtrace[$levelKey][$traceKey]= $traceElement;
                }
            }
        }
        return $backtrace;
    }

    /**
     * Gets a logging level as a string representation.
     *
     * @param integer $level The logging level to retrieve a string for.
     * @return string The string representation of a valid logging level.
     */
    function _getLogLevel($level) {
        $levelText= '';
        switch ($level) {
            case XPDO_LOG_LEVEL_DEBUG :
                $levelText= 'DEBUG';
                break;
            case XPDO_LOG_LEVEL_INFO :
                $levelText= 'INFO';
                break;
            case XPDO_LOG_LEVEL_WARN :
                $levelText= 'WARN';
                break;
            case XPDO_LOG_LEVEL_ERROR :
                $levelText= 'ERROR';
                break;
            default :
                $levelText= 'FATAL';
        }
        return $levelText;
    }

    /**
     * Adds the table prefix, and optionally database name, to a given table.
     *
     * @param string $baseTableName The table name as specified in the object
     * model.
     * @param boolean $includeDb Qualify the table name with the database name.
     * @return string The fully-qualified and quoted table name for the
     */
    function _getFullTableName($baseTableName, $includeDb= false) {
        $fqn= '';
        if (!empty ($baseTableName)) {
            if ($includeDb) {
                $fqn .= $this->_escapeChar . $this->config['dbname'] . $this->_escapeChar . '.';
            }
            $fqn .= $this->_escapeChar . $this->config['table_prefix'] . $baseTableName . $this->_escapeChar;
        }
        return $fqn;
    }

    /**
     * Parses a DSN and returns an array of the connection details.
     *
     * @static
     * @param string $string The DSN to parse.
     * @return array An array of connection details from the DSN.
     * @todo Have this method handle all methods of DSN specification as handled
     * by latest native PDO implementation.
     */
    function parseDSN($string) {
        $result= array ();
        $pos= strpos($string, ':');
        $parameters= explode(';', substr($string, ($pos +1)));
        $result['dbtype']= strtolower(substr($string, 0, $pos));
        for ($a= 0, $b= count($parameters); $a < $b; $a++) {
            $tmp= explode('=', $parameters[$a]);
            if (count($tmp) == 2) {
                $result[$tmp[0]]= $tmp[1];
            } else {
                $result['dbname']= $parameters[$a];
            }
        }
        return $result;
    }

    /**
     * Retrieves a result array from the object cache.
     *
     * @param string|xPDOCriteria $signature A unique string or xPDOCriteria object
     * that represents the query identifying the result set.
     * @param string $class An optional classname the result represents.
     * @param array $options Various cache options.
     * @return array|string|null A PHP array or JSON object representing the
     * result set, or null if no cache representation is found.
     */
    function fromCache($signature, $class= '', $options= array()) {
        $result= null;
        if ($this->getOption(XPDO_OPT_CACHE_DB, $options)) {
            if ($signature && $this->getCacheManager()) {
                $sig= '';
                $sigKey= array();
                $sigHash= '';
                $sigClass= empty($class) || !is_string($class) ? '' : $class;
                if (is_object($signature)) {
                    if (is_a($signature, 'xPDOCriteria')) {
                        if (is_a($signature, 'xPDOQuery')) {
                            $signature->construct();
                            if (empty($sigClass)) $sigClass= $signature->_alias;
                        }
                        $sigKey= array ($signature->sql, $signature->bindings);
                    }
                }
                elseif (is_string($signature)) {
                    if ($exploded= explode('_', $signature)) {
                        $class= reset($exploded);
                        if (empty($sigClass) || $sigClass !== $class) {
                            $sigClass= $class;
                        }
                        if (empty($sigKey)) {
                            while ($key= next($exploded)) {
                                $sigKey[]= $key;
                            }
                        }
                    }
                }
                if (empty($sigClass)) $sigClass= '__sqlResult';
                if ($sigClass && $sigKey) {
                    $sigHash= md5($this->toJSON($sigKey));
                    $sig= implode('/', array ($sigClass, $sigHash));
                }
                if (is_string($sig) && !empty($sig)) {
                    $result= $this->cacheManager->get(XPDO_CACHE_DIR . $sig);
                    if ($result && $this->getOption('cache_db_format', $options, 'php') == 'json') {
                        $result= $this->toJSON($result);
                    }
                    if (!$result) {
                        $this->log(XPDO_LOG_LEVEL_DEBUG, 'No cache item found for class ' . $sigClass . ' with signature ' . XPDO_CACHE_DIR . $sig);
                    }
                }
            }
        }
        return $result;
    }

    /**
     * Places a result set in the object cache.
     *
     * @param string|xPDOCriteria $signature A unique string or xPDOCriteria object
     * representing the object.
     * @param object $object An object to place a representation of in the cache.
     * @param integer $lifetime An optional number of seconds the cached result
     * will remain valid, with 0 meaning it will remain valid until replaced or
     * removed.
     * @param array $options Various cache options.
     * @return boolean Indicates if the object was successfully cached.
     */
    function toCache($signature, $object, $lifetime= 0, $options = array()) {
        $result= false;
        if ($this->getCacheManager()) {
            if ($this->getOption(XPDO_OPT_CACHE_DB, $options)) {
                if ($lifetime === true) {
                    $lifetime = 0;
                }
                elseif (!$lifetime && $this->getOption(XPDO_OPT_CACHE_DB_EXPIRES, $options, 0)) {
                    $lifetime= intval($this->getOption(XPDO_OPT_CACHE_DB_EXPIRES, $options, 0));
                }
                $sig= '';
                $sigKey= array();
                $sigHash= '';
                $sigClass= '';
                $sigGraph= array();
                if (is_object($signature)) {
                    if (is_a($signature, 'xPDOCriteria')) {
                        if (is_a($signature, 'xPDOQuery')) {
                            $signature->construct();
                            if (empty($sigClass)) $sigClass = $signature->_alias;
                        }
                        $sigKey= array($signature->sql, $signature->bindings);
                    }
                }
                elseif (is_string($signature)) {
                    $exploded= explode('_', $signature);
                    if ($exploded && count($exploded) >= 2) {
                        $class= reset($exploded);
                        if (empty($sigClass) || $sigClass !== $class) {
                            $sigClass= $class;
                        }
                        if (empty($sigKey)) {
                            while ($key= next($exploded)) {
                                $sigKey[]= $key;
                            }
                        }
                    }
                }
                if (empty($sigClass)) $sigClass= '__sqlResult';
                if (empty($sigKey) && is_string($signature)) $sigKey= $signature;
                if ($sigClass && $sigKey) {
                    $sigHash= md5($this->toJSON($sigKey));
                    $sig= implode('/', array ($sigClass, $sigHash));
                    if (is_string($sig)) {
                        if (empty($sigGraph) && is_a($object, 'xPDOObject')) {
                            $classes= array();
                            $sigGraph= array_merge($object->_aggregates, $object->_composites);
                        }
                        if (!empty($sigGraph)) {
                            foreach ($sigGraph as $alias => $fkMeta) {
                                if (isset($classes[$fkMeta['class']])) {
                                    continue;
                                }
                                $removed= $this->cacheManager->delete(XPDO_CACHE_DIR . $fkMeta['class'], array_merge($options, array('multiple_object_delete' => true)));
                                if ($this->getDebug() === true) {
                                    $this->log(XPDO_LOG_LEVEL_DEBUG, "Removing all cache objects of class {$fkMeta['class']}: " . ($removed ? 'successful' : 'failed'));
                                }
                                $classes[$fkMeta['class']]= $fkMeta['class'];
                            }
                        }
                        $result= $this->cacheManager->set(XPDO_CACHE_DIR . $sig, $object, $lifetime, $options);
                        if ($result && is_a($object, 'xPDOObject')) {
                            if ($this->getDebug() === true) {
                                $this->log(XPDO_LOG_LEVEL_DEBUG, "xPDO->toCache() successfully cached object with signature " . XPDO_CACHE_DIR . $sig);
                            }
                            $object->_cacheFlag= true;
                            $pkClass= $object->_class;
                            $pk= $object->getPrimaryKey(false);
                            $pk= is_array($pk) ? $pk : array($pk);
                            $pkHash= md5($this->toJSON($pk));
                            $pkSig= implode('/', array($pkClass, $pkHash));
                            $this->cacheManager->set(XPDO_CACHE_DIR . $pkSig, $object, $lifetime, $options);
                        }
                        if (!$result) {
                            $this->log(XPDO_LOG_LEVEL_WARN, "xPDO->toCache() could not cache object with signature " . XPDO_CACHE_DIR . $sig);
                        }
                    }
                } else {
                    $this->log(XPDO_LOG_LEVEL_ERROR, "Object sent toCache() has an invalid signature.");
                }
            }
        } else {
            $this->log(XPDO_LOG_LEVEL_ERROR, "Attempt to send a non-object to toCache().");
        }
        return $result;
    }

    /**
     * Converts a PHP array into a JSON encoded string.
     *
     * @param array $array The PHP array to convert.
     * @return string The JSON representation of the source array.
     */
    function toJSON($array) {
        $encoded= '';
        if (is_array ($array)) {
            if (!function_exists('json_encode')) {
                if (@ include_once (XPDO_CORE_PATH . 'json/JSON.php')) {
                    $json = new Services_JSON();
                    $encoded= $json->encode($array);
                }
            } else {
                $encoded= json_encode($array);
            }
        }
        return $encoded;
    }

    /**
     * Converts a JSON source string into an equivalent PHP representation.
     *
     * @param string $src A JSON source string.
     * @param boolean $asArray Indicates if the result should treat objects as
     * associative arrays; since all JSON associative arrays are objects, the default
     * is true.  Set to false to have JSON objects returned as PHP objects.
     * @return mixed The PHP representation of the JSON source.
     */
    function fromJSON($src, $asArray= true) {
        $decoded= '';
        if ($src) {
            if (!function_exists('json_decode')) {
                if (@ include_once (XPDO_CORE_PATH . 'json/JSON.php')) {
                    if ($asArray) {
                        $json = new Services_JSON(SERVICES_JSON_LOOSE_TYPE);
                    } else {
                        $json = new Services_JSON();
                    }
                    $decoded= $json->decode($src);
                }
            } else {
                $decoded= json_decode($src, $asArray);
            }
        }
        return $decoded;
    }

    /**
     * @see http://php.net/manual/en/function.pdo-begintransaction.php
     */
    function beginTransaction() {
        if (!$this->connect()) {
            return false;
        }
        return $this->pdo->beginTransaction();
    }

    /**
     * @see http://php.net/manual/en/function.pdo-commit.php
     */
    function commit() {
        if (!$this->connect()) {
            return false;
        }
        return $this->pdo->commit();
    }

    /**
     * @see http://php.net/manual/en/function.pdo-exec.php
     */
    function exec($query) {
        if (!$this->connect()) {
            return false;
        }
        $tstart= $this->getMicroTime();
        $return= $this->pdo->exec($query);
        $tend= $this->getMicroTime();
        $totaltime= $tend - $tstart;
        $this->queryTime= $this->queryTime + $totaltime;
        $this->executedQueries= $this->executedQueries + 1;
        return $return;
    }

    /**
     * @see http://php.net/manual/en/function.pdo-errorcode.php
     */
    function errorCode() {
        if (!$this->connect()) {
            return false;
        }
        return $this->pdo->errorCode();
    }

    /**
     * @see http://php.net/manual/en/function.pdo-errorinfo.php
     */
    function errorInfo() {
        if (!$this->connect()) {
            return false;
        }
        return $this->pdo->errorInfo();
    }

    /**
     * @see http://php.net/manual/en/function.pdo-getattribute.php
     */
    function getAttribute($attribute) {
        if (!$this->connect()) {
            return false;
        }
        return $this->pdo->getAttribute($attribute);
    }

    /**
     * @see http://php.net/manual/en/function.pdo-lastinsertid.php
     */
    function lastInsertId() {
        if (!$this->connect()) {
            return false;
        }
        return $this->pdo->lastInsertId();
    }

    /**
     * @see http://php.net/manual/en/function.pdo-prepare.php
     */
    function prepare($statement, $driver_options= array ()) {
        if (!$this->connect()) {
            return false;
        }
        return $this->pdo->prepare($statement, $driver_options= array ());
    }

    /**
     * @see http://php.net/manual/en/function.pdo-query.php
     */
    function query($query) {
        if (!$this->connect()) {
            return false;
        }
        $tstart= $this->getMicroTime();
        $return= $this->pdo->query($query);
        $tend= $this->getMicroTime();
        $totaltime= $tend - $tstart;
        $this->queryTime= $this->queryTime + $totaltime;
        $this->executedQueries= $this->executedQueries + 1;
        return $return;
    }

    /**
     * @see http://php.net/manual/en/function.pdo-quote.php
     */
    function quote($string, $parameter_type= PDO_PARAM_STR) {
        if (!$this->connect()) {
            return false;
        }
        return $this->pdo->quote($string, $parameter_type);
    }

    /**
     * @see http://php.net/manual/en/function.pdo-rollback.php
     */
    function rollBack() {
        if (!$this->connect()) {
            return false;
        }
        return $this->pdo->rollBack();
    }

    /**
     * @see http://php.net/manual/en/function.pdo-setattribute.php
     */
    function setAttribute($attribute, $value) {
        if (!$this->connect()) {
            return false;
        }
        return $this->pdo->setAttribute($attribute, $value);
    }

    /**
     * Convert current microtime() result into seconds.
     *
     * @return float
     */
    function getMicroTime() {
       list($usec, $sec) = explode(' ', microtime());
       return ((float)$usec + (float)$sec);
    }

    /**
     * Creates an new xPDOQuery for a specified xPDOObject class.
     *
     * @param string $class The class to create the xPDOQuery for.
     * @param mixed $criteria Any valid xPDO criteria expression.
     * @param boolean|integer $cacheFlag Indicates if the result should be cached
     * and optionally for how many seconds (if passed an integer greater than 0).
     * @return xPDOQuery The resulting xPDOQuery instance or false if unsuccessful.
     */
    function newQuery($class, $criteria= null, $cacheFlag= true) {
        $query= false;
        if ($this->loadClass($this->config['dbtype'] . '.xPDOQuery', '', false, true)) {
            $xpdoQueryClass= 'xPDOQuery_' . $this->config['dbtype'];
            if ($query= new $xpdoQueryClass($this, $class, $criteria)) {
                $query->cacheFlag= $cacheFlag;
            }
        }
        return $query;
    }

    /**
     * Splits a string on a specified character, ignoring escaped content.
     *
     * @static
     * @param string $char A character to split the tag content on.
     * @param string $str The string to operate on.
     * @param string $escToken A character used to surround escaped content; all
     * content within a pair of these tokens will be ignored by the split
     * operation.
     * @param integer $limit Limit the number of results. Default is 0 which is
     * no limit. Note that setting the limit to 1 will only return the content
     * up to the first instance of the split character and will discard the
     * remainder of the string.
     * @return array An array of results from the split operation, or an empty
     * array.
     */
    function escSplit($char, $str, $escToken = '`', $limit = 0) {
        $split= array();
        $charPos = strpos($str, $char);
        if ($charPos !== false) {
            if ($charPos === 0) {
                $searchPos = 1;
                $startPos = 1;
            } else {
                $searchPos = 0;
                $startPos = 0;
            }
            $escOpen = false;
            $strlen = strlen($str);
            for ($i = $startPos; $i <= $strlen; $i++) {
                if ($i == $strlen) {
                    $tmp= trim(substr($str, $searchPos));
                    if (!empty($tmp)) $split[]= $tmp;
                    break;
                }
                if ($str[$i] == $escToken) {
                    $escOpen = $escOpen == true ? false : true;
                    continue;
                }
                if (!$escOpen && $str[$i] == $char) {
                    $tmp= trim(substr($str, $searchPos, $i - $searchPos));
                    if (!empty($tmp)) {
                        $split[]= $tmp;
                        if ($limit > 0 && count($split) >= $limit) {
                            break;
                        }
                    }
                    $searchPos = $i + 1;
                }
            }
        } else {
            $split[]= trim($str);
        }
        return $split;
    }

    function parseBindings($sql, $bindings) {
        if (!empty($sql) && !empty($bindings)) {
            reset($bindings);
            while (list ($k, $param)= each($bindings)) {
                if (!is_array($param)) {
                    $v= $param;
                    $type= $this->getPDOType($param);
                    $bindings[$k]= array(
                        'value' => $v,
                        'type' => $type
                    );
                } else {
                    $v= $param['value'];
                    $type= $param['type'];
                }
                if (!$v) {
                    switch ($type) {
                        case PDO_PARAM_INT:
                            $v= '0';
                            break;
                        case PDO_PARAM_BOOL:
                            $v= '0';
                            break;
                        default:
                            break;
                    }
                }
                if (!is_int($k) || substr($k, 0, 1) === ':') {
                    if (!isset ($tempf)) {
                        $tempf= $tempr= array ();
                    }
                    $pattern= '/' . $k . '\b/';
                    array_push($tempf, $pattern);
                    $v= $this->quote($v, $type);
                    array_push($tempr, $v);
                } else {
                    $parse= create_function('$d,$v,$t', 'return $d->quote($v, $t);');
                    $sql= preg_replace("/(\?)/e", '$parse($this,$bindings[$k][\'value\'],$type);', $sql, 1);
                }
            }
            if (isset ($tempf)) {
                $sql= preg_replace($tempf, $tempr, $sql);
            }
        }
        return $sql;
    }

    function getPDOType($value) {
        $type= null;
        if (is_null($value)) $type= PDO_PARAM_NULL;
        elseif (is_scalar($value)) {
            if (is_int($value)) $type= PDO_PARAM_INT;
            else $type= PDO_PARAM_STR;
        }
        return $type;
    }
}

/**
 * Encapsulates a SQL query into a PDOStatement with a set of bindings.
 *
 * @package xpdo
 *
 */
class xPDOCriteria {
    var $sql= '';
    var $stmt= null;
    var $bindings= array ();
    var $cacheFlag= false;

    /**#@+
     * The constructor for a new xPDOCriteria instance.
     *
     * The constructor optionally prepares provided SQL and/or parameter
     * bindings.  Setting the bindings via the constructor or with the {@link
     * xPDOCriteria::bind()} function allows you to make use of the data object
     * caching layer.
     *
     * The statement will not be prepared immediately if the cacheFlag value is
     * true or a positive integer, in order to allow the result to be found in
     * the cache before being queried from an actual database connection.
     *
     * @param xPDO &$xpdo An xPDO instance that will control this criteria.
     * @param string $sql The SQL statement.
     * @param array $bindings Bindings to bind to the criteria.
     * @param boolean|integer $cacheFlag Indicates if the result set from the
     * criteria is to be cached (true|false) or optionally a TTL in seconds.
     * @return xPDOCriteria
     */
    function xPDOCriteria(& $xpdo, $sql= '', $bindings= array (), $cacheFlag= false) {
        $this->__construct($xpdo, $sql, $bindings, $cacheFlag);
    }
    /** @ignore */
    function __construct(& $xpdo, $sql= '', $bindings= array (), $cacheFlag= false) {
        $this->xpdo= & $xpdo;
        $this->cacheFlag= $cacheFlag;
        if (is_string($sql) && !empty ($sql)) {
            $this->sql= $sql;
            if ($cacheFlag === false || $cacheFlag < 0) {
                $this->stmt= $xpdo->prepare($sql);
            }
            if (!empty ($bindings)) {
                $this->bind($bindings, true, $cacheFlag);
            }
        }
    }
    /**#@-*/

    /**
     * Binds an array of key/value pairs to the xPDOCriteria prepared statement.
     *
     * Use this method to bind parameters in a way that makes it possible to
     * cache results of previous executions of the criteria or compare the
     * criteria to other individual or collections of criteria.
     *
     * @param array $bindings Bindings to merge with any existing bindings
     * defined for this xPDOCriteria instance.  Bindings can be simple
     * associative array of key-value pairs or the value for each key can
     * contain elements titled value, type, and length corresponding to the
     * appropriate parameters in the PDOStatement::bindValue() and
     * PDOStatement::bindParam() functions.
     * @param boolean $byValue Determines if the $bindings are to be bound as
     * parameters (by variable reference, the default behavior) or by direct
     * value (if true).
     * @param boolean|integer $cacheFlag The cacheFlag indicates the cache state
     * of the xPDOCriteria object and can be absolutely off (false), absolutely
     * on (true), or an integer indicating the number of seconds the result will
     * live in the cache.
     */
    function bind($bindings= array (), $byValue= true, $cacheFlag= null) {
        if (!empty ($bindings)) {
            $this->bindings= array_merge($this->bindings, $bindings);
        }
        if (is_object($this->stmt) && $this->stmt && !empty ($this->bindings)) {
            reset($this->bindings);
            while (list ($key, $val)= each($this->bindings)) {
                if (is_array($val)) {
                    $type= isset ($val['type']) ? $val['type'] : PDO_PARAM_STR;
                    $length= isset ($val['length']) ? $val['length'] : 0;
                    $value= & $val['value'];
                } else {
                    $value= & $val;
                    $type= PDO_PARAM_STR;
                    $length= 0;
                }
                if (is_int($key)) $key= $key + 1;
                if ($byValue) {
                    $this->stmt->bindValue($key, $value, $type);
                } else {
                    $this->stmt->bindParam($key, $value, $type, $length);
                }
            }
        }
        $this->cacheFlag= $cacheFlag === null ? $this->cacheFlag : $cacheFlag;
    }

    /**
     * Compares to see if two xPDOCriteria instances are the same.
     *
     * @param object $obj A xPDOCriteria object to compare to this one.
     * @return boolean true if they are both equal is SQL and bindings, otherwise
     * false.
     */
    function equals($obj) {
        return (is_object($obj) && is_a($obj, 'xPDOCriteria') && $this->sql === $obj->sql && !array_diff_assoc($this->bindings, $obj->bindings));
    }

    /**
     * Prepares the sql and bindings of this instance into a PDOStatement.
     *
     * The {@link xPDOCriteria::$sql} attribute must be set in order to prepare
     * the statement. You can also pass bindings directly to this function and
     * they will be run through {@link xPDOCriteria::bind()} if the statement
     * is successfully prepared.
     *
     * If the {@link xPDOCriteria::$stmt} already exists, it is simply returned.
     *
     * @param array $bindings Bindings to merge with any existing bindings
     * defined for this xPDOCriteria instance.  Bindings can be simple
     * associative array of key-value pairs or the value for each key can
     * contain elements titled value, type, and length corresponding to the
     * appropriate parameters in the PDOStatement::bindValue() and
     * PDOStatement::bindParam() functions.
     * @param boolean $byValue Determines if the $bindings are to be bound as
     * parameters (by variable reference, the default behavior) or by direct
     * value (if true).
     * @param boolean|integer $cacheFlag The cacheFlag indicates the cache state
     * of the xPDOCriteria object and can be absolutely off (false), absolutely
     * on (true), or an integer indicating the number of seconds the result will
     * live in the cache.
     * @return PDOStatement The prepared statement, ready to execute.
     */
    function prepare($bindings= array (), $byValue= true, $cacheFlag= null) {
        if ($this->stmt === null || !is_object($this->stmt)) {
            if (!empty ($this->sql) && $stmt= $this->xpdo->prepare($this->sql)) {
                $this->stmt= & $stmt;
                $this->bind($bindings, $byValue, $cacheFlag);
            }
        }
        return $this->stmt;
    }

    function toSQL() {
        $sql = $this->sql;
        if (!empty($this->bindings)) {
            $sql = $this->xpdo->parseBindings($sql, $this->bindings);
        }
        return $sql;
    }
}
?>