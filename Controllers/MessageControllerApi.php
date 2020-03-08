<?php

namespace App\Http\Controllers;


class MessageControllerApi extends Controller
{
    

    //数据库消息模板解析
    public function templateIndex(){
        $request = \Request::all();
        $validator = \Validator::make($request, [
            'index' => 'required',
            'indexEventId' => 'required',
            'dictionaryId' => 'required',
        ], [
            'index.required'    => '[index]主键不能为空',
            'indexEventId.required'    => '[indexEventId]事件不能为空',
            'dictionaryId.required'    => '[dictionaryId]事件不能为空',
        ]);
        if ($validator->fails()) {
            $errors = $validator->errors()->all()[0];
            return response()->json(['code'=>600,'msg'=>$errors,'data'=>$errors]);
        }

        $event=hlyun_message_index_event::where('ID',$request['indexEventId'])->first();
        
        switch($event['index']){
            case 'SROA':
            case 'OMS':
            default:
                $sql=\DB::connection($event['DataBase'])->table($event['Table']);
            ;
        }   

        //查询连表配置信息
        $joinTables=hlyun_message_index_joinTables::where('EventId',$event['ID'])->first();
        if(!empty($joinTables)&&!empty($joinTables['Info'])){
            foreach($joinTables['Info'] as $v){
                $sql=$sql->leftJoin(...$v);
            }
        }    
        // \DB::connection($event['DataBase'])->enableQueryLog();
        // $res=$sql->first();
        // $res=\DB::connection($event['DataBase'])->getQueryLog();
        // 查询出现映射字段
        
        try{

            

            foreach($request['dictionaryId'] as $ke=>$val){
                if(empty($val))continue;
                 
                $copySql=clone $sql;
                $dictonary=hlyun_message_index_dictionary::whereIn('ID',$val)->get()->toArray();
                // 拼接连表字段
                $selectField=[];
                foreach($dictonary as $v){
                    // as {$v['ID']}
                    $map="{$v['Table']}.{$v['Field']} as {$v['ID']}";
                    array_push($selectField,$map);
                }

                if(!empty($selectField)){
                    $copySql=$copySql->select(...$selectField);
                }
             
                if(is_array($request['index'])&&count($request['index'])>1){
                    $copySql=$copySql->whereIn("{$event['Table']}.{$event['PrimaryKey']}",$request['index']);
                    $data[$ke]=$copySql->get();  
                    $reset=[];
                    foreach(array_column($dictonary,'ID') as $valID){
                        $reset[$valID]=array_unique($data[$ke]->pluck($valID)->toArray());
                    }   
                    $data[$ke]=$reset;
                    unset($reset);
                  

                }else{
                    if(is_array($request['index'])){$request['index']=implode(',',$request['index']);}
                    $copySql=$copySql->where("{$event['Table']}.{$event['PrimaryKey']}",$request['index']);
                    $data[$ke]=$copySql->first();
                }
                unset($copySql);
            }
           
            if(isset($data['receiver'])&&!empty($data['receiver'])){
                $reset=[];
                foreach($data['receiver'] as &$val){
                    //接收者特殊处理
                    if(!is_array($val)){$val=explode(',',$val);}
                    $reset+=hlyun_sso_users::whereIn('ID',$val)->select('ID','Name')->get()->keyBy('ID')->map(function($item){
                        return $item;
                    })->toArray();
                    // continue;
                }
                $data['receiver']=$reset;
                unset($reset);
            }  
          
            //内容渲染按select查询顺序填充
            return ['code'=>0,]+$data;

        }catch(\Throwable $t){
            // dd($t);
            // sed -i "s/web.tars.com/${MachineIp}/g" `grep web.tars.com -rl ./*`
            return ['code'=>600,'data'=>'模板变量解析查询错误','error'=>$t->getTraceAsString(),'message'=>$t->getMessage()];
        }   
       
    }
}
