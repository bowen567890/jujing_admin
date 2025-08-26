<?php

namespace App\GatewayWorker;

use GatewayWorker\Lib\Gateway;
use Illuminate\Support\Facades\Log;

use App\Models\User;
use App\Models\RoomTeam;
use App\Models\RoomTeamOrder;

class Events
{

    public static function onWorkerStart($businessWorker)
    {
        
//         echo "BusinessWorker    Start\n";
    }

    //重启后 $client_id 可能会重复
    public static function onConnect($client_id)
    {
        //此处绑定用户 ID 和 房间ID
        
//         Gateway::sendToClient($client_id, json_encode(['type' => 'init', 'client_id' => $client_id]));
//         Log::channel('gateway')->info('onConnect');
    }

    // 当客户端连接上来时，设置连接的onWebSocketConnect，即在websocket握手时的回调
    // 可以在这里判断连接来源是否合法，不合法就关掉连接
    // $_SERVER['HTTP_ORIGIN']标识来自哪个站点的页面发起的websocket链接
    // onWebSocketConnect 里面$_GET $_SERVER是可用的
    public static function onWebSocketConnect($client_id, $data)
    {
//         Log::channel('gateway')->info('onWebSocketConnect');
        // 可以在这里判断连接来源是否合法，不合法就关掉连接
    }

    public static function onMessage($client_id, $message)
    {
    	//以下为业务逻辑，自行修改
        $response = ['code' => 200, 'msg' => 'success', 'data' => []];
        $message = json_decode($message, true);
//         var_dump($message);
//         Log::channel('gateway')->info('onMessage',$message);
     
        if (!isset($message['mode'])) {
            $response['code'] = 422;
            $response['msg'] = '状态异常';
            $response['data'] = [];
            Gateway::sendToClient($client_id, json_encode($response));
            self::onClose($client_id);
            return false;
        }
        
        
        
        if ($message['mode']=='double') 
        {
            if (!isset($message['uid']) || !isset($message['team_id']) || intval($message['uid'])<0 || intval($message['team_id'])<0) 
            {
                $response['code'] = 422;
                $response['msg'] = '状态异常';
                $response['data'] = [];
                Gateway::sendToClient($client_id, json_encode($response));
                self::onClose($client_id);
                return false;
            }
            $team_id = intval($message['team_id']);
            
            //加入分组
            Gateway::joinGroup($client_id, $message['team_id']);
            
            $RoomTeam = RoomTeam::query()->where('id', $team_id)->first();
            if ($RoomTeam) 
            {
                $list = RoomTeamOrder::query()
                    ->join('users as u', 'room_team_order.user_id', '=', 'u.id')
                    ->where(['team_id'=>$team_id])
                    ->whereIn('room_team_order.status', [0,1,2])
                    ->groupBy('room_team_order.user_id')
                    ->get(['room_team_order.id','room_team_order.user_id','room_team_order.price','u.name','u.headimgurl'])
                    ->toArray();
                if ($list) {
                    foreach ($list as &$val) {
                        $val['headimgurl'] = getImageUrl($val['headimgurl']);
                    }
                }
                
                $data = [
                    'team_id' => $team_id,
                    'state' => $RoomTeam->state,
                    'list' => $list,
                ];
                $response['data'] = $data;
            }
            Gateway::sendToGroup($team_id, json_encode($response));
        } 
        else if ($message['mode']=='fishing') 
        {
            if (!isset($message['uid']) || !isset($message['team_id']) || intval($message['uid'])<0 || intval($message['team_id'])<0)
            {
                $response['code'] = 422;
                $response['msg'] = '状态异常';
                $response['data'] = [];
                Gateway::sendToClient($client_id, json_encode($response));
                self::onClose($client_id);
                return false;
            }
            $team_id = intval($message['team_id']);
            
            $RoomTeam = RoomTeam::query()->where('id', $team_id)->first();
            if ($RoomTeam && $RoomTeam->state==1)
            {
                $groupKey = 'fishing_'.$team_id;
                Gateway::joinGroup($client_id, $groupKey);
//                 $response['data'] = ['groupKey'=>$groupKey,'team_id'=>$team_id];
//                 Gateway::sendToClient($client_id, json_encode($response));
            }
        }
    }

    public static function onClose($client_id)
    {
//         Log::channel('gateway')->info('用户关闭链接：'.$client_id);
        Gateway::closeClient($client_id);
    }
}
