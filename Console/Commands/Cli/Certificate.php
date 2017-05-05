<?php

namespace Core\Console\Commands\Cli;
use Illuminate\Console\Command;
use Symfony\Component\Console\Input\InputOption;
use App;
use Illuminate\Foundation\Providers\ArtisanServiceProvider;
use Db;
use Core\Model\Error;
use File;
use Logger;
use Illuminate\Console\Application;

class Certificate extends Command
{
    protected $current_directory;
    protected $cache;
    protected $cachefilename;
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'cli:certificate {dns} {--pull=d} {--composer=d} {--cache=d} {--supervisor=d} {--migrate=d} {--cron=d} {--doc=d} {--execute-only}';

    protected $defaultChoices =
    [
        "pull"              => 1,
        "composer"          => 1,
        "migrate"           => 1,
        "cache"             => 1,
        "supervisor"        => 1,
        'cron'              => 0,
        'doc'              => 1
    ];
    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Update project';

    /**
     *
     * Execute the console command.
     *
     * @return mixed
     */

    public function handle()
    {
        $dns = $this->argument('dns');

        $config = ["linux"=> ["apache_path"=> "/etc/apache2","ssl_config"=> "/etc/ssl/openssl.cnf"],
        "mac"=>["apache_path"=> "/usr/local/etc/apache2/2.4/","ssl_config"=> "/System/Library/OpenSSL/openssl.cnf"]];
        if(PHP_OS == 'Linux')
        {
            $config = $config["linux"];
        }else
        {
            $config = $config["mac"];
        }


        $path = $this->ask('Do you want to copy the files to apache ?',   $config["apache_path"]);

        $result = $this->cmd("cd $path && ssh-keygen", ['-f ' . $dns . '.key'], true, [
            'Overwrite (y/n)?'  => 'y'
        ]);

        if(!$result["success"])
        {
            throw new \Exception("Error while creating the key");
        }

        $result = $this->cmd("cd $path && openssl req", ['-new -key ' . $dns . '.key', '-out ' . $dns . '.csr'], true, [
            'Common Name (e.g. server FQDN or YOUR name) []:' => $dns,
            'State or Province Name (full name) [Some-State]:' => '',
            'Locality Name (eg, city) []:' => '',
            'Country Name (2 letter code) [AU]:' => '',
            'Organization Name (eg, company) [Internet Widgits Pty Ltd]:' => '',
            'Organizational Unit Name (eg, section) []:'    => '',
            'Email Address []:' => '',
            'A challenge password []:'  => '',
            'An optional company name []:'  => ''
        ]);

        if(!$result["success"])
        {
            throw new \Exception("Error while creating the key");
        }

        $result = $this->cmd("cd $path && openssl x509", ['-req -days 3650', '-in ' . $dns . '.csr', '-signkey ' . $dns . '.key', '-out ' . $dns . '.crt']);

        if(PHP_OS == 'Linux')
        {
            $sslconf = "/etc/ssl/openssl.cnf";
        }else
        {
            $sslconf = "/System/Library/OpenSSL/openssl.cnf";
        }
        $result = $this->cmd('cd ' . $path . ' && cat '.$config["ssl_config"].' > ' . $path . '/' . $dns . '.cnf && printf \'[SAN]\nsubjectAltName=DNS:' . $dns . '\n\' >> ' . $path . '/' . $dns . '.cnf');

        $result = $this->cmd("cd $path && openssl req", ['-x509 -nodes -new -days 3650', '-subj /CN=' . $dns . ' -reqexts SAN -extensions SAN -config ' . $path . '/' . $dns . '.cnf -sha256', '-key ' . $dns . '.key', '-out ' . $dns . '.crt']);
    }

    protected function cmd($command, $params = NULL, $execute = True, $stdin_autoinput = [])
    {
        if(isset($params))
        {
            $command.= " ".implode(" ", $params);
        }
        $this->info("execute: ".$command);
        // $command.=" 2>&1";
        $output = [];

        $returnValue = NULL;
        if($execute)
        {
            $descriptorspec = array(
               0 => array("pipe", "r"),   // stdin is a pipe that the child will read from
               1 => array("pipe", "w"),   // stdout is a pipe that the child will write to
               2 => array("pipe", "w")    // stderr is a pipe that the child will write to
            );
            $r = '';
            $process = proc_open($command, $descriptorspec, $pipes);

            stream_set_blocking($pipes[1], 0);
            stream_set_blocking($pipes[2], 0);
            stream_set_blocking(STDIN, 0);
            if (is_resource($process)) {

                $status = proc_get_status($process);
                if($status === FALSE) {
                    throw new Exception (sprintf(
                        'Failed to obtain status information '
                    ));
                }
                $pid = $status['pid'];
                $data = null;
                // now, poll for childs termination
                while(true) {
                    // detect if the child has terminated - the php way
                    $status = proc_get_status($process);
                    // check retval
                    if($status === FALSE) {
                        throw new Exception ("Failed to obtain status information for $pid");
                    }
                    if($status['running'] === FALSE) {
                        // $exitcode = $status['exitcode'];
                        // $pid = -1;
                        $returnValue = 0;
                        proc_close($process);
                        // echo "child exited with code: $exitcode\n";
                        return ["output"=>$output, "returnValue"=>$returnValue, "success"=>$returnValue==0];
                        // break;
                    }

                    // read from childs stdout and stderr
                    // avoid *forever* blocking through using a time out (50000usec)
                    foreach(array(1, 2) as $desc) {
                        // check stdout for data
                        $read = array($pipes[$desc]);
                        $write = NULL;
                        $except = NULL;
                        $tv = 0;
                        $utv = 50000;

                        $n = stream_select($read, $write, $except, $tv, $utv);
                        if($n > 0) {
                            do {
                                $data = fread($pipes[$desc], 8092);
                                fwrite(STDOUT, $data);

                                $output[] = $data;
                            } while (strlen($data) > 0);
                        }
                    }

                    if (count($stdin_autoinput) > 0)
                    {
                        foreach ($output as $o)
                        {
                            foreach ($stdin_autoinput as $key => $value)
                            {
                                if (!empty($o) && mb_strpos($o, $key) !== false)
                                {
                                    fwrite(STDOUT, "$value\n");
                                    fwrite($pipes[0], "$value\n");

                                    unset( $stdin_autoinput[$key] );
                                }
                            }
                        }
                    }

                    $read = array(STDIN);
                    $n = stream_select($read, $write, $except, $tv, $utv);
                    if($n > 0) {
                        $input = fread(STDIN, 8092);
                        // inpput to program
                        fwrite($pipes[0], $input);
                    }
                }
                $returnValue = proc_close($process);
            }
        }
        return ["output"=>$output, "returnValue"=>$returnValue, "success"=>$returnValue==0];
    }
}
