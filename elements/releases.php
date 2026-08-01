<?php return array (
  'title' => 'Releases',
  'model' =>
  array (
    'title' =>
    array (
      'type' => 'string',
      'maxlength' => 80,
      'required' => true,
    ),
    'art' =>
    array (
      'type' => 'image',
      'width' => 800,
      'height' => 800,
    ),
    'status' =>
    array (
      'type' => 'string',
      'options' =>
      array (
        0 => 'aktuell',
        1 => 'archiv',
      ),
      'required' => true,
    ),
    'released' =>
    array (
      'type' => 'date',
    ),
    'spotify' =>
    array (
      'type' => 'string',
      'maxlength' => 300,
    ),
    'apple' =>
    array (
      'type' => 'string',
      'maxlength' => 300,
    ),
    'deezer' =>
    array (
      'type' => 'string',
      'maxlength' => 300,
    ),
    'claim' =>
    array (
      'type' => 'string',
      'locale' => true,
      'maxlength' => 60,
    ),
  ),
  '*' =>
  array (
    '*' =>
    array (
      'status' => 'archiv',
    ),
    'wecker' =>
    array (
      'title' => 'Wecker',
      'status' => 'aktuell',
      'released' => '',
      'art' => '.demo/kp-wecker-placeholder.svg',
      'spotify' => '',
      'apple' => '',
      'deezer' => '',
    ),
  ),
  'de_DE' =>
  array (
    'wecker' =>
    array (
      'claim' => 'Jetzt streamen',
    ),
  ),
  'en_US' =>
  array (
    'wecker' =>
    array (
      'claim' => 'Stream it now',
    ),
  ),
);
