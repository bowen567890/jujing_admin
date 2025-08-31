<?php
namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Cache;
use SimpleSoftwareIO\QrCode\Facades\QrCode; 
use Illuminate\Http\Request;
use App\Models\MainCurrency;
use App\Models\User;
use App\Models\OrderLog;
use App\Models\MyRedis;
use Illuminate\Support\Facades\DB;
use App\Models\UserUsdt;
use App\Models\RankConfig;
use App\Models\UserPower;
use App\Models\UserPowerList;
use App\Models\UserAleo;
use App\Models\UserEntt;
use App\Models\UserDino;
use GuzzleHttp\Client;
use Illuminate\Support\Facades\Log;
use App\Models\AssetPackage;
use App\Models\EcoPackage;
use App\Models\UserVoucher;
use App\Models\UserVoucherDino;
use App\Models\AllocationApply;
use App\Models\UserMh;
use App\Models\UserVv;
use App\Models\UserVvQuantify;
use App\Models\UserEnergy;
use App\Models\UserSpacex;
use App\Models\UserDogbee;
use App\Models\ManageRankConfig;
use App\Models\UserPoint;
use App\Models\UserTicket;
use App\Models\TicketConfig;
use App\Models\UserJuj;

class UserController extends Controller
{
    public $host = '';
    
    public function __construct()
    {
        parent::__construct();
        $this->host =  $_SERVER['REQUEST_SCHEME'] . '://' . $_SERVER['HTTP_HOST'];
    }
    
    public function info(Request $request)
    {
        $user = auth()->user();
        
        $data = [];
        $data['id'] = $user->id;
        $data['wallet'] = $user->wallet;
        $data['code'] = $user->code;
        $data['usdt'] = $user->usdt;
        $data['juj'] = $user->usdt;
        $data['nft_rank'] = $user->nft_rank;
        $data['zhi_num'] = $user->zhi_num;
        $data['group_num'] = $user->group_num;
        
        $data['headimgurl'] = getImageUrl($user->headimgurl);
        
        $withdraw_fee_bnb = @bcadd(config('withdraw_fee_bnb'), '0', 6);
        $withdraw_fee_bnb = bccomp($withdraw_fee_bnb, '0', 6)>0 ? $withdraw_fee_bnb : '0.0015';
        $data['withdraw_fee_bnb'] = $withdraw_fee_bnb;
        return responseJson($data);
    }
    
    public function teamList(Request $request)
    {
        $user = auth()->user();
        $in = $request->input();
        
        $pageNum = isset($in['page_num']) && intval($in['page_num'])>0 ? intval($in['page_num']) : 10;
        $page = isset($in['page']) ? intval($in['page']) : 1;
        $page = $page<=0 ? 1 : $page;
        $pageNum = $pageNum>=20 ? $pageNum : $pageNum;
        $offset = ($page-1)*$pageNum;
        
        $user_id = $user->id;
        if($user->path) {
            $path = $user->path."{$user->id}-";
        } else {
            $path = "-{$user->id}-";
        }
        $list = User::query()
            ->where('parent_id', '=', $user_id)
            //             ->where('path', 'like', "{$path}%")
            ->orderBy('id','asc')
            ->offset($offset)
            ->limit($pageNum)
            ->get(['wallet','self_num','self_yeji','created_at'])
            ->toArray();
        if ($list) {
            foreach ($list as &$val) {
                $val['wallet'] =  substr_replace($val['wallet'],'*****', 3, -3);
            }
        }
        
        return responseJson($list);
    }
    
    
    public function usdtLog(Request $request)
    {
        $user = auth()->user();
        $in = $request->input();
        
        $pageNum = isset($in['page_num']) && intval($in['page_num'])>0 ? intval($in['page_num']) : 10;
        $page = isset($in['page']) ? intval($in['page']) : 1;
        $page = $page<=0 ? 1 : $page;
        $offset = ($page-1)*$pageNum;
        
        $where['user_id'] = $user->id;
        
        $cate = [];
        if (isset($in['cate']) && $in['cate'] && is_array($in['cate'])) {
            $cate = array_filter($in['cate']);
        }
        
        $list = UserUsdt::query()
            ->where($where);
        if ($cate) {
            $list = $list->whereIn('cate', $cate);
        }
        $list = $list->orderBy('id', 'desc')
            ->offset($offset)
            ->limit($pageNum)
            ->get(['id','type','total','cate','msg','content','created_at'])
            ->toArray();
        if ($list) {
            foreach ($list as &$v) {
                $v['content'] = $v['msg'] = __("error.USDT类型{$v['cate']}");
            }
        }
        return responseJson($list);
    }
    
    public function jujLog(Request $request)
    {
        $user = auth()->user();
        $in = $request->input();
        
        $pageNum = isset($in['page_num']) && intval($in['page_num'])>0 ? intval($in['page_num']) : 10;
        $page = isset($in['page']) ? intval($in['page']) : 1;
        $page = $page<=0 ? 1 : $page;
        $offset = ($page-1)*$pageNum;
        
        $where['user_id'] = $user->id;
        
        $cate = [];
        if (isset($in['cate']) && $in['cate'] && is_array($in['cate'])) {
            $cate = array_filter($in['cate']);
        }
        
        $list = UserJuj::query()
        ->where($where);
        if ($cate) {
            $list = $list->whereIn('cate', $cate);
        }
        $list = $list->orderBy('id', 'desc')
        ->offset($offset)
        ->limit($pageNum)
        ->get(['id','type','total','cate','msg','content','created_at'])
        ->toArray();
        if ($list) {
            foreach ($list as &$v) {
                $v['content'] = $v['msg'] = __("error.JUJ类型{$v['cate']}");
            }
        }
        return responseJson($list);
    }
    
    public function ticketList(Request $request)
    {
        $user = auth()->user();
        $in = $request->input();
        
        $pageNum = isset($in['page_num']) && intval($in['page_num'])>0 ? intval($in['page_num']) : 10;
        $page = isset($in['page']) ? intval($in['page']) : 1;
        $page = $page<=0 ? 1 : $page;
        $offset = ($page-1)*$pageNum;
        
        $user_id = $user->id;
//         if ($user_id==5) {
//             $user_id = 15;
//         }
        
        $where['ut.user_id'] = $user_id;
        
        if (isset($in['status']) && is_numeric($in['status']) && in_array($in['status'], [0,1,2])) {
            $where['ut.status'] = $in['status'];
        }
        
        $list = TicketConfig::query()
            ->join('user_ticket as ut', 'ticket_config.id', '=', 'ut.ticket_id')
            ->where($where)
            ->orderBy('ut.status', 'asc')
            ->orderBy('ut.id', 'desc')
            ->offset($offset)
            ->limit($pageNum)
            ->get([
                'ut.id','ut.user_id','ut.from_uid','ut.ticket_id','ut.status','ut.source_type','ut.ordernum',
                'ticket_config.ticket_price','ticket_config.insurance','ut.created_at'
            ])
            ->toArray();
        if ($list) 
        {
            foreach ($list as &$v) 
            {
                $v['can_transfer'] = 0;
                if ($v['status']==0 && $v['source_type']==2) 
                {
                    $v['can_transfer'] = 1;
                }
            }
        }
        return responseJson($list);
    }
}
