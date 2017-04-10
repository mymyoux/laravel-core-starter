<?php

return  [
   'migrations' => 'migrations',
    'phinx'=>
    [
        'paths' =>
        [
          'default'=>'project',
          'project' => 'database/phinx',
          'core'=> 'app_core/config/database/phinx'
        ],
        'templates' =>
        [
          'file' => 'app_core/config/database/template.temp',
        ],
        'migration_table'=>'phinxlog_laravel'
    ],
];