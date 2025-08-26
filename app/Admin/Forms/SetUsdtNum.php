<?php

namespace App\Admin\Forms;

use Dcat\Admin\Widgets\Form;
use Dcat\Admin\Models\Administrator;
use Dcat\Admin\Traits\LazyWidget;
use Dcat\Admin\Contracts\LazyRenderable;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class SetUsdtNum extends Form implements LazyRenderable
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
        $num = $input['usdt'] ?? 0;
        $optype = $input['optype'] == 2 ? 2 : 1;
        
        // return $this->response()->error('Your error message.');
        
        $user = User::query()->where('id',$id)->first();
        
        if (intval($num)>0)
        {
            if ($optype==2) {
                if ($user->usdt<$num) {
                    return $this->response()->error('扣除数量大于现有TCB数量');
                }
            }
            
            $userModel = new User();
            $userModel->handleUser('usdt', $user->id, $num, $optype, ['cate'=>3,'msg'=>'链上分配']);
            
        } else {
            return $this->response()->error('操作数量需大于0');
        }
        
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
        //         $this->text('name')->required();
        //         $this->email('email')->rules('email');
        $this->radio('optype','操作类型')->options([1=>'增加',2=>'减少']);
        $this->number('usdt', '数量');
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
        
        //         $user = User::query()->where('id',$id)->first();
        
        return [
            'usdt' => 0,
            'optype' => 1
        ];
    }
}
