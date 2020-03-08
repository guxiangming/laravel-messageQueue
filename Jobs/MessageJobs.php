<?php

namespace App\Jobs;

use App\Exceptions\ValidatorException;
use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;

class MessageJobs implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $data;
    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($data)
    {
        $this->data=$data;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        
        if($this->attempts() > 3){
            //写入异常 $this->data
            echo $this->attempts();
        }else{
            // try{
            //     foreach($this->data as $k=>$v){
            //         request()->offsetSet($k,$v);
            //     }
            //     // dd($this->data);
            //     app('App\Http\Controllers\ResolvingController')->resolving($this->data);
            //     return true;
            // }catch(\Throwable $t){
            //     echo $t->getMessage();
            //     throw new ValidatorException($t->getMessage());
            // } 
        }
       
        try{
            foreach($this->data as $k=>$v){
                request()->offsetSet($k,$v);
            }
            // dd($this->data);
            app('App\Http\Controllers\ResolvingController')->resolving($this->data);
            return true;
        }catch(\Throwable $t){
            echo $t->getMessage();
            throw new ValidatorException($t->getMessage(),$t->getTraceAsString());
        } 
    }

    public function failed()
    {
        \Log::error('队列任务执行失败'."\n".date('Y-m-d H:i:s'));
    }
}
