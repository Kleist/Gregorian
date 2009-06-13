<?php
$xpdo_meta_map['MODxCalendarEventTag']= array (
  'package' => 'MODxCalendar',
  'table' => 'calendar_event_tag',
  'fields' => 
  array (
    'event' => NULL,
    'tag' => NULL,
  ),
  'fieldMeta' => 
  array (
    'event' => 
    array (
      'dbtype' => 'integer',
      'precision' => '10',
      'attributes' => 'unsigned',
      'phptype' => 'integer',
      'index' => 'pk',
    ),
    'tag' => 
    array (
      'dbtype' => 'integer',
      'precision' => '10',
      'attributes' => 'unsigned',
      'phptype' => 'integer',
      'index' => 'pk',
    ),
  ),
  'aggregates' => 
  array (
    'Event' => 
    array (
      'class' => 'MODxCalendarEvent',
      'local' => 'event',
      'foreign' => 'id',
      'owner' => 'foreign',
      'cardinality' => 'one',
    ),
    'Tag' => 
    array (
      'class' => 'MODxCalendarTag',
      'local' => 'tag',
      'foreign' => 'id',
      'owner' => 'foreign',
      'cardinality' => 'one',
    ),
  ),
);
if (XPDO_PHP4_MODE) $xpdo_meta_map['MODxCalendarEventTag']['aggregates']= array_merge($xpdo_meta_map['MODxCalendarEventTag']['aggregates'], array_change_key_case($xpdo_meta_map['MODxCalendarEventTag']['aggregates']));
$xpdo_meta_map['modxcalendareventtag']= & $xpdo_meta_map['MODxCalendarEventTag'];
