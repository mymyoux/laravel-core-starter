<?php

namespace Core\Http\Controllers;

use Illuminate\Http\Request;
use App\User;
use Auth;
use Api;
use Core\Exception\ApiException;
use Core\Api\Annotations as ghost;
use App\Model\Connector;


/**
 * @ghost\Role("visitor")
 */
class ConnectorController extends Controller
{
    /**
     * @ghost\Param(name="is_primary",requirements="boolean",required=false,default=true)
     * @ghost\Cache(name="connectors-list")
     */
    public function all(Request $request)
    {
        $is_primary = $request->input('is_primary');

        $data = Connector::where('is_primary', '=', $is_primary)->get();

        return $data;
    }
}