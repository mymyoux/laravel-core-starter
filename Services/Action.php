<?php

namespace Core\Services;

use Job;
use App\User;
use Logger;
use Auth;

use Core\Model\Action as ActionModel;

class Action
{
    public function add(User $user, $type, $user_action = null, $value = null)
    {
        if ($user->isRealAdmin())
             return null;

        if (isset($user_action))
        {
            if(is_numeric($user_action))
                $id_user_action = (int) $user_action;
            else
                $id_user_action = $user_action->getKey();
        }
        else
        {
            $id_user_action = null;
        }

        $action = new ActionModel;

        $action->id_user        = $user->getKey();
        $action->type           = $type;
        $action->id_user_action = $id_user_action;
        $action->value          = $value;
        if($user->isImpersonated())
        {
           $action->id_real_user = (int) $user->getRealUser()->getKey();
        }


        $action->save();

        // if(isset($id_user_action) && !isset($user_action))
        // {
        //     $user_action = $this->sm->get("UserTable")->getUser($id_user_action);
        // }
        // $this->sm->get("Notifications")->addAction($user, $action, $user_action, $value);
    }

    public function del( $id_action )
    {
        $action = ActionModel::where('id_action', '=', $id_action)->first();

        if (null === $action)
            return false;

        $action->delete();

        return true;
    }
}