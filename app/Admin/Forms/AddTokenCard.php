<?php

namespace App\Admin\Forms;

use Dcat\Admin\Widgets\Form;
use Dcat\Admin\Models\Administrator;
use Dcat\Admin\Traits\LazyWidget;
use Dcat\Admin\Contracts\LazyRenderable;
use App\Models\User;
use App\Models\MyRedis;
use Illuminate\Support\Facades\DB;
use Dcat\Admin\Admin;
use App\Models\TokenCard;
use App\Models\UserToken;

class AddTokenCard extends Form implements LazyRenderable
{
    use LazyWidget; // 使用异步加载功能
    
    public $cardArr = [
        1 => 'envoy_token',
        2 => 'angel_token',
        3 => 'zeus_token',
    ];
    
    public function handle(array $input)
    {
        $in = $input;
        
        if (!isset($in['wallet']) || !$in['wallet']) {
            return $this->response()->error('请输入钱包地址');
        }
        $wallet = trim($in['wallet']);
        $wallet = strtolower($wallet);
        
        if (!isset($in['card_id']) || !$in['card_id'])  {
            return $this->response()->error('请选择令牌');
        }
        $card_id = intval($in['card_id']);
        
        $lockKey = 'AddTokenCard';
        $MyRedis = new MyRedis();
//         $MyRedis->del_lock($lockKey);
        $lock = $MyRedis->setnx_lock($lockKey, 60);
        if(!$lock){
            return $this->response()->error('操作频繁');
        }
        
        $user = User::where('wallet', $wallet)->first(['id']);
        if (!$user) {
            $MyRedis->del_lock($lockKey);
            return $this->response()->error('钱包地址不存在');
        }
        
        $TokenCard = TokenCard::query()->where('id', $card_id)->first();
        if (!$TokenCard) {
            $MyRedis->del_lock($lockKey);
            return $this->response()->error('令牌不存在');
        }
        
        if (!isset($this->cardArr[$card_id])) {
            $MyRedis->del_lock($lockKey);
            return $this->response()->error('令牌不存在');
        }
        
        $num = 1;
        
        $UserToken = new UserToken();
        $UserToken->card_id = $card_id;
        $UserToken->user_id = $user->id;
        $UserToken->from_user_id = 0;
        $UserToken->num = $num;
        $UserToken->type = 1;
        $UserToken->save();
        
        User::query()->where('id', $user->id)->increment($this->cardArr[$card_id], $num);
        
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
        $this->text('wallet','钱包地址')->required();
        $this->select('card_id', '发放令牌')->options(TokenCard::query()->pluck('name','id')->toArray())->required();
    }
    
    /**
     * The data of the form.
     *
     * @return array
     */
    public function default()
    {
        return [
            'wallet' => '',
            'card_id' => 1,
        ];
    }
}
