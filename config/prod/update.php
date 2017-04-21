<?php

return [

  "user"=>env('APACHE_USER', 'www-data'),
  "group"=>env('APACHE_GROUP', 'www-data'),
  "+choices"=>
  [
    'cron'=>true,
    'doc'=>true, 
    "cache"=>True,
    "supervisor"=>True,
  ],
];
