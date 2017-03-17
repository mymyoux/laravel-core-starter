<?php

namespace Core\Http\Controllers;

use Illuminate\Http\Request;
use App\User;
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
class CrawlController extends Controller
{
	/**
	 * Update a crawl record
     * @ghost\Api
     * @notice To be implemented
	 */
    public function update(Request $request)
    {
     	//TODO
    } 
}
