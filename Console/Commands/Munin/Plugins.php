<?php

namespace Core\Console\Commands\Munin;

use Illuminate\Console\Command as BaseCommand;
use DB;
use Artisan;
use Illuminate\Support\Facades\Storage;
use File;
use Logger;
use Core\Util\Command;


class Plugins extends BaseCommand
{
    protected $signature = 'munin:plugins';
    protected $description = 'Munin stats';

    public function handle()
    {
        $signature = $this->signature;
        // get list of munin cron

        Artisan::call('list');
        $list = Artisan::output();

        $list = explode(PHP_EOL, $list);
        $list = array_map(function($item){
            $item = trim($item);
            return mb_substr($item, 0, mb_strpos($item, ' '));
        }, $list);
        $list = array_filter($list, function($item) use ($signature){
            return preg_match('/^munin:/', $item) && $item != $signature;
        });

        $root  		= base_path() . '/munin/';
        $files 		= File::allFiles($root);

        foreach ($files as $file)
        {
            $munin_file = config('munin.plugins') . $file->getRelativePathname();
            
            if (File::exists($munin_file))
            {
                Logger::error('delete ' . $munin_file );
                File::delete( $munin_file );
            }

            $path = $root . $file->getRelativePathname();
            Logger::error('delete ' . $path );
            File::delete( $path );
        }

        foreach ($list as $command)
        {
            Logger::info('create ' . $command );

            $file = '#!/bin/sh' . PHP_EOL;
            $file .= 'php ' . base_path() . '/artisan ' . $command . ' $1' . PHP_EOL;

            $command = str_replace('munin:', '', $command);
            $command = slug($command, [':'], '_');

            file_put_contents(base_path() . '/munin/' . $command, $file);
            Command::executeRaw('ln -s ' . base_path() . '/munin/' . $command . ' ' . config('munin.plugins'));
        }
    }
}
