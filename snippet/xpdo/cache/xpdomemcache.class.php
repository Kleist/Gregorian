<?php
/*
 * Copyright 2006, 2007, 2008, 2009 by Jason Coward <xpdo@opengeek.com>
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
 * Provides a memcache-powered xPDOCache implementation.
 *
 * This requires the memcache extension for PHP.
 *
 * @package xpdo
 * @subpackage cache
 */
class xPDOMemCache extends xPDOCache {
    var $key = '';
    var $memcache = null;

    function xPDOMemCache(& $xpdo, $options = array()) {
        $this->__construct($xpdo);
    }
    function __construct(& $xpdo, $options = array()) {
        parent :: __construct($xpdo, $options);
        if (class_exists('Memcache')) {
            $this->memcache= new Memcache();
            if ($this->memcache) {
                $servers = explode(',', $this->getOption($this->key . '_memcached_server', $options, $this->getOption('memcached_server', $options, 'localhost:11211')));
                foreach ($servers as $server) {
                    $server = explode(':', $server);
                    $this->memcache->addServer($server[0], (integer) $server[1]);
                }
                $compressThreshold = $this->getOption($this->key . '_memcached_compress_threshold', $options, $this->getOption('memcached_compress_threshold', array(), '20000:0.2'));
                if (!empty($compressThreshold)) {
                    $threshold = explode(':', $compressThreshold);
                    if (count($threshold) == 2) {
                        $minValue = (integer) $threshold[0];
                        $minSaving = (float) $threshold[1];
                        if ($minSaving >= 0 && $minSaving <= 1) {
                            $this->memcache->setCompressThreshold($minValue, $minSaving);
                        }
                    }
                }
                $this->initialized = true;
            } else {
                $this->memcache = null;
                $this->xpdo->log(XPDO_LOG_LEVEL_ERROR, "xPDOMemCache[{$this->key}]: Error creating memcache provider for server(s): " . $this->getOption($this->key . '_memcached_server', $options, $this->getOption('memcached_server', $options, 'localhost:11211')));
            }
        } else {
            $this->xpdo->log(XPDO_LOG_LEVEL_ERROR, "xPDOMemCache[{$this->key}]: Error creating memcache provider; xPDOMemCache requires the PHP memcache extension.");
        }
    }

    function add($key, $var, $expire= 0, $options= array()) {
        $added= $this->memcache->add(
            $this->getCacheKey($key),
            $var,
            $this->getOption($this->key . XPDO_OPT_CACHE_COMPRESS, $options, $this->getOption(XPDO_OPT_CACHE_COMPRESS, $options, false)),
            $expire
        );
        return $added;
    }

    function set($key, $var, $expire= 0, $options= array()) {
        $set= $this->memcache->set(
            $this->getCacheKey($key),
            $var,
            $this->getOption($this->key . XPDO_OPT_CACHE_COMPRESS, $options, $this->getOption(XPDO_OPT_CACHE_COMPRESS, $options, false)),
            $expire
        );
        return $set;
    }

    function replace($key, $var, $expire= 0, $options= array()) {
        $replaced= $this->memcache->replace(
            $this->getCacheKey($key),
            $var,
            $this->getOption($this->key . XPDO_OPT_CACHE_COMPRESS, $options, $this->getOption(XPDO_OPT_CACHE_COMPRESS, $options, false)),
            $expire
        );
        return $replaced;
    }

    function delete($key, $options= array()) {
        $deleted = false;
        if (!isset($options['multiple_object_delete']) || empty($options['multiple_object_delete'])) {
            $deleted= $this->memcache->delete($this->getCacheKey($key));
        }
        return $deleted;
    }

    function get($key, $options= array()) {
        $value= $this->memcache->get($this->getCacheKey($key));
        return $value;
    }

    function flush($options= array()) {
        return $this->memcache->flush();
    }
}