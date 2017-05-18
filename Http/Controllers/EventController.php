<?php

namespace Core\Http\Controllers;

use Illuminate\Http\Request;
use App\User;
use Core\Model\Event;
use App\Events\CompanySignupEvent;
use Auth;
use Api;
use Core\Exception\ApiException;
use \Core\Api\Paginate;
use Core\Api\Annotations as ghost;
use App;
use Notification;
use Db;
use Sheets;
use Google;
use Job;
use Core\Jobs\Test;
use Logger;
use Apiz;

use Core\Model\Crawl;
use Core\Model\CrawlAttempt;
use Illuminate\Support\Facades\Redis;
class EventController extends Controller
{
    /**
     * @ghost\Api
     *  @ghost\Paginate(allowed="id_event,created_time,updated_time",keys="created_time",directions="-1", limit=2)
     */
    public function list(Request $request, Paginate $paginate)
    {
        //dd(Auth::user());

        CompanySignupEvent::create(["test"=>"oui"], Auth::user(), Auth::user())->save();

        //$events = Event::all();
        //$event = Event::with('owner','external')->find(1);
        $request = Event::with('owner','external');
        $request = $paginate->apply($request, "event");
        $event = $request->get();

        return $event;
    } 
    /**
     * @ghost\Param(name="id_event",required=true,requirements="\d+",type="int")
     * @ghost\Param(name="state",required=false)
     * @ghost\Param(name="result",required=false)
     * @ghost\Param(name="postpone",required=false,requirements="\d+",type="int")
     */
    public function answer(Request $request)
    {
        $event = Event::find($request->input('id_event'));
        if(!isset($event))
        {
            throw new ApiException('bad_event');
        }
        $postpone = $request->input('postpone')?date("Y-m-d H:i:s", time()+$request->input('postpone')):NULL;

        $event->answer($request->input('result'), $request->input('state'),$postpone, Auth::id());
    }

}
