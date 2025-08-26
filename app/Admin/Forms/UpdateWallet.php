<?php

namespace App\Admin\Forms;

use Dcat\Admin\Widgets\Form;
use Dcat\Admin\Models\Administrator;
use Dcat\Admin\Traits\LazyWidget;
use Dcat\Admin\Contracts\LazyRenderable;
use App\Models\User;
use App\Models\UpdateParentLog;
use Illuminate\Support\Facades\DB;
use App\Models\MyRedis;
use App\Models\UpdateWalletLog;
use GuzzleHttp\Client;
use Illuminate\Support\Facades\Log;
use App\Models\UsersBscAddress;

class UpdateWallet extends Form implements LazyRenderable
{
    use LazyWidget; // 使用异步加载功能
    /**
     * Handle the form request.
     *
     * @param array $input
     *
     * @return mixed
     */
    public function handle(array $input)
    {
        $id = $this->payload['id'] ?? 0;
        $in = $input;
        
        if (!isset($in['new_wallet']) || !$in['new_wallet']) {
            return $this->response()->error('请输入新钱包地址');
        }
        $new_wallet = trim($in['new_wallet']);
        if (!checkBnbAddress($new_wallet)) {
            return $this->response()->error('新钱包地址错误');
        }
        $new_wallet = strtolower($new_wallet);
        
        $MyRedis = new MyRedis();
        $lockKey = 'UpdateWallet';
//         $MyRedis->del_lock($lockKey);
        $lock = $MyRedis->setnx_lock($lockKey, 30);
        if(!$lock){
            return $this->response()->error('操作频繁');
        }
        
        DB::beginTransaction();
        try
        {
            $hasUser = User::query()->where('wallet', $new_wallet)->first();
            if ($hasUser) {
                $MyRedis->del_lock($lockKey);
                return $this->response()->error('地址已存在');
            }
            $user = User::query()->where('id', $id)->first();
            $old_wallet = $user->wallet;
//             if ($old_wallet) {
//                 $MyRedis->del_lock($lockKey);
//                 return $this->response()->error('地址不允许修改,只能新增');
//             }
            
            $UpdateWalletLog = new UpdateWalletLog();
            $UpdateWalletLog->user_id = $user->id;
            $UpdateWalletLog->new_wallet = $new_wallet;
            $UpdateWalletLog->old_wallet = $old_wallet;
            $UpdateWalletLog->type = 1; //来源类型1DAPP系统2游戏系统
            $UpdateWalletLog->save();
            
            $user->wallet = $new_wallet;
            $user->save();
            
            DB::commit();
        }
        catch (\Exception $e)
        {
            DB::rollBack();
            $MyRedis->del_lock($lockKey);
            return $this->response()->error($e->getMessage().$e->getLine());
            return $this->response()->error('操作失败');
        }
        $MyRedis->del_lock($lockKey);
        return $this
            ->response()
            ->success('操作成功')
            ->refresh();
    }
    
    /**
     * Build a form here.
     */
    public function form()
    {
        $this->display('user_id', '当前用户ID');
        $this->display('old_wallet', '当前钱包地址');
        $this->text('new_wallet', '新钱包地址')->placeholder('填写新钱包地址')->required();
        $this->disableResetButton();
    }
    
    /**
     * The data of the form.
     *
     * @return array
     */
    public function default()
    {
        $id = $this->payload['id'] ?? 0;
        $user = User::query()->where('id', $id)->first(['id','wallet']);
        return [
            'user_id' => $user->id,
            'old_wallet' => $user->wallet,
            'new_wallet' => '',
        ];
    }
}
