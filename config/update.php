<?php

return [
  "user"=>env('APACHE_USER'),
  "group"=>env('APACHE_GROUP'),
  "choices"=>
  [
    "pull"=>True,
    "composer"=>True,
    "migrate"=>True,
    "cache"=>True,
    "supervisor"=>True,
    "cron"=>false,
    "doc"=>true,
    "sass"=>true,
    "tsc"=>true
  ],
  "pull"=>
  [
    ".",
    "app_core"
  ],
  "table"=>"update_git",
  "project"=>"laravel",

];
