<?php
$xpdo_meta_map['GregorianEvent']= array (
  'package' => 'Gregorian',
  'table' => 'calendar_event',
  'fields' => 
  array (
    'calendar' => NULL,
    'summary' => NULL,
    'description' => NULL,
    'last_modified' => 'CURRENT_TIMESTAMP',
    'created' => NULL,
    'dtstart' => NULL,
    'dtend' => NULL,
    'location' => NULL,
    'allday' => '0',
  ),
  'fieldMeta' => 
  array (
    'calendar' => 
    array (
      'dbtype' => 'integer',
      'precision' => '10',
      'attributes' => 'unsigned',
      'phptype' => 'integer',
      'index' => 'index',
    ),
    'summary' => 
    array (
      'dbtype' => 'tinytext',
      'phptype' => 'string',
      'index' => 'fulltext',
      'null' => false,
    ),
    'description' => 
    array (
      'dbtype' => 'mediumtext',
      'phptype' => 'string',
      'index' => 'fulltext',
    ),
    'last_modified' => 
    array (
      'dbtype' => 'timestamp',
      'phptype' => 'timestamp',
      'attributes' => 'on update CURRENT_TIMESTAMP',
      'default' => 'CURRENT_TIMESTAMP',
    ),
    'created' => 
    array (
      'dbtype' => 'timestamp',
      'phptype' => 'timestamp',
    ),
    'dtstart' => 
    array (
      'dbtype' => 'datetime',
      'phptype' => 'datetime',
      'index' => 'index',
    ),
    'dtend' => 
    array (
      'dbtype' => 'datetime',
      'phptype' => 'datetime',
      'index' => 'index',
    ),
    'location' => 
    array (
      'dbtype' => 'tinytext',
      'phptype' => 'string',
      'index' => 'fulltext',
    ),
    'allday' => 
    array (
      'dbtype' => 'binary',
      'phptype' => 'boolean',
      'null' => false,
      'default' => '0',
      'index' => 'index',
    ),
  ),
  'aggregates' => 
  array (
    'Calendar' => 
    array (
      'class' => 'Gregorian',
      'local' => 'calendar',
      'foreign' => 'id',
      'owner' => 'foreign',
      'cardinality' => 'one',
    ),
  ),
  'composites' => 
  array (
    'Tags' => 
    array (
      'class' => 'GregorianEventTag',
      'local' => 'id',
      'foreign' => 'event',
      'owner' => 'local',
      'cardinality' => 'many',
    ),
  ),
);
if (XPDO_PHP4_MODE) $xpdo_meta_map['GregorianEvent']['aggregates']= array_merge($xpdo_meta_map['GregorianEvent']['aggregates'], array_change_key_case($xpdo_meta_map['GregorianEvent']['aggregates']));
if (XPDO_PHP4_MODE) $xpdo_meta_map['GregorianEvent']['composites']= array_merge($xpdo_meta_map['GregorianEvent']['composites'], array_change_key_case($xpdo_meta_map['GregorianEvent']['composites']));
$xpdo_meta_map['gregorianevent']= & $xpdo_meta_map['GregorianEvent'];
