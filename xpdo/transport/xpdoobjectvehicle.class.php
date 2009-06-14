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
 * Defines a class that represents an xPDOObject within a transportable package.
 *
 * @package xpdo
 * @subpackage transport
 */

/**
 * Represents an xPDOObject within an {@link xPDOTransport} package.
 * 
 * @package xpdo
 * @subpackage transport
 */
class xPDOObjectVehicle extends xPDOVehicle {
    var $class = 'xPDOObjectVehicle';
    
    /**
     * Retrieve an xPDOObject instance represented in this vehicle.
     *
     * This method returns the main object contained in the payload, but you can optionally specify
     * a related_objects node within the payload to retrieve a specific dependent object.
     */
    function get(& $transport, $options = array (), $element = null) {
        $object = null;
        $element = parent :: get($transport, $options, $element);
        if (isset ($element['class']) && isset ($element['object'])) {
            $vClass = $element['class'];
            if (!empty ($element['package'])) {
                $pkgPrefix = $element['package'];
                $pkgKeys = array_keys($transport->xpdo->packages);
                if ($pkgFound = in_array($pkgPrefix, $pkgKeys)) {
                    $pkgPrefix = '';
                }
                elseif ($pos = strpos($pkgPrefix, '.')) {
                    $prefixParts = explode('.', $pkgPrefix);
                    $prefix = '';
                    foreach ($prefixParts as $prefixPart) {
                        $prefix .= $prefixPart;
                        $pkgPrefix = substr($pkgPrefix, $pos +1);
                        if ($pkgFound = in_array($prefix, $pkgKeys))
                            break;
                        $prefix .= '.';
                        $pos = strpos($pkgPrefix, '.');
                    }
                    if (!$pkgFound)
                        $pkgPrefix = $element['package'];
                }
                $vClass = (!empty ($pkgPrefix) ? $pkgPrefix . '.' : '') . $vClass;
            }
            $object = $transport->xpdo->newObject($vClass);
            if (is_object($object) && is_a($object, 'xPDOObject')) {
                $options = array_merge($options, $element);
                $setKeys = false;
                if (isset ($options[XPDO_TRANSPORT_PRESERVE_KEYS])) {
                    $setKeys = (boolean) $options[XPDO_TRANSPORT_PRESERVE_KEYS];
                }
                $object->fromJSON($element['object'], '', $setKeys, true);
            }
        }
        return $object;
    }

    /**
     * Install the xPDOObjects represented by vehicle into the transport host.
     */
    function install(& $transport, $options) {
        $parentObj = null;
        $parentMeta = null;
        $installed = $this->_installObject($transport, $options, $this->payload, $parentObj, $parentMeta);
        return $installed;
    }

    /**
     * Install a single xPDOObject from the vehicle payload.
     * 
     * @param xPDOTransport $transport The host xPDOTransport instance.
     * @param array $options Any optional attributes to apply to the installation.
     * @param array $element A node of the payload representing the object to install.
     * @param xPDOObject &$parentObject A reference to the object serving as a parent to the one
     * being installed.
     * @param array $fkMeta The foreign key relationship data that defines the relationship with the
     * parentObject.
     */
    function _installObject(& $transport, $options, $element, & $parentObject, $fkMeta) {
        $saved = false;
        $preserveKeys = false;
        $preExistingMode = XPDO_TRANSPORT_PRESERVE_PREEXISTING;
        $upgrade = false;
        $exists = false;
        $object = $this->get($transport, $options, $element);
        if (is_object($object) && is_a($object, 'xPDOObject')) {
            $vOptions = array_merge($options, $element);
            $vClass = $vOptions['class'];
            if ($transport->xpdo->getDebug() === true)
                $transport->xpdo->log(XPDO_LOG_LEVEL_DEBUG, "Installing Vehicle: " . print_r($vOptions, true));
            if ($parentObject !== null && $fkMeta !== null) {
                if ($fkMeta['owner'] == 'local') {
                    if ($object->get($fkMeta['foreign']) !== $parentObject->get($fkMeta['local'])) {
                        $object->set($fkMeta['foreign'], $parentObject->get($fkMeta['local']));
                    }
                }
            }
            if ($this->validate($transport, $object, $vOptions)) {
                $preserveKeys = !empty ($vOptions[XPDO_TRANSPORT_PRESERVE_KEYS]);
                $upgrade = !empty ($vOptions[XPDO_TRANSPORT_UPDATE_OBJECT]);
                if (!empty ($vOptions[XPDO_TRANSPORT_UNIQUE_KEY])) {
                    $uniqueKey = $object->get($vOptions[XPDO_TRANSPORT_UNIQUE_KEY]);
                    if (is_array($uniqueKey)) {
                        $criteria = array_combine($vOptions[XPDO_TRANSPORT_UNIQUE_KEY], $uniqueKey);
                    } else {
                        $criteria = array (
                            $vOptions[XPDO_TRANSPORT_UNIQUE_KEY] => $uniqueKey
                        );
                    }
                }
                elseif (isset ($vOptions['key_expr']) && isset ($vOptions['key_format'])) {
                    //TODO: implement ability to generate new keys
                } else {
                    $criteria = $vOptions[XPDO_TRANSPORT_NATIVE_KEY];
                }
                if (!empty ($vOptions[XPDO_TRANSPORT_PREEXISTING_MODE])) {
                    $preExistingMode = intval($vOptions[XPDO_TRANSPORT_PREEXISTING_MODE]);
                }
                if ($obj = $transport->xpdo->getObject($vClass, $criteria)) {
                    $exists = true;
                    if ($preExistingMode !== 1) {
                        $transport->_preserved[$vOptions['guid']] = array (
                            'criteria' => $criteria,
                            'object' => $obj->toArray('', true)
                        );
                    }
                    if ($upgrade) {
                        $obj->fromArray($object->toArray('', true), '', false, true);
                        $object = $obj;
                    }
                }
                elseif ($transport->xpdo->getDebug() === true) {
                    $transport->xpdo->log(XPDO_LOG_LEVEL_DEBUG, "Object for class {$vClass} not found using criteria " . print_r($criteria, true));
                }
                if (!$exists || ($exists && $upgrade)) {
                    $saved = $object->save();
                    if (!$saved) {
                        $transport->xpdo->log(XPDO_LOG_LEVEL_ERROR, 'Error saving vehicle object: ' . print_r($vOptions, true));
                    } else {
                        if ($parentObject !== null && $fkMeta !== null) {
                            if ($fkMeta['owner'] == 'foreign') {
                                if ($object->get($fkMeta['foreign']) !== $parentObject->get($fkMeta['local'])) {
                                    $parentObject->set($fkMeta['local'], $object->get($fkMeta['foreign']));
                                    if ($parentObject->save()) {
                                        $transport->xpdo->log(XPDO_LOG_LEVEL_ERROR, "Error saving changes to parent object fk field {$fkMeta['local']}");
                                    }
                                }
                            }
                        }
                    }
                } else {
                    $transport->xpdo->log(XPDO_LOG_LEVEL_INFO, 'Skipping vehicle object (data object exists and cannot be upgraded): ' . print_r($vOptions, true));
                }
                if (($saved || $exists) && !$this->_installRelated($transport, $object, $element, $options)) {
                    $transport->xpdo->log(XPDO_LOG_LEVEL_ERROR, 'Could not install related objects for vehicle: ' . print_r($vOptions, true));
                }
                if ($parentObject === null && !$this->resolve($transport, $object, $vOptions)) {
                    $transport->xpdo->log(XPDO_LOG_LEVEL_ERROR, 'Could not resolve vehicle: ' . print_r($vOptions, true));
                }
            } else {
                $transport->xpdo->log(XPDO_LOG_LEVEL_ERROR, 'Could not validate vehicle object: ' . print_r($vOptions, true));
            }
        } else {
            $transport->xpdo->log(XPDO_LOG_LEVEL_ERROR, 'Could not load vehicle!');
        }
        return ($saved || ($exists && !$upgrade));
    }

    /**
     * Installs a related object from the vehicle.
     */
    function _installRelated(& $transport, & $parent, $element, $options) {
        $installed = true;
        if (is_object($parent) && isset ($element[XPDO_TRANSPORT_RELATED_OBJECTS])) {
            $installed = false;
            foreach ($element[XPDO_TRANSPORT_RELATED_OBJECTS] as $rAlias => $rVehicles) {
                $parentClass = $parent->_class;
                $rMeta = $transport->xpdo->getFKDefinition($parentClass, $rAlias);
                if ($rMeta) {
                    foreach ($rVehicles as $rKey => $rVehicle) {
                        $installed = $this->_installObject($transport, $options, $rVehicle, $parent, $rMeta);
                    }
                }
            }
        }
        return $installed;
    }

    /**
     * Uninstalls vehicle artifacts from the transport host.
     */
    function uninstall(& $transport, $vOptions) {
        $uninstalled = false;
        $removed = false;
        $preExistingMode = XPDO_TRANSPORT_PRESERVE_PREEXISTING;
        $object = $this->get($transport, $vOptions);
        if (is_object($object) && is_a($object, 'xPDOObject')) {
            $vOptions = array_merge($vOptions, $this->payload);
            $vClass = $vOptions['class'];
            if ($this->validate($transport, $object, $vOptions)) {
                $uninstalled = true;
                $preserveKeys = !empty ($vOptions[XPDO_TRANSPORT_PRESERVE_KEYS]);
                if (!empty ($vOptions[XPDO_TRANSPORT_UNIQUE_KEY])) {
                    $uniqueKey = $object->get($vOptions[XPDO_TRANSPORT_UNIQUE_KEY]);
                    if (is_array($uniqueKey)) {
                        $criteria = array_combine($vOptions[XPDO_TRANSPORT_UNIQUE_KEY], $uniqueKey);
                    } else {
                        $criteria = array (
                            $vOptions[XPDO_TRANSPORT_UNIQUE_KEY] => $uniqueKey
                        );
                    }
                } else {
                    $criteria = $vOptions[XPDO_TRANSPORT_NATIVE_KEY];
                }
                if (!empty ($vOptions[XPDO_TRANSPORT_PREEXISTING_MODE])) {
                    $preExistingMode = intval($vOptions[XPDO_TRANSPORT_PREEXISTING_MODE]);
                }
                if ($obj = $transport->xpdo->getObject($vClass, $criteria)) {
                    $exists = true;
                    $object = $obj;
                }
                if ($exists) {
                    if (!isset ($transport->_preserved[$vOptions['guid']]) || $preExistingMode === XPDO_TRANSPORT_REMOVE_PREEXISTING) {
                        $removed = $object->remove();
                        if (!$removed) {
                            $uninstalled = false;
                            $transport->xpdo->log(XPDO_LOG_LEVEL_ERROR, 'Error removing vehicle object: ' . print_r($vOptions, true));
                        }
                    }
                    if ($preExistingMode === XPDO_TRANSPORT_RESTORE_PREEXISTING) {
                        if (isset ($transport->_preserved[$vOptions['guid']])) {
                            $preserved = $transport->_preserved[$vOptions['guid']]['object'];
                            $object->fromArray($preserved, '', true, true);
                            $restored = $object->save();
                            if (!$restored) {
                                $transport->xpdo->log(XPDO_LOG_LEVEL_ERROR, 'Error restoring preserved object: ' . print_r($transport->_preserved[$vOptions['guid']], true));
                            }
                        }
                    }
                } else {
                    $transport->xpdo->log(XPDO_LOG_LEVEL_WARN, 'Skipping ' . $vClass . ' object (data object does not exist and cannot be removed): ' . print_r($criteria, true));
                }
                if (!$this->resolve($transport, $object, $vOptions)) {
                    $transport->xpdo->log(XPDO_LOG_LEVEL_ERROR, 'Could not resolve vehicle: ' . print_r($vOptions, true));
                }
            } else {
                $transport->xpdo->log(XPDO_LOG_LEVEL_ERROR, 'Could not validate vehicle object: ' . print_r($vOptions, true));
            }
        } else {
            $transport->xpdo->log(XPDO_LOG_LEVEL_ERROR, 'Problem instantiating object from vehicle: ' . print_r($vOptions, true));
        }
        return $uninstalled;
    }

    /**
     * Put an xPDOObject representation into a transport package.
     * 
     * This implementation supports the inclusion of related objects. Simply instantiate the related
     * objects that you want to include in the vehicle on the main object, and set
     * XPDO_TRANSPORT_RELATED_OBJECTS => true in your attributes.
     */
    function put(& $transport, & $object, $attributes = array ()) {
        parent :: put($transport, $object, $attributes);
        if (is_object($object)) {
            if (!isset ($this->payload['package'])) {
                if (is_a($object, 'xPDOObject')) {
                    $packageName = $object->_package;
                } else {
                    $packageName = '';
                }
                $this->payload['package'] = $packageName;
            }
            if (is_a($object, 'xPDOObject')) {
                $nativeKey = $object->getPrimaryKey();
                $this->payload['object'] = $object->toJSON('', true);
                $this->payload['native_key'] = $nativeKey;
                $this->payload['signature'] = md5($this->payload['class'] . '_' . $this->payload['guid']);
                if (isset ($this->payload[XPDO_TRANSPORT_RELATED_OBJECTS]) && !empty ($this->payload[XPDO_TRANSPORT_RELATED_OBJECTS])) {
                    $relatedObjects = array ();
                    foreach ($object->_relatedObjects as $rAlias => $related) {
                        if (is_array($related)) {
                            foreach ($related as $rKey => $rObj) {
                                if (!isset ($relatedObjects[$rAlias]))
                                    $relatedObjects[$rAlias] = array ();
                                $guid = md5(uniqid(rand(), true));
                                $relatedObjects[$rAlias][$guid] = array ();
                                $this->_putRelated($transport, $rAlias, $rObj, $relatedObjects[$rAlias][$guid]);
                            }
                        }
                        elseif (is_object($related)) {
                            if (!isset ($relatedObjects[$rAlias]))
                                $relatedObjects[$rAlias] = array ();
                            $guid = md5(uniqid(rand(), true));
                            $relatedObjects[$rAlias][$guid] = array ();
                            $this->_putRelated($transport, $rAlias, $related, $relatedObjects[$rAlias][$guid]);
                        }
                    }
                    if (!empty ($relatedObjects))
                        $this->payload['related_objects'] = $relatedObjects;
                }
            }
            elseif (is_object($object)) {
                $this->payload['object'] = $transport->xpdo->toJSON(get_object_vars($object));
                $this->payload['native_key'] = $this->payload['guid'];
                $this->payload['signature'] = md5($this->payload['class'] . '_' . $this->payload['guid']);
            }
        }
    }

    /**
     * Recursively put related objects into the vehicle.
     * 
     * @access protected
     * @param xPDOTransport $transport The host xPDOTransport instance.
     * @param string $alias The alias representing the relation to the parent object.
     * @param xPDOObject &$object A reference to the dependent object being added into the vehicle.
     * @param array $payloadElement An element of the payload to place the dependent object in.
     */
    function _putRelated(& $transport, $alias, & $object, & $payloadElement) {
        if (is_array($payloadElement)) {
            if (is_object($object) && is_a($object, 'xPDOObject')) {
                if (isset ($this->payload['related_object_attributes'][$alias]) && is_array($this->payload['related_object_attributes'][$alias])) {
                    $payloadElement = array_merge($payloadElement, $this->payload['related_object_attributes'][$alias]);
                }
                elseif (isset ($this->payload['related_object_attributes'][$object->_class]) && is_array($this->payload['related_object_attributes'][$object->_class])) {
                    $payloadElement = array_merge($payloadElement, $this->payload['related_object_attributes'][$object->_class]);
                }
                $payloadElement['class'] = $object->_class;
                $nativeKey = $object->getPrimaryKey();
                $payloadElement['object'] = $object->toJSON('', true);
                $payloadElement['guid'] = md5(uniqid(rand(), true));
                $payloadElement['native_key'] = $nativeKey;
                $payloadElement['signature'] = md5($object->_class . '_' . $payloadElement['guid']);
                $relatedObjects = array ();
                foreach ($object->_relatedObjects as $rAlias => $related) {
                    if (is_array($related)) {
                        foreach ($related as $rKey => $rObj) {
                            if (!isset ($relatedObjects[$rAlias]))
                                $relatedObjects[$rAlias] = array ();
                            $guid = md5(uniqid(rand(), true));
                            $relatedObjects[$rAlias][$guid] = array ();
                            $this->putRelated($transport, $rAlias, $rObj, $relatedObjects[$rAlias][$guid]);
                        }
                    }
                    elseif (is_object($related)) {
                        if (!isset ($relatedObjects[$rAlias]))
                            $relatedObjects[$rAlias] = array ();
                        $guid = md5(uniqid(rand(), true));
                        $relatedObjects[$rAlias][$guid] = array ();
                        $this->putRelated($transport, $rAlias, $related, $relatedObjects[$rAlias][$guid]);
                    }
                }
                if (!empty ($relatedObjects))
                    $payloadElement['related_objects'] = $relatedObjects;
            }
        }
    }
}