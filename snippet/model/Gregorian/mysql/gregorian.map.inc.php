<?php
$xpdo_meta_map['Gregorian']= array (
  'package' => 'Gregorian',
  'table' => 'calendar',
  'fields' => 
  array (
    'name' => NULL,
    'description' => NULL,
  ),
  'fieldMeta' => 
  array (
    'name' => 
    array (
      'dbtype' => 'varchar',
      'precision' => '255',
      'phptype' => 'string',
      'null' => false,
      'index' => 'unique',
    ),
    'description' => 
    array (
      'dbtype' => 'mediumtext',
      'phptype' => 'string',
    ),
  ),
  'composites' => 
  array (
    'Events' => 
    array (
      'class' => 'GregorianEvent',
      'local' => 'id',
      'foreign' => 'calendar',
      'owner' => 'local',
      'cardinality' => 'many',
    ),
  ),
);
if (XPDO_PHP4_MODE) $xpdo_meta_map['Gregorian']['composites']= array_merge($xpdo_meta_map['Gregorian']['composites'], array_change_key_case($xpdo_meta_map['Gregorian']['composites']));
$xpdo_meta_map['gregorian']= & $xpdo_meta_map['Gregorian'];
