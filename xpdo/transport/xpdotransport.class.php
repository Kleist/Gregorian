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
 * Represents a transportable package of related data and other resources.
 *
 * @package xpdo
 * @subpackage transport
 */

/**
 * Include the PclZip compression library.
 */
require_once (XPDO_CORE_PATH . 'compression/pclzip.lib.php');

if (!defined('XPDO_TRANSPORT_PRESERVE_KEYS')) {
    /**#@+
     * Attributes of the package that can be used to control behavior.
     * @var string
     */
    define('XPDO_TRANSPORT_PRESERVE_KEYS', 'preserve_keys');
    define('XPDO_TRANSPORT_NATIVE_KEY', 'native_key');
    define('XPDO_TRANSPORT_UNIQUE_KEY', 'unique_key');
    define('XPDO_TRANSPORT_UPDATE_OBJECT', 'update_object');
    define('XPDO_TRANSPORT_RESOLVE_FILES', 'resolve_files');
    define('XPDO_TRANSPORT_RESOLVE_FILES_REMOVE','resolve_files_remove');
    define('XPDO_TRANSPORT_RESOLVE_PHP', 'resolve_php');
    define('XPDO_TRANSPORT_PACKAGE_ACTION', 'package_action');
    define('XPDO_TRANSPORT_PACKAGE_STATE', 'package_state');
    define('XPDO_TRANSPORT_NAMESPACE', 'namespace');
    define('XPDO_TRANSPORT_RELATED_OBJECTS', 'related_objects');
    define('XPDO_TRANSPORT_RELATED_OBJECT_ATTRIBUTES', 'related_object_attributes');
    define('XPDO_TRANSPORT_MANIFEST_ATTRIBUTES', 'manifest-attributes');
    define('XPDO_TRANSPORT_MANIFEST_VEHICLES', 'manifest-vehicles');
    define('XPDO_TRANSPORT_MANIFEST_VERSION', 'manifest-version');
    define('XPDO_TRANSPORT_PREEXISTING_MODE', 'preexisting_mode');
    /**
     * Indicates how pre-existing objects are treated on install/uninstall.
     * @var integer
     */
    define('XPDO_TRANSPORT_PRESERVE_PREEXISTING', 0);
    define('XPDO_TRANSPORT_REMOVE_PREEXISTING', 1);
    define('XPDO_TRANSPORT_RESTORE_PREEXISTING', 2);
    /**
     * Indicates the physical state of the package.
     * @var integer
     */
    define('XPDO_TRANSPORT_STATE_UNPACKED', 0);
    define('XPDO_TRANSPORT_STATE_PACKED', 1);
    define('XPDO_TRANSPORT_STATE_INSTALLED', 2);
    /**
     * Indicates an action that can be performed on the package.
     * @var integer
     */
    define('XPDO_TRANSPORT_ACTION_INSTALL', 0);
    define('XPDO_TRANSPORT_ACTION_UPGRADE', 1);
    define('XPDO_TRANSPORT_ACTION_UNINSTALL', 2);
    /**#@-*/
}

/**
 * Represents xPDOObject and related data in a serialized format for exchange.
 *
 * @package xpdo
 * @subpackage transport
 */
class xPDOTransport {
    /**
     * An {@link xPDO} reference controlling this transport instance.
     * @var xPDO
     * @access public
     */
    var $xpdo = null;
    /**
     * A unique signature to identify the package.
     * @var string
     * @access public
     */
    var $signature = null;
    /**
     * Indicates the state of the xPDOTransport instance.
     * @var integer
     */
    var $state = null;
    /**
     * Stores various attributes about the transport package.
     * @var array
     */
    var $attributes = array ();
    /**
     * A map of object vehicles containing payloads of data for transport.
     * @var array
     */
    var $vehicles = array ();
    /**
     * The physical location of the transport package.
     * @var string
     */
    var $path = null;
    /**
     * The current manifest version for this transport.
     * @var string
     */
    var $manifestVersion = '1.1';
    /**
     * An map of preserved objects from an install used by uninstall.
     * @var array
     */
    var $_preserved = array();

    /**
     * Prepares and returns a new xPDOTransport instance.
     *
     * @param xPDO &$xpdo The xPDO instance accessing this package.
     * @param string $signature The unique signature of the package.
     * @param string $path Valid path to the physical transport package.
     */
    function xPDOTransport(& $xpdo, $signature, $path) {
        $this->xpdo = & $xpdo;
        $this->signature = $signature;
        $this->path = $path;
        $xpdo->loadClass('transport.xPDOVehicle', XPDO_CORE_PATH, true, true);
    }

    /**
     * Get an {@link xPDOVehicle} instance from an unpacked transport package.
     *
     * @param string $objFile Full path to a payload file to import.  The
     * payload file, when included must return a valid {@link xPDOVehicle::$payload}.
     * @param array $options An array of options to be applied when getting the
     * object.
     * @return xPDOVehicle The vehicle represented in the file.
     */
    function get($objFile, $options = array ()) {
        $vehicle = null;
        $objFile = $this->path . $this->signature . '/' . $objFile;
        $vehiclePackage = isset($options['vehicle_package']) ? $options['vehicle_package'] : '';
        $vehiclePackagePath = isset($options['vehicle_package_path']) ? $options['vehicle_package_path'] : '';
        $vehicleClass = isset($options['vehicle_class']) ? $options['vehicle_class'] : '';
        if (empty($vehiclePackage)) $vehiclePackage = $options['vehicle_package'] = 'transport';
        if (empty($vehicleClass)) $vehicleClass = $options['vehicle_class'] = 'xPDOObjectVehicle';
        if ($className = $this->xpdo->loadClass("{$vehiclePackage}.{$vehicleClass}", $vehiclePackagePath, true, true)) {
            $vehicle = new $className();
            if (file_exists($objFile)) {
                $payload = include ($objFile);
                if ($payload) {
                    $vehicle->payload = $payload;
                }
            }
        } else {
            $this->xpdo->log(XPDO_LOG_LEVEL_ERROR, "The specified xPDOVehicle class ({$vehiclePackage}.{$vehicleClass}) could not be loaded.");
        }
        return $vehicle;
    }

    /**
     * Install vehicles in the package into the sponsor {@link xPDO} instance.
     *
     * @param array $options Install options to be applied to the process.
     * @return boolean true if the vehicles were successfully installed.
     */
    function install($options = array ()) {
        $installed = false;
        $saved = array();
        $this->_preserved = array();
        if (!is_array($options)) {
            $options= array(XPDO_TRANSPORT_PACKAGE_ACTION => XPDO_TRANSPORT_ACTION_INSTALL);
        } elseif (!isset($options[XPDO_TRANSPORT_PACKAGE_ACTION])) {
            $options[XPDO_TRANSPORT_PACKAGE_ACTION]= XPDO_TRANSPORT_ACTION_INSTALL;
        }
        if (!empty ($this->vehicles)) {
            foreach ($this->vehicles as $vIndex => $vehicleMeta) {
                $vOptions = array_merge($options, $vehicleMeta);
                if ($vehicle = $this->get($vehicleMeta['filename'], $vOptions)) {
                   $saved[$vehicle->payload['guid']] = $vehicle->install($this, $vOptions);
                }
            }
        } else {
            $this->xpdo->log(XPDO_LOG_LEVEL_WARN, 'No vehicles are defined in the transport package (' . $this->signature . ') manifest for installation');
        }
        $this->writePreserved();
        if (!empty($saved)) {
            $installed = true;
        }
        return $installed;
    }

    /**
     * Uninstall vehicles in the package from the sponsor {@link xPDO} instance.
     *
     * @param array $options Uninstall options to be applied to the process.
     * @return boolean true if the vehicles were successfully uninstalled.
     */
    function uninstall($options = array ()) {
        $processed = array();
        if (!is_array($options)) {
            $options= array(XPDO_TRANSPORT_PACKAGE_ACTION => XPDO_TRANSPORT_ACTION_UNINSTALL);
        } elseif (!isset($options[XPDO_TRANSPORT_PACKAGE_ACTION])) {
            $options[XPDO_TRANSPORT_PACKAGE_ACTION]= XPDO_TRANSPORT_ACTION_UNINSTALL;
        }
        if (!empty ($this->vehicles)) {
            $this->_preserved = $this->loadPreserved();
            $vehicleArray = array_reverse($this->vehicles, true);
            foreach ($vehicleArray as $vIndex => $vehicleMeta) {
                $vOptions = array_merge($options, $vehicleMeta);
                if ($this->xpdo->getDebug() === true) {
                    $this->xpdo->log(XPDO_LOG_LEVEL_DEBUG, "Removing Vehicle: " . print_r($vOptions, true));
                }
                if ($vehicle = $this->get($vehicleMeta['filename'], $vOptions)) {
                    $processed[$vehicleMeta['guid']] = $vehicle->uninstall($this, $vOptions);
                } else {
                    $this->xpdo->log(XPDO_LOG_LEVEL_ERROR, 'Could not load vehicle: ' . print_r($vOptions, true));
                }
            }
        } else {
            $this->xpdo->log(XPDO_LOG_LEVEL_WARN, 'No vehicles are defined in the transport package (' . $this->signature . ') for removal');
        }
        $uninstalled = (array_search(false, $processed, true) === false);
        return $uninstalled;
    }

    /**
     * Wrap artifact with an {@link xPDOVehicle} and register in the transport.
     *
     * @param mixed $artifact An artifact to load into the transport.
     * @param array $attributes A set of attributes related to the artifact; these
     * can be anything from rules describing how to pack or unpack the artifact,
     * or any other data that might be useful when dealing with a transportable
     * artifact.
     */
    function put($artifact, $attributes = array ()) {
        $added = false;
        if (!empty($artifact)) {
            $vehiclePackage = isset($attributes['vehicle_package']) ? $attributes['vehicle_package'] : '';
            $vehiclePackagePath = isset($attributes['vehicle_package_path']) ? $attributes['vehicle_package_path'] : '';
            $vehicleClass = isset($attributes['vehicle_class']) ? $attributes['vehicle_class'] : '';
            if (empty($vehiclePackage)) $vehiclePackage = $attributes['vehicle_package'] = 'transport';
            if (empty($vehicleClass)) $vehicleClass = $attributes['vehicle_class'] = 'xPDOObjectVehicle';
            if ($className = $this->xpdo->loadClass("{$vehiclePackage}.{$vehicleClass}", $vehiclePackagePath, true, true)) {
                $vehicle = new $className();
                $vehicle->put($this, $artifact, $attributes);
                if ($added = $vehicle->store($this)) {
                    $this->registerVehicle($vehicle);
                }
            } else {
                $this->xpdo->log(XPDO_LOG_LEVEL_ERROR, "The specified xPDOVehicle class ({$vehiclePackage}.{$vehicleClass}) could not be loaded.");
            }
        }
        return $added;
    }

    /**
     * Pack the {@link xPDOTransport} instance in preparation for distribution.
     *
     * @uses xpdo/cache/pclzip.lib.php
     * @todo remove dependency on pclzip, making it optional when php zip
     * extension is not available
     * @return boolean Indicates if the transport was packed successfully.
     */
    function pack() {
        $packed = false;
        $this->writeManifest();
        $path = $this->path;
        $pos = strpos($path, ':');
        if ($pos !== false) {
            $path = substr($path, $pos +1);
        }
        $fileName = $path . $this->signature . '.transport.zip';
        $archive = new PclZip($fileName);
        if (!empty ($this->vehicles)) {
            $packResults = $archive->create("{$path}{$this->signature}", PCLZIP_OPT_REMOVE_PATH, "{$path}");
            if ($packResults == 0) {
                $this->xpdo->log(XPDO_LOG_LEVEL_ERROR, 'Error creating transport package ' . $fileName . ' : ' . $archive->errorInfo(true));
            }
            elseif ($this->xpdo->getDebug() === true) {
                $this->xpdo->log(XPDO_LOG_LEVEL_DEBUG, print_r($packResults, 1));
            }
        }
        return $packed;
    }

    /**
     * Write the package manifest file.
     *
     * @return boolean Indicates if the manifest was successfully written.
     */
    function writeManifest() {
        $written = false;
        if (!empty ($this->vehicles)) {
            if (!empty($this->attributes['setup-options']) && is_array($this->attributes['setup-options'])) {
                $cacheManager = $this->xpdo->getCacheManager();
                $cacheManager->copyFile($this->attributes['setup-options']['source'],$this->path . $this->signature . '/setup-options.php');

                $this->attributes['setup-options'] = $this->signature . '/setup-options.php';
            }
            $manifest = array(
                XPDO_TRANSPORT_MANIFEST_VERSION => $this->manifestVersion,
                XPDO_TRANSPORT_MANIFEST_ATTRIBUTES => $this->attributes,
                XPDO_TRANSPORT_MANIFEST_VEHICLES => $this->vehicles
            );
            $content = var_export($manifest, true);
            $cacheManager = $this->xpdo->getCacheManager();
            if ($content && $cacheManager) {
                $fileName = $this->path . $this->signature . '/manifest.php';
                $content = "<?php return {$content};";
                if (!($written = $cacheManager->writeFile($fileName, $content))) {
                    $this->xpdo->log(XPDO_LOG_LEVEL_ERROR, 'Error writing manifest to ' . $fileName);
                }
            }
        }
        return $written;
    }

    /**
     * Write objects preserved during install() to file for use by uninstall().
     *
     * @return boolean Indicates if the preserved file was successfully written.
     */
    function writePreserved() {
        $written = false;
        if (!empty($this->_preserved)) {
            $content = var_export($this->_preserved, true);
            $cacheManager = $this->xpdo->getCacheManager();
            if ($content && $cacheManager) {
                $fileName = $this->path . $this->signature . '/preserved.php';
                $content = "<?php return {$content};";
                if (!($written = $cacheManager->writeFile($fileName, $content))) {
                    $this->xpdo->log(XPDO_LOG_LEVEL_ERROR, 'Error writing preserved objects to ' . $fileName);
                }
            }
        }
        return $written;
    }

    /**
     * Load preserved objects from the previous install().
     *
     * @return array An array of preserved objects, or an empty array.
     */
    function loadPreserved() {
        $preserved = array();
        $fileName = $this->path . $this->signature . '/preserved.php';
        if (file_exists($fileName)) {
            $content = include($fileName);
            if (is_array($content)) {
                $preserved = $content;
            } else {
                $this->xpdo->log(XPDO_LOG_LEVEL_ERROR, 'Error loading preserved objects from ' . $fileName);
            }
        }
        return $preserved;
    }

    /**
     * Register an xPDOVehicle with this transport instance.
     *
     * @param xPDOVehicle &$vehicle A reference to the vehicle being registered.
     */
    function registerVehicle(& $vehicle) {
        $this->vehicles[] = $vehicle->register($this);
    }

    /**
     * Get an attribute of the package manifest.
     *
     * @param string $key The key of the attribute to retrieve.
     * @return mixed The value of the attribute or null if it is not set.
     */
    function getAttribute($key) {
        $value = null;
        if (array_key_exists($key, $this->attributes)) $value = $this->attributes[$key];
        return $value;
    }

    /**
     * Set an attribute of the package manifest.
     *
     * @param string $key The key identifying the attribute to set.
     * @param mixed $value The value to set the attribute to.
     */
    function setAttribute($key, $value) {
        $this->attributes[$key]= $value;
    }

    /**
     * Get an existing {@link xPDOTransport} instance.
     */
    function retrieve(& $xpdo, $source, $target, $state = XPDO_TRANSPORT_STATE_PACKED) {
        $instance = null;
        if (file_exists($source)) {
            if (is_writable($target)) {
                $manifest = xPDOTransport :: unpack($xpdo, $source, $target, $state);
                if ($manifest) {
                    $signature = basename($source, '.transport.zip');
                    $instance = new xPDOTransport($xpdo, $signature, $target);
                    if (!$instance) {
                        $xpdo->log(XPDO_LOG_LEVEL_ERROR, "Could not instantiate a valid xPDOTransport object from the package {$source} to {$target}. SIG: {$signature} MANIFEST: " . print_r($manifest, 1));
                    }
                    $manifestVersion = xPDOTransport :: manifestVersion($manifest);
                    switch ($manifestVersion) {
                        case '0.1':
                            $instance->vehicles = xPDOTransport :: _convertManifestVer1_1(xPDOTransport :: _convertManifestVer1_0($manifest));
                        case '0.2':
                            $instance->vehicles = xPDOTransport :: _convertManifestVer1_1(xPDOTransport :: _convertManifestVer1_0($manifest[XPDO_TRANSPORT_MANIFEST_VEHICLES]));
                            $instance->attributes = $manifest[XPDO_TRANSPORT_MANIFEST_ATTRIBUTES];
                            break;
                        case '1.0':
                            $instance->vehicles = xPDOTransport :: _convertManifestVer1_1($manifest[XPDO_TRANSPORT_MANIFEST_VEHICLES]);
                            $instance->attributes = $manifest[XPDO_TRANSPORT_MANIFEST_ATTRIBUTES];
                            break;
                        default:
                            $instance->vehicles = $manifest[XPDO_TRANSPORT_MANIFEST_VEHICLES];
                            $instance->attributes = $manifest[XPDO_TRANSPORT_MANIFEST_ATTRIBUTES];
                            break;
                    }
                } else {
                    $xpdo->log(XPDO_LOG_LEVEL_ERROR, "Could not unpack package {$source} to {$target}. SIG: {$signature}");
                }
            } else {
                $xpdo->log(XPDO_LOG_LEVEL_ERROR, "Could not unpack package: {$target} is not writable. SIG: {$signature}");
            }
        } else {
            $xpdo->log(XPDO_LOG_LEVEL_ERROR, "Package {$source} not found. SIG: {$signature}");
        }
        return $instance;
    }

    /**
     * Store the package to a specified resource location.
     *
     * @param mixed $location The location to store the package.
     */
    function store($location) {
        $stored = false;
        if ($this->state === XPDO_TRANSPORT_PACKED) {
            //TODO: store the packed package to a specified location (support any resource context)
        }
        return $stored;
    }

    /**
     * Unpack the package to prepare for installation and return a manifest.
     *
     * @uses xpdo/cache/pclzip.lib.php
     * @todo remove dependency on pclzip, making it optional when php zip
     * extension is not available
     * @param xPDO $xpdo A reference to an xPDO instance.
     * @param string $from Filename of the archive containing the transport
     * package.
     * @param string $to The root path where the contents of the archive should
     * be extracted.  This path must be writable by the user executing the PHP
     * process on the server.
     * @return array The manifest which is included after successful extraction.
     */
    function unpack(& $xpdo, $from, $to, $state = XPDO_TRANSPORT_STATE_PACKED) {
        $manifest = null;
        if ($state !== XPDO_TRANSPORT_STATE_UNPACKED) {
            $archive = new PclZip($from);
            $resources = $archive->extract(PCLZIP_OPT_PATH, $to);
        } else {
            $resources = true;
        }
        if ($resources) {
            $manifestFilename = $to . basename($from, '.transport.zip') . '/manifest.php';
            if (file_exists($manifestFilename)) {
                $manifest = @ include ($manifestFilename);
            } else {
                $xpdo->log(XPDO_LOG_LEVEL_ERROR, "Could not find package manifest at {$manifestFilename}");
            }
        }
        return $manifest;
    }

    /**
     * Returns the structure version of the given manifest array.
     *
     * @static
     * @param array $manifest A valid xPDOTransport manifest array.
     * @return string Version string of the manifest structure.
     */
    function manifestVersion($manifest) {
        $version = false;
        if (is_array($manifest)) {
            if (isset($manifest[XPDO_TRANSPORT_MANIFEST_VERSION])) {
                $version = $manifest[XPDO_TRANSPORT_MANIFEST_VERSION];
            }
            elseif (isset($manifest[XPDO_TRANSPORT_MANIFEST_VEHICLES])) {
                $version = '0.2';
            }
            else {
                $version = '0.1';
            }
        }
        return $version;
    }

    /**
     * Converts older manifest vehicles to 1.0 format.
     *
     * @static
     * @access private
     * @param array $manifestVehicles A structure representing vehicles from a pre-1.0 manifest
     * format.
     * @return array Vehicle definition structures converted to 1.0 format.
     */
    function _convertManifestVer1_0($manifestVehicles) {
        $manifest = array();
        foreach ($manifestVehicles as $vClass => $vehicles) {
            foreach ($vehicles as $vKey => $vehicle) {
                $entry = array(
                    'class' => $vClass,
                    'native_key' => $vehicle['native_key'],
                    'filename' => $vehicle['filename'],
                );
                if (isset($vehicle['namespace'])) {
                    $entry['namespace'] = $vehicle['namespace'];
                }
                $manifest[] = $entry;
            }
        }
        return $manifest;
    }

    /**
     * Converts 1.0 manifest vehicles to 1.1 format.
     *
     * @static
     * @access private
     * @param array $vehicles A structure representing vehicles from a pre-1.1 manifest format.
     * @return array Vehicle definition structures converted to 1.1 format.
     */
    function _convertManifestVer1_1($vehicles) {
        $manifest = array();
        foreach ($vehicles as $vKey => $vehicle) {
            $entry = $vehicle;
            if (!isset($vehicle['vehicle_class'])) {
                $entry['vehicle_class'] = 'xPDOObjectVehicle';
            }
            if (!isset($vehicle['vehicle_package'])) {
                $entry['vehicle_package'] = 'transport';
            }
            $manifest[] = $entry;
        }
        return $manifest;
    }
}
