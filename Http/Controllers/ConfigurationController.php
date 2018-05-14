<?php

namespace Core\Http\Controllers;

use Illuminate\Http\Request;
use App\User;
use Auth;
use Api;
use Core\Exception\ApiException;
use Core\Api\Annotations as ghost;
use App\Model\Connector;


class ConfigurationController extends Controller
{
    /**
     * @ghost\Api
     */
    public function get(Request $request)
    {
        if(!config('app.config_file'))
        {
            throw new ApiException('CONFIGURATION_PATH missing from .env');
        }
        if(!file_exists(config('app.config_file')))
        {
            throw new ApiException(config('app.config_file').' not found');
        }
        return json_decode(file_get_contents(config('app.config_file')), True);
    }
    /**
     * @ghost\API
     */
    public function test()
    {

    }
}