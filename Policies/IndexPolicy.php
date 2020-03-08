<?php

namespace App\Policies;

use App\User;
use Illuminate\Auth\Access\HandlesAuthorization;
use App\Model\hlyun_message_index;

class IndexPolicy
{
    use HandlesAuthorization;

    /**
     * Create a new policy instance.
     *
     * @return void
     */
    public function __construct()
    {
 
        //
    }

    public function selfDefine(){

    
        dd('selfDefinePlicy');
    }

    public function update(User $user, hlyun_message_index $index){
        dd('update');
        return 'update';
    }
}
