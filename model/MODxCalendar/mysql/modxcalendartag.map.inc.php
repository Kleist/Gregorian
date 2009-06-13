<?php
$xpdo_meta_map['MODxCalendarTag']= array (
  'package' => 'MODxCalendar',
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
      'class' => 'MODxCalendarEventTag',
      'local' => 'id',
      'foreign' => 'tag',
      'owner' => 'foreign',
      'cardinality' => 'many',
    ),
  ),
);
if (XPDO_PHP4_MODE) $xpdo_meta_map['MODxCalendarTag']['composites']= array_merge($xpdo_meta_map['MODxCalendarTag']['composites'], array_change_key_case($xpdo_meta_map['MODxCalendarTag']['composites']));
$xpdo_meta_map['modxcalendartag']= & $xpdo_meta_map['MODxCalendarTag'];
