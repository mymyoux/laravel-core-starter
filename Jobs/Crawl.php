<?php

namespace Core\Jobs;

use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Core\Traits\Job;
use Core\Queue\JobHandler;
use App;
use Notification;
use Logger;
use Auth;
use Illuminate\Foundation\Application;
use Core\Model\Crawl as CrawlModel;
use Api;

class Crawl extends JobHandler
{
    const name = 'crawl';

    public $queue = self::name;
    protected $html;
    protected $crawl;

    /**
     * Execute the job.
     *
     * @return void
     */
    protected function unserializeData($data)
    {
        $this->data = $data;
    }

    public function handle()
    {
    	$uuid = gethostname().":".get_current_user();
        $uuid = substr($uuid, 0, 64);
        $this->crawl = CrawlModel::find($this->data->id_crawl);
        if(!isset($this->crawl))
        {
            throw new \Exception("Crawl['" . $jobdata->id_crawl . "'] doesn't exists");
            return NULL;
        }

        $attempt = CrawlModel::parse($this->crawl, $uuid);
        $this->crawl->id_crawl_attempt = $attempt->id_crawl_attempt;

        if (isset($jobdata->state) && $jobdata->state == "failed")
        {
           	$this->initFailed();
        }
        else
        {
        	$this->initParse();
        }
    }

    public function initParse()
    {
        $this->html = \pQuery::parseStr($this->crawl->value);

    	return $this->parse();
    }
  //   public function failed()
  //   {
  //       return $this->failedAction();
  //   }
  //   public function needsLogin()
  //   {
		// $next = $this->params()->fromRoute("next");
		// $this->api->crawl->updateparse(NULL, 'POST', array("id_crawl_attempt"=>$next["id_crawl_attempt"], "state"=>CrawlTable::STATE_CRAWL_NEEDS_LOGIN,"success"=>false));
  //   }
    public function success($data)
    {
    	$params =  [
    		"id_crawl_attempt"=>$this->crawl->id_crawl_attempt,
    		"state"=>CrawlModel::STATE_PARSED,
    		"success"=>true,
    		"value"=>json_encode($data)
		];

    	$result = Api::post('crawl/updateparse')->params( $params )->response();

    	return $result;
    }

    public function failed()
    {
      $params =  [
        "id_crawl_attempt"=>$this->crawl->id_crawl_attempt,
        "state"=>CrawlModel::STATE_PARSING_FAILED,
        "success"=>false,
    ];

      $result = Api::post('crawl/updateparse')->params( $params )->response();

      return $result;
    }
  //   public function deleted($data)
  //   {
  //           $next = $this->params()->fromRoute("next");
  //       $this->api->crawl->updateparse(NULL, 'POST', array("id_crawl_attempt"=>$next["id_crawl_attempt"], "state"=>'deleted',"success"=>true,"value"=>json_encode($data)));

  //   }
  //   public function failed()
  //   {
		// $next = $this->params()->fromRoute("next");
		// $this->api->crawl->updateparse(NULL, 'POST', array("id_crawl_attempt"=>$next["id_crawl_attempt"], "state"=>CrawlTable::STATE_PARSING_FAILED,"success"=>false));
  //   }
  //   public function recrawl()
  //   {
  //   	$next = $this->params()->fromRoute("next");
		// $this->api->crawl->updateparse(NULL, 'POST', array("id_crawl_attempt"=>$next["id_crawl_attempt"], "state"=>CrawlTable::STATE_CREATED,"success"=>false));
  //   }

}
