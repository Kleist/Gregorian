<?php
/*
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
 * Classes implementing a default cache implementation for xPDO.
 *
 * @package xpdo
 * @subpackage cache
 */

if (!defined('XPDO_CACHE_PHP')) {
    /**
     * A flag to indicate cacheing to executable PHP format.
     * @var integer
     */
    define('XPDO_CACHE_PHP', 0);
    /**
     * A flag to indicate cacheing to JSON format.
     * @var integer
     */
    define('XPDO_CACHE_JSON', 1);
    /**
     * The default cache directory for criteria result sets.
     * @var string
     */
    define('XPDO_CACHE_DIR', 'objects/');
    /**
     * The default log directory for xPDO.
     * @var string
     */
    define('XPDO_LOG_DIR', 'logs/');
}

/**
 * The default cache manager implementation for xPDO.
 *
 * @package xpdo
 * @subpackage cache
 */
class xPDOCacheManager {
    var $xpdo= null;
    var $caches= array();
    var $options= array();

    function xPDOCacheManager(& $xpdo, $options = array()) {
        $this->__construct($xpdo, $options);
    }

    function __construct(& $xpdo, $options = array()) {
        $this->xpdo= & $xpdo;
        $this->options= $options;
    }

    /**
     * Get an instance of a provider which implements the xPDOCache interface.
     */
    function & getCacheProvider($key = '', $options = array()) {
        $objCache = null;
        if (empty($key)) {
            $key = $this->getOption(XPDO_OPT_CACHE_KEY, $options, 'default');
        }
        $objCacheClass= 'xPDOFileCache';
        if (!isset($this->caches[$key]) || !is_object($this->caches[$key])) {
            if ($cacheClass = $this->getOption($key . '_' . XPDO_OPT_CACHE_HANDLER, $options, $this->getOption(XPDO_OPT_CACHE_HANDLER, $options))) {
                $cacheClass = $this->xpdo->loadClass($cacheClass, XPDO_CORE_PATH, false, true);
                if ($cacheClass) {
                    $objCacheClass= $cacheClass;
                }
            }
            $options[XPDO_OPT_CACHE_KEY]= $key;
            $this->caches[$key] = new $objCacheClass($this->xpdo, $options);
            if (empty($this->caches[$key]) || !$this->caches[$key]->isInitialized()) {
                $this->caches[$key] = new xPDOFileCache($this->xpdo, $options);
            }
            $objCache = $this->caches[$key];
            $objCacheClass= get_class($objCache);
        } else {
            $objCache =& $this->caches[$key];
            $objCacheClass= get_class($objCache);
        }
        if ($this->xpdo->getDebug() === true) $this->xpdo->log(MODX_LOG_LEVEL_DEBUG, "Returning {$objCacheClass}:{$key} cache provider from available providers: " . print_r(array_keys($this->caches), 1));
        return $objCache;
    }

    /**
     * Get an option from supplied options, the cacheManager options, or xpdo itself.
     *
     * @param string $key Unique identifier for the option.
     * @param array $options A set of explicit options to override those from xPDO or the
     * xPDOCacheManager implementation.
     * @param mixed $default An optional default value to return if no value is found.
     * @return mixed The value of the option.
     */
    function getOption($key, $options = array(), $default = null) {
        $option = $default;
        if (is_array($key)) {
            $option = array();
            foreach ($key as $k) {
                $option[$k]= $this->getOption($k, $options, $default);
            }
        } elseif (is_string($key) && !empty($key)) {
            if (is_array($options) && array_key_exists($key, $options)) {
                $option = $options[$key];
            } elseif (array_key_exists($key, $this->options)) {
                $option = $options[$key];
            } else {
                $option = $this->xpdo->getOption($key, array(), $default);
            }
        }
        return $option;
    }

    /**
     * Get the absolute path to a writable directory for storing files.
     *
     * @access public
     * @return string The absolute path of the xPDO cache directory.
     */
    function getCachePath() {
        $cachePath= false;
        if (empty($this->xpdo->cachePath)) {
            if (!isset ($this->xpdo->config['cache_path'])) {
                while (true) {
                    if (!empty ($_ENV['TMP'])) {
                        if ($cachePath= strtr($_ENV['TMP'], '\\', '/'))
                            break;
                    }
                    if (!empty ($_ENV['TMPDIR'])) {
                        if ($cachePath= strtr($_ENV['TMPDIR'], '\\', '/'))
                            break;
                    }
                    if (!empty ($_ENV['TEMP'])) {
                        if ($cachePath= strtr($_ENV['TEMP'], '\\', '/'))
                            break;
                    }
                    if ($temp_file= @ tempnam(md5(uniqid(rand(), TRUE)), '')) {
                        $cachePath= strtr(dirname($temp_file), '\\', '/');
                        @ unlink($temp_file);
                    }
                    break;
                }
                if ($cachePath) {
                    if ($cachePath{strlen($cachePath) - 1} != '/') $cachePath .= '/';
                    $cachePath .= '.xpdo-cache';
                }
            }
            else {
                $cachePath= strtr($this->xpdo->config['cache_path'], '\\', '/');
            }
        } else {
            $cachePath= $this->xpdo->cachePath;
        }
        if ($cachePath) {
            $perms = $this->getOption('new_folder_permissions');
            if (empty($perms)) $perms = '0775';
            $perms = octdec($perms);
            if (@ $this->writeTree($cachePath, $perms)) {
                if ($cachePath{strlen($cachePath) - 1} != '/') $cachePath .= '/';
                if (!is_writeable($cachePath)) {
                    @ chmod($cachePath, 0777);
                }
            } else {
                $cachePath= false;
            }
        }
        return $cachePath;
    }

    /**
     * Writes a file to the filesystem
     *
     * @access public
     * @param string $filename The absolute path to the location the file will
     * be written in.
     * @param string $content The content of the newly written file.
     * @param string $mode The php file mode to write in. Defaults to 'wb'
     * @param integer $dirMode The chmod mode to put the file in, if possible.
     * Defaults to 0777.
     * @return boolean Returns true if the file was successfully written.
     */
    function writeFile($filename, $content, $mode= 'wb', $dirMode= 0777) {
        $written= false;
        $dirname= dirname($filename);
        if (!file_exists($dirname)) {
            if ($this->writeTree($dirname, $dirMode)) {
                $file= @ fopen($filename, $mode);
            }
        }
        if ($file= @ fopen($filename, $mode)) {
            $written= @ fwrite($file, $content);
            @ fclose($file);
        }
        return $written;
    }

    /**
     * Recursively writes a directory tree of files to the filesystem
     *
     * @access public
     * @param string $dirname The directory to write
     * @param integer $mode The mode to write the directory in. Defaults to
     * 0777.
     * @return boolean Returns true if the directory was successfully written.
     */
    function writeTree($dirname, $mode= 0777) {
        $written= false;
        if (!empty ($dirname)) {
            $dirname= strtr(trim($dirname), '\\', '/');
            if ($dirname{strlen($dirname) - 1} == '/') $dirname = substr($dirname, 0, strlen($dirname) - 1);
            if (is_dir($dirname) || (is_writable(dirname($dirname)) && @mkdir($dirname, $mode))) {
                $written= true;
            } elseif (!$this->writeTree(dirname($dirname), $mode)) {
                $written= false;
            } else {
                $written= @ mkdir($dirname, $mode);
            }
            if ($written && !is_writable($dirname)) {
                @ chmod($dirname, $mode);
            }
        }
        return $written;
    }

    /**
     * Copies a file from a source file to a target directory.
     *
     * @access public
     * @param string $source The absolute path of the source file.
     * @param string $target The absolute path of the target destination
     * directory.
     * @param integer $fileMode The mode to write the copied file in.
     * @param integer $dirMode The mode to write the target directory in.
     * @return boolean Returns true if the copying was successful.
     */
    function copyFile($source, $target, $fileMode= 0666, $dirMode= 0777) {
        $copied= false;
        if ($this->writeTree(dirname($target), $dirMode)) {
            $copied= @ copy($source, $target);
            if ($copied) {
                @ chmod($target, $fileMode);
                @ touch($target, filemtime($source));
            }
        }
        if (!$copied) {
            $this->xpdo->log(XPDO_LOG_LEVEL_ERROR, "Could not copy file {$source} to {$target}");
        }
        return $copied;
    }

    /**
     * Recursively copies a directory tree from a source directory to a target
     * directory.
     *
     * @access public
     * @param string $source The absolute path of the source directory.
     * @param string $target The absolute path of the target destination
     * directory.
     * @param integer $dirMode The mode to write the target directory in.
     * @param integer $fileMode The mode to write the copied files in.
     * @return boolean Returns true if the copying was successful.
     */
    function copyTree($source, $target, $dirMode= 0777, $fileMode= 0666) {
        $copied= false;
        $source= strtr($source, '\\', '/');
        $target= strtr($target, '\\', '/');
        if ($source{strlen($source) - 1} == '/') $source = substr($source, 0, strlen($source) - 1);
        if ($target{strlen($target) - 1} == '/') $target = substr($target, 0, strlen($target) - 1);
        if (is_dir($source . '/')) {
            if (!is_dir($target . '/')) {
                $this->writeTree($target . '/', $dirMode);
            }
            if (is_dir($target)) {
                if (!is_writable($target)) {
                    if (! @ chmod($target, $dirMode)) {
                        $this->xpdo->log(XPDO_LOG_LEVEL_ERROR, "{$target} is not writable and permissions could not be modified.");
                    }
                }
                if ($handle= @ opendir($source)) {
                    while (false !== ($item= readdir($handle))) {
                        if (in_array($item, array ('.', '..','.svn','.svn/','.svn\\'))) continue;
                        $from= $source . '/' . $item;
                        $to= $target . '/' . $item;
                        if (is_dir($from)) {
                            if (!$copied= $this->copyTree($from, $to, $dirMode, $fileMode)) {
                                $this->xpdo->log(XPDO_LOG_LEVEL_ERROR, "Could not copy directory {$from} to {$to}");
                            }
                        } elseif (is_file($from)) {
                            if (!$copied= $this->copyFile($from, $to, $fileMode)) {
                                $this->xpdo->log(XPDO_LOG_LEVEL_ERROR, "Could not copy file {$from} to {$to}; could not create directory.");
                            }
                        } else {
                            $this->xpdo->log(XPDO_LOG_LEVEL_ERROR, "Could not copy {$from} to {$to}");
                        }
                    }
                    @ closedir($handle);
                } else {
                    $this->xpdo->log(XPDO_LOG_LEVEL_ERROR, "Could not read source directory {$source}");
                }
            } else {
                $this->xpdo->log(XPDO_LOG_LEVEL_ERROR, "Could not create target directory {$target}");
            }
        } else {
            $this->xpdo->log(XPDO_LOG_LEVEL_ERROR, "Source directory {$source} does not exist.");
        }
        return $copied;
    }

    /**
     * Recursively deletes a directory tree of files.
     *
     * @access public
     * @param string $dirname An absolute path to the source directory to
     * delete.
     * @param boolean $deleteTop If true, will delete the top directory.
     * Defaults to false.
     * @param boolean $skipDirs If true, will only delete files and leave
     * directories intact. Defaults to false.
     * @param array $extensions If not empty, will only delete files with the
     * specified file extensions. Defaults to .cache.php
     * @return boolean Returns true if the deletion was successful.
     */
    function deleteTree($dirname, $deleteTop= false, $skipDirs= false, $extensions= array('.cache.php')) {
        $result= false;
        if (is_dir($dirname)) { /* Operate on dirs only */
            if (substr($dirname, -1) != '/') {
                $dirname .= '/';
            }
            $result= array ();
            $hasMore= true;
            if ($handle= opendir($dirname)) {
                $limit= 4;
                while ($hasMore && $limit--) {
                    if (!$handle) {
                        $handle= opendir($dirname);
                    }
                    $hasMore= false;
                    while (false !== ($file= @ readdir($handle))) {
                        if ($file != '.' && $file != '..') { /* Ignore . and .. */
                            $path= $dirname . $file;
                            if (is_dir($path)) {
                                if ($subresult= $this->deleteTree($path, ($skipDirs ? false : $deleteTop), $skipDirs, $extensions)) {
                                    $result= array_merge($result, $subresult);
                                }
                            }
                            elseif (empty($extensions) || $this->endsWith($file, $extensions)) {
                                if (unlink($path)) {
                                    array_push($result, $path);
                                } else {
                                    $hasMore= true;
                                }
                            }
                        }
                    }
                    closedir($handle);
                }
                $deleteTop= $skipDirs ? false : $deleteTop;
                if ($deleteTop) {
                    if (@ rmdir($dirname)) {
                        array_push($result, $dirname);
                    }
                }
            }
        } else {
            $result= false; /* return false if attempting to operate on a file */
        }
        return $result;
    }

    /**
     * Sees if a string ends with a specific pattern.
     *
     * @access public
     * @param string $string The string to check.
     * @param string $pattern The pattern to check against.
     * @return boolean True if the pattern was found in the string.
     */
    function endsWith($string, $pattern) {
        $matched= false;
        if (is_string($string) && ($stringLen= strlen($string))) {
            if (is_array($pattern)) {
                foreach ($pattern as $subPattern) {
                    if (is_string($subPattern) && $this->endsWith($string, $subPattern)) {
                        $matched= true;
                        break;
                    }
                }
            } elseif (is_string($pattern)) {
                if (($patternLen= strlen($pattern)) && $stringLen >= $patternLen) {
                    $matched= (substr($string, -$patternLen) === $pattern);
                }
            }
        }
        return $matched;
    }

    /**
     * Generate a PHP executable representation of an xPDOObject.
     *
     * @todo Complete $generateRelated functionality.
     * @todo Add stdObject support.
     *
     * @access public
     * @param xPDOObject $obj An xPDOObject to generate the cache file for
     * @param string $objName The name of the xPDOObject
     * @param boolean $generateObjVars If true, will also generate maps for all
     * object variables. Defaults to false.
     * @param boolean $generateRelated If true, will also generate maps for all
     * related objects. Defaults to false.
     * @param string $objRef The reference to the xPDO instance, in string
     * format.
     * @param boolean $format The format to cache in. Defaults to
     * XPDO_CACHE_PHP, which is set to cache in executable PHP format.
     * @return string The source map file, in string format.
     */
    function generateObject($obj, $objName, $generateObjVars= false, $generateRelated= false, $objRef= 'this->xpdo', $format= XPDO_CACHE_PHP) {
        $source= false;
        if (is_object($obj) && is_a($obj, 'xPDOObject')) {
            $className= $obj->_class;
            $source= "\${$objName}= \${$objRef}->newObject('{$className}');\n";
            $source .= "\${$objName}->fromArray(" . var_export($obj->toArray('', true), true) . ", '', true, true);\n";
            if ($generateObjVars && $objectVars= get_object_vars($obj)) {
                while (list ($vk, $vv)= each($objectVars)) {
                    if ($vk === 'modx') {
                        $source .= "\${$objName}->{$vk}= & \${$objRef};\n";
                    }
                    elseif ($vk === 'xpdo') {
                        $source .= "\${$objName}->{$vk}= & \${$objRef};\n";
                    }
                    elseif (!is_resource($vv)) {
                        $source .= "\${$objName}->{$vk}= " . var_export($vv, true) . ";\n";
                    }
                }
            }
            if ($generateRelated && !empty ($obj->_relatedObjects)) {
                foreach ($obj->_relatedObjects as $className => $fk) {
                    foreach ($fk as $key => $relObj) {} /* TODO: complete $generateRelated functionality */
                }
            }
        }
        return $source;
    }

    /**
     * Add a key-value pair to a cache provider if it does not already exist.
     *
     * @param string $key A unique key identifying the item being stored.
     * @param mixed & $var A reference to the PHP variable representing the item.
     * @param integer $lifetime Seconds the item will be valid in cache.
     * @param array $options Additional options for the cache add operation.
     */
    function add($key, & $var, $lifetime= 0, $options= array()) {
        $return= false;
        if ($cache = $this->getCacheProvider($this->getOption(XPDO_OPT_CACHE_KEY, $options))) {
            $value= null;
            if (is_object($var) && is_a($var, 'xPDOObject')) {
                $value= $var->toArray('', true);
            } else {
                $value= $var;
            }
            $return= $cache->add($key, $value, $lifetime, $options);
        }
        return $return;
    }

    /**
     * Replace a key-value pair in in a cache provider.
     *
     * @access public
     * @param string $key A unique key identifying the item being replaced.
     * @param mixed & $var A reference to the PHP variable representing the item.
     * @param integer $lifetime Seconds the item will be valid in objcache.
     * @param array $options Additional options for the cache replace operation.
     * @return boolean True if the replace was successful.
     */
    function replace($key, & $var, $lifetime= 0, $options= array()) {
        $return= false;
        if ($cache = $this->getCacheProvider($this->getOption(XPDO_OPT_CACHE_KEY, $options), $options)) {
            $value= null;
            if (is_object($var) && is_a($var, 'xPDOObject')) {
                $value= $var->toArray('', true);
            } else {
                $value= $var;
            }
            $return= $cache->replace($key, $value, $lifetime, $options);
        }
        return $return;
    }

    /**
     * Set a key-value pair in a cache provider.
     *
     * @access public
     * @param string $key A unique key identifying the item being set.
     * @param mixed & $var A reference to the PHP variable representing the item.
     * @param integer $lifetime Seconds the item will be valid in objcache.
     * @param array $options Additional options for the cache set operation.
     * @return boolean True if the set was successful
     */
    function set($key, & $var, $lifetime= 0, $options= array()) {
        $return= false;
        if ($cache = $this->getCacheProvider($this->getOption(XPDO_OPT_CACHE_KEY, $options), $options)) {
            $value= null;
            if (is_object($var) && is_a($var, 'xPDOObject')) {
                $value= $var->toArray('', true);
            } else {
                $value= $var;
            }
            $return= $cache->set($key, $value, $lifetime, $options);
        } else {
            $this->xpdo->log(XPDO_LOG_LEVEL_ERROR, 'No cache implementation found.');
        }
        return $return;
    }

    /**
     * Delete a key-value pair from a cache provider.
     *
     * @access public
     * @param string $key A unique key identifying the item being deleted.
     * @param array $options Additional options for the cache deletion.
     * @return boolean True if the deletion was successful.
     */
    function delete($key, $options = array()) {
        $return= false;
        if ($cache = $this->getCacheProvider($this->getOption(XPDO_OPT_CACHE_KEY, $options), $options)) {
            $return= $cache->delete($key, $options);
        }
        return $return;
    }

    /**
     * Get a value from a cache provider by key.
     *
     * @access public
     * @param string $key A unique key identifying the item being retrieved.
     * @param array $options Additional options for the cache retrieval.
     * @return mixed The value of the object cache key
     */
    function get($key, $options = array()) {
        $return= false;
        if ($cache = $this->getCacheProvider($this->getOption(XPDO_OPT_CACHE_KEY, $options), $options)) {
            $return= $cache->get($key, $options);
        }
        return $return;
    }

    /**
     * Flush the contents of a cache provider.
     *
     * @access public
     * @param array $options Additional options for the cache flush.
     * @return boolean True if the flush was successful.
     */
    function clean($options = array()) {
        $return= false;
        if ($cache = $this->getCacheProvider($this->getOption(XPDO_OPT_CACHE_KEY, $options), $options)) {
            $return= $cache->flush($options);
        }
        return $return;
    }

    /**
     * Escapes all single quotes in a string
     *
     * @access public
     * @param string $s The string to escape single quotes in.
     * @return string The string with single quotes escaped.
     */
    function escapeSingleQuotes($s) {
        $q1= array (
            "\\",
            "'"
        );
        $q2= array (
            "\\\\",
            "\\'"
        );
        return str_replace($q1, $q2, $s);
    }
}

/**
 * An interface class that defines the methods a cache provider must implement.
 *
 * @package xpdo
 * @subpackage cache
 */
class xPDOCache {
    var $xpdo= null;
    var $options= array();
    var $key= '';
    var $initialized= false;

    function xPDOCache(& $xpdo, $options = array()) {
        $this->__construct($xpdo);
    }
    function __construct(& $xpdo, $options = array()) {
        $this->xpdo= & $xpdo;
        $this->options= $options;
        $this->key = $this->getOption(XPDO_OPT_CACHE_KEY, $options, 'default');
    }

    /**
     * Indicates if this xPDOCache instance has been properly initialized.
     *
     * @return boolean true if the implementation was initialized successfully.
     */
    function isInitialized() {
        return (boolean) $this->initialized;
    }

    /**
     * Get an option from supplied options, the cache options, or the xpdo config.
     *
     * @param string $key Unique identifier for the option.
     * @param array $options A set of explicit options to override those from xPDO or the xPDOCache
     * implementation.
     * @param mixed $default An optional default value to return if no value is found.
     * @return mixed The value of the option.
     */
    function getOption($key, $options = array(), $default = null) {
        $option = $default;
        if (is_array($key)) {
            $option = array();
            foreach ($key as $k) {
                $option[$k]= $this->getOption($k, $options, $default);
            }
        } elseif (is_string($key) && !empty($key)) {
            if (is_array($options) && array_key_exists($key, $options)) {
                $option = $options[$key];
            } elseif (array_key_exists($key, $this->options)) {
                $option = $this->options[$key];
            } else {
                $option = $this->xpdo->getOption($key, array(), $default);
            }
        }
        return $option;
    }

    /**
     * Get the actual cache key the implementation will use.
     *
     * @param string $key The identifier the application uses.
     * @param array $options Additional options for the operation.
     * @return string The identifier with any implementation specific prefixes or other
     * transformations applied.
     */
    function getCacheKey($key, $options = array()) {
        $prefix = $this->getOption('cache_prefix', $options);
        if (!empty($prefix)) $key = $prefix . $key;
        return $key;
    }

    /**
     * Adds a value to the cache.
     *
     * @access public
     * @param string $key A unique key identifying the item being set.
     * @param string $var A reference to the PHP variable representing the item.
     * @param integer $expire The amount of seconds for the variable to expire in.
     * @param array $options Additional options for the operation.
     * @return boolean True if successful
     */
    function add($key, $var, $expire= 0, $options= array()) {}

    /**
     * Sets a value in the cache.
     *
     * @access public
     * @param string $key A unique key identifying the item being set.
     * @param string $var A reference to the PHP variable representing the item.
     * @param integer $expire The amount of seconds for the variable to expire in.
     * @param array $options Additional options for the operation.
     * @return boolean True if successful
     */
    function set($key, $var, $expire= 0, $options= array()) {}

    /**
     * Replaces a value in the cache.
     *
     * @access public
     * @param string $key A unique key identifying the item being set.
     * @param string $var A reference to the PHP variable representing the item.
     * @param integer $expire The amount of seconds for the variable to expire in.
     * @param array $options Additional options for the operation.
     * @return boolean True if successful
     */
    function replace($key, $var, $expire= 0, $options= array()) {}

    /**
     * Deletes a value from the cache.
     *
     * @access public
     * @param string $key A unique key identifying the item being deleted.
     * @param array $options Additional options for the operation.
     * @return boolean True if successful
     */
    function delete($key, $options= array()) {}

    /**
     * Gets a value from the cache.
     *
     * @access public
     * @param string $key A unique key identifying the item to fetch.
     * @param array $options Additional options for the operation.
     * @return mixed The value retrieved from the cache.
     */
    function get($key, $options= array()) {}

    /**
     * Flush all values from the cache.
     *
     * @access public
     * @param array $options Additional options for the operation.
     * @return boolean True if successful.
     */
    function flush($options= array()) {}
}

/**
 * A simple file-based caching implementation using executable PHP.
 *
 * This can be used to relieve database loads, though the overall performance is
 * about the same as without the file-based cache.  For maximum performance and
 * scalability, use a server with memcached and the PHP memcache extension
 * configured.
 *
 * @package xpdo
 * @subpackage cache
 */
class xPDOFileCache extends xPDOCache {
    function xPDOFileCache(& $xpdo, $options = array()) {
        $this->__construct($xpdo);
    }
    function __construct(& $xpdo, $options = array()) {
        parent :: __construct($xpdo, $options);
        $this->initialized = true;
    }

    function getCacheKey($key, $options = array()) {
        $cachePath = $this->getOption('cache_path', $options);
        $cacheExt = $this->getOption('cache_ext', $options, '.cache.php');
        $key = parent :: getCacheKey($key, $options);
        return $cachePath . $key . $cacheExt;
    }

    function add($key, $var, $expire= 0, $options= array()) {
        $added= false;
        if (!file_exists($this->getCacheKey($key, $options))) {
            if ($expire === true)
                $expire= 0;
            $added= $this->set($key, $var, $expire, $options);
        }
        return $added;
    }

    function set($key, $var, $expire= 0, $options= array()) {
        $set= false;
        if ($var !== null) {
            if ($expire === true)
                $expire= 0;
            $expirationTS= $expire ? time() + $expire : 0;
            $expireContent= '';
            if ($expirationTS) {
                $expireContent= 'if(time() > ' . $expirationTS . '){return null;}';
            }
            $fileName= $this->getCacheKey($key, $options);
            if (!empty($options['format']) && $options['format'] == XPDO_CACHE_JSON) {
                $content= !is_scalar($var) ? $this->xpdo->toJSON($var) : $var;
            } else {
                $content= '<?php ' . $expireContent . ' return ' . var_export($var, true) . ';';
            }
            $set= $this->xpdo->cacheManager->writeFile($fileName, $content);
        }
        return $set;
    }

    function replace($key, $var, $expire= 0, $options= array()) {
        $replaced= false;
        if (file_exists($this->getCacheKey($key, $options))) {
            if ($expire === true)
                $expire= 0;
            $replaced= $this->set($key, $var, $expire, $options);
        }
        return $replaced;
    }

    function delete($key, $options= array()) {
        $deleted= false;
        $cacheKey= $this->getCacheKey($key, array_merge($options, array('cache_ext' => '')));
        if (file_exists($cacheKey) && is_dir($cacheKey)) {
            $deleted= $this->xpdo->cacheManager->deleteTree($cacheKey, false, true);
        } else {
            $cacheKey.= $this->getOption('cache_ext', $options, '.cache.php');
            if (file_exists($cacheKey)) {
                $deleted= @ unlink($cacheKey);
            }
        }
        return $deleted;
    }

    function get($key, $options= array()) {
        $value= null;
        $cacheKey= $this->getCacheKey($key, $options);
        if (file_exists($cacheKey)) {
            if (!empty($options['format']) && $options['format'] == XPDO_CACHE_JSON) {
                $value= file_get_contents($cacheKey);
            } else {
                $value= @ include ($cacheKey);
            }
            if ($value === null && $this->getOption('removeIfEmpty', $options, true)) {
                @ unlink($cacheKey);
            }
        }
        return $value;
    }

    function flush($options= array()) {
        $cacheKey= $this->getCacheKey('', array_merge($options, array('cache_ext' => '')));
        return $this->xpdo->cacheManager->deleteTree($cacheKey, false, true);
    }
}