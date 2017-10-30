<?php

namespace Core\Services;

use Symfony\Component\Console\Formatter\OutputFormatterStyle;
use App;
use Illuminate\Support\Debug\Dumper;
use Auth;

class Logger
{
	CONST LOG_CRITICAL  = 6;
    CONST LOG_ERROR     = 5;
    CONST LOG_BG_DEBUG  = 4;
    CONST LOG_WARN      = 3;
    CONST LOG_DEBUG     = 2;
    CONST LOG_INFO      = 1;
    CONST LOG_NONE      = 0;

	private $debug          = true;
    private $metrics        = [];
    private $critical       = null;
    private $display_time   = true;
    private $config_query   = null;
    private $output         = null;
    private $outputs         = null;
    public $timestamp = NULL;

    public function __construct()
    {
        $this->metrics = [];

        if(App::runningInConsole())
        {
            $input  = new \Symfony\Component\Console\Input\ArgvInput();
            $output = new \Symfony\Component\Console\Output\ConsoleOutput();

            $this->output = new \Illuminate\Console\OutputStyle($input, $output);
        }
        $this->outputs = [];
        $this->timestamp  = round(constant("LARAVEL_START")*1000);
    }

    public function startTime()
    {
        $this->timestamp = $this->getTime();
    }
    protected function getTime()
    {
        return round(microtime(True)*1000);
    }
    protected function getCurrentTime()
    {
        return $this->getTime()-$this->timestamp;
    }
    public function setDisplayTime( $boolean )
    {
        $this->display_time = $boolean;
    }

    public function logApiCall( $message = null )
    {
        $this->logMetric('api_call', 1);
        if (null !== $message)
            $this->log($message, $type);
    }

    public function logApiBatch( $message = null )
    {
        $this->logMetric('api_batch', 1);
        if (null !== $message)
            $this->log($message, $type);
    }

    public function logApiCallError( $message = null )
    {
        $this->logMetric('api_call_error', 1);
        if (null !== $message)
            $this->log($message, $type);
    }

    public function logMetric( $stat_name, $value )
    {
        if (false === isset($this->metrics[ $stat_name ]))
            $this->metrics[ $stat_name ] = 0;

        $this->metrics[ $stat_name ] += $value;
    }

    public function getCriticalMessage()
    {
        return $this->critical;
    }

    public function getMetric( $metric_name )
    {
        return isset($this->metrics[ $metric_name ]) ? $this->metrics[ $metric_name ] : 0;
    }

    public function getMetrics()
    {
        return $this->metrics;
    }

    public function setDebug( /*\boolean*/ $debug )
    {
    	$this->debug = $debug;
    }

    public function warn( $message )
    {
        if (true === App::runningInConsole())
        {
             if (! $this->output->getFormatter()->hasStyle('warning')) {
                $style = new OutputFormatterStyle('yellow');

                $this->output->getFormatter()->setStyle('warning', $style);
            }
        }

        $this->logMetric('warn', 1);
    	$this->log($message, self::LOG_WARN);
    }

    public function error( $message )
    {
        $this->logMetric('error', 1);
    	$this->log($message, self::LOG_ERROR);
    }

    public function critical( $message )
    {
        $this->critical = $message;
        $this->logMetric('critical', 1);
        $this->log($message, self::LOG_CRITICAL);
    }
    public function fatal($message)
    {
        $this->critical($message);
        exit();
    }

    public function normal( $message )
    {
    	$this->log($message, self::LOG_NONE);
    }

    public function info( $message )
    {
    	$this->log($message, self::LOG_INFO);
    }

    public function color( $message, $style, $rc = true )
    {
        if (false === $this->debug && $type < self::LOG_ERROR) return;

        $begin = $end = '';

        if (true === $this->display_time)
            $begin = '[' . date('Y-m-d H:i:s') . '] ' . $begin;

        if (true === App::runningInConsole())
        {
            if (true === App::runningInCron())
            {
                echo $begin . $message . $end . PHP_EOL;
            }
            else
            {
                $message = $style ? "<$style>$begin$message$end</$style>" : ($begin . $message . $end);
                $this->output->writeln($message, null);
            }
        }
    }

    public function debug( $message, $bg = false )
    {
    	$this->log($message, (false === $bg ? self::LOG_DEBUG : self::LOG_BG_DEBUG));
    }

    private function log( $msg, $type = self::LOG_NONE )
    {
        $message = $msg;
        if (false === $this->debug && $type < self::LOG_ERROR) return;
        $begin = $end = '';
        $style = null;

        switch ( $type )
        {
            case self::LOG_CRITICAL :
                $style = 'error';
                // $color 	= Color::RED;
                $begin 	= '/!\\ ';
            break;
            case self::LOG_BG_DEBUG :
                $style = 'error';
                // $color = Color::BLUE;
            break;
            case self::LOG_ERROR :
                $style = 'error';
                // $color = Color::LIGHT_RED;
            break;
            case self::LOG_WARN :
                $style = 'warning';
                // $color = Color::YELLOW;
            break;
            case self::LOG_INFO :
                $style = 'info';
                // $color = Color::GREEN;
            break;
            case self::LOG_DEBUG :
                $style = 'question';
                // $color = Color::LIGHT_BLUE;
            break;
            default:
            	// $color = Color::NORMAL;
            break;
        }

        if ($begin !== $end)
        {
            if (self::LOG_CRITICAL === $type)
                $end .= " /!\ ";
        }

        if (true === $this->display_time)
            $begin = '[' . date('Y-m-d H:i:s') . '] ' . $begin;
        if (true === App::runningInConsole())
        {
            if (App::runningInCron())
            {
                echo $begin;
                if(is_object($message) || is_array($message))
                {
                     (new Dumper)->dump($message);
                }else
                {
                    echo $message;
                }

                 echo $end . PHP_EOL;
            }
            else
            {
                if(is_object($message) || is_array($message))
                {
                    echo $begin;
                     (new Dumper)->dump($message);
                     echo $end;
                }else
                {
                    $message = "$begin$message$end";
                    if($style)
                    {
                        $message = "<$style>$message</$style>";
                    }
                     $this->output->writeln($message, null);
                }
            }
            $this->outputs[] = $type.": ".$msg;
        }else
        {
            if(App::isLocal() ||  Auth::isRealAdmin())
            {
                
                $this->outputs[] = (isset($this->timestamp)?'['.$this->getCurrentTime().']':"").$style  .": ".$msg;
            }

        }
    }
    public function getOutput()
    {
        return $this->outputs;
    }
}
