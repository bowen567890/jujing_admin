<?php
namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;

use App\Models\MyRedis;
use App\Models\Banner;
use App\Models\Bulletin;
use App\Models\User;
use App\Models\MainCurrency;
use App\Models\OrderLog;
use App\Models\PowerConf;
use App\Models\News;
use App\Models\UserNft;
use App\Models\NftConf;
use App\Models\UserVvStatic;
use App\Models\UserVvDynamic;
use App\Models\UserVv;
use App\Models\JackpotConfig;
use App\Models\JackpotRanking;
use App\Models\FeeOrder;
use App\Models\UserVvQuantifyWait;
use App\Models\UserVvQuantify;
use App\Models\UserVvJackpot;
use GuzzleHttp\Client;
use App\Models\Withdraw;
use App\Models\EnergyOrderLog;
use App\Models\IgniteOrderLog;
use App\Models\IgniteOrder;
use App\Models\SignConfig;
use App\Models\UserRankingDay;
use App\Models\PoolConfig;

class IndexController extends Controller
{
    public $host = '';
    
    public function __construct()
    {
        parent::__construct();
        $this->host =  $_SERVER['REQUEST_SCHEME'] . '://' . $_SERVER['HTTP_HOST'];
    }
    
    public function index(Request $request)
    {
        $in = $request->input();
        $user = auth()->user();
        $data['wallet'] = $user->wallet;
        $data['telegram_group_link'] = config('telegram_group_link');
        $data['nadi_group_link'] = config('nadi_group_link');
        return responseJson($data);
    }
    
    public function ranking(Request $request)
    {
        $day_ranking_limit = intval(config('day_ranking_limit'));
        $day = date('Y-m-d', time()-86400);
        $list = User::query()
            ->join('user_ranking_day as d', 'users.id', '=', 'd.user_id')
            ->where('d.day', $day)
            ->where('d.num', '>', 0)
            ->orderBy('d.num', 'desc')
            ->orderBy('d.updated_at', 'asc')
            ->limit($day_ranking_limit)
            ->get(['users.wallet','d.day','d.num'])
            ->toArray();
        if ($list) {
            foreach ($list as $key=>&$val) {
                $val['wallet'] =  substr_replace($val['wallet'],'*****', 3, -3);
                $val['ranking'] = $key+1;
            }
        }
        return responseJson($list);
    }
    
    public function pool(Request $request)
    {
        $PoolConfig = PoolConfig::query()->get(['type','pool'])->toArray();
        return responseJson($PoolConfig);
    }
    
    public function newsList(Request $request)
    {
        $user = auth()->user();
        $in = $request->input();
        
        $pageNum = isset($in['page_num']) && intval($in['page_num'])>0 ? intval($in['page_num']) : 10;
        $page = isset($in['page']) ? intval($in['page']) : 1;
        $page = $page<=0 ? 1 : $page;
        $offset = ($page-1)*$pageNum;
        
        $list = News::query()
            ->orderBy('id', 'desc')
            ->offset($offset)
            ->limit($pageNum)
            ->get(['id','title','content','created_at'])
            ->toArray();
        if ($list) {
//             foreach ($list as &$v) {
//             }
        }
        return responseJson($list);
    }
    
    public function newsInfo(Request $request)
    {
        $in = $request->input();
        
        $id = isset($in['id']) && intval($in['id'])>0 ? intval($in['id']) : 0;
        $info = News::query()
            ->where('id', $id)
            ->first(['id','title','content','created_at']);
        return responseJson($info);
    }
}
