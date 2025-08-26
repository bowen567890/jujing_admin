<?php

namespace App\Admin\Controllers;

use App\Models\TicketCurrency;
use Dcat\Admin\Form;
use Dcat\Admin\Grid;
use Dcat\Admin\Show;
use Dcat\Admin\Http\Controllers\AdminController;
use Dcat\Admin\Http\JsonResponse;

class TicketCurrencyController extends AdminController
{
    public $pancakeArr = [
        1=>'V2',
        2=>'V3',
    ];
    public $syncArr = [
        0=>'否',
        1=>'是',
    ];
    
    protected function grid()
    {
        return Grid::make(new TicketCurrency(), function (Grid $grid) {
            $grid->column('id')->sortable();
            $grid->column('symbol');
            $grid->column('contract_address');
            $grid->column('contract_address_lp');
            $grid->column('price');
            $grid->column('coin_img')->image(env('APP_URL').'/uploads/',50,50);
            $grid->column('status')
            ->display(function () {
                $arr = [
                    0=>'关闭',
                    1=>'开启',
                ];
                $msg = $arr[$this->status];
                $colour = $this->status == 1 ? '#21b978' : '#b8c2cc';
                return "<span class='label' style='background:{$colour}'>{$msg}</span>";
            });
            
//             $grid->column('is_platform', '平台币')->display(function () {
//                 $arr = [
//                     0=>'否',
//                     1=>'是',
//                 ];
//                 $msg = $arr[$this->is_platform];
//                 $colour = $this->is_platform == 1 ? '#edc30e' : '#808080';
//                 return "<span class='label' style='background:{$colour}'>{$msg}</span>";
//             });
            
            $grid->column('is_sync')->display(function () {
                $arr = [
                    0=>'否',
                    1=>'是',
                ];
                $msg = $arr[$this->is_sync];
                $colour = $this->is_sync == 1 ? '#edc30e' : '#808080';
                return "<span class='label' style='background:{$colour}'>{$msg}</span>";
            });
            $grid->column('pancake_cate')->display(function () {
                $arr = [
                    1=>'V2',
                    2=>'V3',
                ];
                $msg = $arr[$this->pancake_cate];
                $colour = $this->pancake_cate == 1 ? '#edc30e' : '#ea5455';
                return "<span class='label' style='background:{$colour}'>{$msg}</span>";
            });
            $grid->column('updated_at')->sortable();
            
            $grid->model()->where('is_del', '=', 0);
            
            $grid->disableCreateButton();			//查看按钮
            $grid->disableDeleteButton();			//查看按钮
            $grid->disableViewButton();			//查看按钮
            $grid->disableRowSelector();		//帅选按钮
            $grid->scrollbarX();    			//滚动条
            $grid->paginate(10);				//分页
        
            $grid->filter(function (Grid\Filter $filter) {
                $filter->equal('id');
        
            });
        });
    }

    /**
     * Make a show builder.
     *
     * @param mixed $id
     *
     * @return Show
     */
    protected function detail($id)
    {
        return Show::make($id, new TicketCurrency(), function (Show $show) {
            $show->field('id');
            $show->field('symbol');
            $show->field('contract_address');
            $show->field('contract_address_lp');
            $show->field('coin_img');
            $show->field('status');
            $show->field('pancake_cate');
            $show->field('is_fan');
            $show->field('is_del');
            $show->field('created_at');
            $show->field('updated_at');
        });
    }

    /**
     * Make a form builder.
     *
     * @return Form
     */
    protected function form()
    {
        return Form::make(new TicketCurrency(), function (Form $form) {
            $form->display('id');
            
//             $form->text('symbol')->required()->placeholder('前端展示');
            $form->display('symbol');
            $form->text('contract_address')->required();
            $form->text('contract_address_lp')->required()->placeholder('池子地址(USDT填写同样)');
            $form->decimal('price')->required();
            $form->image('coin_img')->disk('admin')->required()->uniqueName()->maxSize(10240)->accept('jpg,png,jpeg,jfif')->autoUpload();
            $form->radio('status')->required()->options([0=>'关闭',1=>'开启'])->default(1);
            $form->radio('pancake_cate')->required()->options($this->pancakeArr)->default(1);
            $form->radio('is_sync')->required()->options($this->syncArr)->default(0);
            
            $form->saving(function (Form $form) {
                
                $post = $_POST;
                if ($post && isset($post['symbol']))
                {
                    $form->contract_address = strtolower($form->contract_address);
                    $form->contract_address_lp = strtolower($form->contract_address_lp);
                    $res = TicketCurrency::query()->where('is_del', 0)->where('contract_address', $form->contract_address)->first();
                    $id = $form->getKey();
                    if ($res)
                    {
                        if (!$id) {
                            return JsonResponse::make()->error('币种已存在');
                        }
                        if ($id && $res->id!=$id) {
                            return JsonResponse::make()->error('币种已存在');
                        }
                    }
                    
                    if ($id==1) {
                        $form->contract_address = env('USDT_ADDRESS');
                        $form->price = '1';
                        $form->is_sync = 0;
                    }
                    
                    if ($form->contract_address!=env('USDT_ADDRESS')) 
                    {
                        if ($form->is_sync==1) {
                            $TicketCurrency = new TicketCurrency();
                            if ($form->pancake_cate==1) //V2
                            {
                                $isLp = $TicketCurrency->getLpInfo($form->contract_address_lp);
                                if (!$isLp) {
                                    return JsonResponse::make()->error('LP信息不存在');
                                } else {
                                    if (!is_array($isLp) || !$isLp) {
                                        return JsonResponse::make()->error('LP信息未查询到');
                                    }
                                    $token0 = strtolower($isLp['token0']);
                                    $token1 = strtolower($isLp['token1']);
                                    $usdtContractAddress = env('USDT_ADDRESS');
                                    $busdContractAddress = env('BUSD_ADDRESS');
                                    if (($token0!=$usdtContractAddress && $token1!=$usdtContractAddress) && ($token0!=$busdContractAddress && $token1!=$busdContractAddress)) {
                                        return JsonResponse::make()->error('LP信息未查询到');
                                    }
                                    
                                    $reserve0 = @bcadd($isLp['reserve0'], '0', 8);
                                    $reserve1 = @bcadd($isLp['reserve1'], '0', 8);
                                    if (bccomp($reserve0, '0', 6)<=0 || bccomp($reserve1, '0', 6)<=0) {
                                        return JsonResponse::make()->error('LP信息有误');
                                    }
                                    
                                    if ($token1==$usdtContractAddress || $token1==$busdContractAddress) {
                                        $coin_price = @bcdiv($reserve1, $reserve0, 10);
                                    } else {
                                        $coin_price = @bcdiv($reserve0, $reserve1, 10);
                                    }
                                    
                                    if (bccomp($coin_price, '0', 6)>0) {
                                        $form->price = $coin_price;
                                    }
                                    
                                }
                                $form->is_fan = 0;
                            }
                            else //V3
                            {
                                $isLp = $TicketCurrency->getLpInfov3($form->contract_address_lp);
                                if (!$isLp) {
                                    return JsonResponse::make()->error('LP信息不存在');
                                } else {
                                    if (!is_array($isLp) || !$isLp) {
                                        return JsonResponse::make()->error('LP信息未查询到');
                                    }
                                    $token0 = strtolower($isLp['token0']);
                                    $token1 = strtolower($isLp['token1']);
                                    $usdtContractAddress = env('USDT_ADDRESS');
                                    $busdContractAddress = env('BUSD_ADDRESS');
                                    if (($token0!=$usdtContractAddress && $token1!=$usdtContractAddress) && ($token0!=$busdContractAddress && $token1!=$busdContractAddress)) {
                                        return JsonResponse::make()->error('LP信息未查询到');
                                    }
                                    
                                    if (bccomp($isLp['amountOut'], '0', 6)<=0) {
                                        return JsonResponse::make()->error('LP信息有误');
                                    }
                                    if ($token0==$usdtContractAddress || $token0==$busdContractAddress) {
                                        $form->is_fan = 2;
                                    }
                                    if ($token1==$usdtContractAddress || $token1==$busdContractAddress) {
                                        $form->is_fan = 1;
                                    }
                                }
                            }
                        }
                    } else {
                        $form->contract_address_lp = $form->contract_address;
                        $form->price =1;
                    }
                }
            });
                
            $form->disableDeleteButton();
            $form->disableViewButton();
            $form->disableCreatingCheck();
            $form->disableEditingCheck();
            $form->disableViewCheck();
            $form->disableResetButton();
        });
    }
    
    /**
     * 删除
     */
    public function destroy($id)
    {
        if (!in_array($id, [1])) {
            $machine = TicketCurrency::query()->where('id', $id)->first();
            $machine->is_del = 1;
            $machine->status = 0;
            $machine->save();
        }
        return JsonResponse::make()->success('删除成功')->location('ticket_currency');
    }
}
