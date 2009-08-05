<?php
$xpdo_meta_map['GregorianEventTag']= array (
  'package' => 'Gregorian',
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
      'class' => 'GregorianEvent',
      'local' => 'event',
      'foreign' => 'id',
      'owner' => 'foreign',
      'cardinality' => 'one',
    ),
    'Tag' => 
    array (
      'class' => 'GregorianTag',
      'local' => 'tag',
      'foreign' => 'id',
      'owner' => 'foreign',
      'cardinality' => 'one',
    ),
  ),
);
if (XPDO_PHP4_MODE) $xpdo_meta_map['GregorianEventTag']['aggregates']= array_merge($xpdo_meta_map['GregorianEventTag']['aggregates'], array_change_key_case($xpdo_meta_map['GregorianEventTag']['aggregates']));
$xpdo_meta_map['gregorianeventtag']= & $xpdo_meta_map['GregorianEventTag'];
