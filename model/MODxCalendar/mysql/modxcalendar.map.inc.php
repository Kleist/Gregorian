<?php
$xpdo_meta_map['MODxCalendar']= array (
  'package' => 'MODxCalendar',
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
      'class' => 'MODxCalendarEvent',
      'local' => 'id',
      'foreign' => 'calendar',
      'owner' => 'local',
      'cardinality' => 'many',
    ),
  ),
);
if (XPDO_PHP4_MODE) $xpdo_meta_map['MODxCalendar']['composites']= array_merge($xpdo_meta_map['MODxCalendar']['composites'], array_change_key_case($xpdo_meta_map['MODxCalendar']['composites']));
$xpdo_meta_map['modxcalendar']= & $xpdo_meta_map['MODxCalendar'];
