<?php

namespace Core\Console\Commands\Notifications;
use Db;
use Core\Console\Commands\CoreCommand;
use Core\Util\ClassWriter;
use Core\Util\ClassWriter\Body\Table;
use Core\Util\ClassWriter\Body\General;
use Schema;
use ReflectionClass;
use File;
use Logger;
use Core\Util\Command;
use App;
use Route;
use Core\Util\MarkdownWriter;
use Core\Util\Command as ExecCommand;
use Core\Util\ClassHelper;
use Tables\STATS_API_CALL;
use stdClass;
use Api;
use Core\Api\Annotations\Paginate;
use Core\Api\Annotations\Param;
use Core\Api\Annotations\Role;
use Doctrine\Common\Annotations\AnnotationReader;
use Doctrine\Common\Annotations\AnnotationRegistry;
use Core\Util\ModuleHelper;
use Notification;
class Send extends CoreCommand
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'notifications:send {channel=test_yb} {message=test}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Send notification message';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $channel = $this->argument('channel');
        $message = $this->argument('message');

        Notification::sendNotification($channel, $message);
    }
   
}
