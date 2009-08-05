<?php
$xpdo_meta_map['GregorianTag']= array (
  'package' => 'Gregorian',
  'table' => 'calendar_tag',
  'fields' => 
  array (
    'tag' => NULL,
  ),
  'fieldMeta' => 
  array (
    'tag' => 
    array (
      'dbtype' => 'varchar',
      'phptype' => 'string',
      'precision' => '255',
      'index' => 'unique',
      'null' => false,
    ),
  ),
  'composites' => 
  array (
    'Event' => 
    array (
      'class' => 'GregorianEventTag',
      'local' => 'id',
      'foreign' => 'tag',
      'owner' => 'foreign',
      'cardinality' => 'many',
    ),
  ),
);
if (XPDO_PHP4_MODE) $xpdo_meta_map['GregorianTag']['composites']= array_merge($xpdo_meta_map['GregorianTag']['composites'], array_change_key_case($xpdo_meta_map['GregorianTag']['composites']));
$xpdo_meta_map['gregoriantag']= & $xpdo_meta_map['GregorianTag'];
