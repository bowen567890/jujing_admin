<?php

namespace App\Admin\Controllers;

use App\Models\MainCurrency;
use Dcat\Admin\Form;
use Dcat\Admin\Grid;
use Dcat\Admin\Show;
use Dcat\Admin\Http\Controllers\AdminController;
use Illuminate\Support\Facades\Cache;
use Dcat\Admin\Http\JsonResponse;

class MainCurrencyController extends AdminController
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
        return Grid::make(new MainCurrency(), function (Grid $grid) {
            $grid->column('id');
            $grid->column('name');
            $grid->column('coin_img')->image(env('APP_URL').'/uploads/',50,50);
            /* 
            $grid->column('rate')->display(function ($rate){
                return '1:'.$rate;
            })->badge();
             */
            $grid->column('rate','代币价格');
            $grid->column('contract_address','合约地址');
            $grid->column('contract_address_lp', 'LP合约地址');
            $grid->column('precision');
            $grid->column('is_sync', '同步价格')->display(function () {
                $arr = [
                    0=>'否',
                    1=>'是',
                ];
                $msg = $arr[$this->is_sync];
                $colour = $this->is_sync == 1 ? '#edc30e' : '#808080';
                return "<span class='label' style='background:{$colour}'>{$msg}</span>";
            });
//             $grid->column('created_at')->sortable();
            $grid->column('updated_at');
//             $grid->model()->orderBy('id','desc');

            $grid->disableCreateButton();		//创建按钮
            $grid->disableRowSelector();		//帅选按钮
            
            $grid->disableViewButton();			//查看按钮
            $grid->disableRowSelector();		//帅选按钮
            $grid->disableDeleteButton();		//删除按钮

            $grid->filter(function (Grid\Filter $filter) {
                $filter->equal('name');
            });
        });
    }


    /**
     * Make a form builder.
     *
     * @return Form
     */
    protected function form()
    {
        return Form::make(new MainCurrency(), function (Form $form) {
            $form->display('name');
            $form->image('coin_img')->disk('admin')->uniqueName()->maxSize(10240)->accept('jpg,png,gif,jpeg')->autoUpload();
            //             $form->decimal('rate')->required();
            $form->hidden('pancake_cate');
            $form->text('contract_address','合约地址')->required();
            $form->text('contract_address_lp', 'LP合约地址')->required()->placeholder('池子地址(USDT填写同样)');
            $form->decimal('rate','代币价格')->required();
            $form->number('precision')->min(0)->required();
            $form->radio('is_sync', '同步价格')->required()->options($this->syncArr)->default(0);
            
            $form->saving(function (Form $form) {
                $post = $_POST;
                if ($post && isset($post['contract_address']))
                {
                    $form->pancake_cate=1;
                    
                    $form->contract_address = strtolower($form->contract_address);
                    $form->contract_address_lp = strtolower($form->contract_address_lp);
                    $res = MainCurrency::query()->where('contract_address', $form->contract_address)->first();
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
                    $rate = @bcadd($form->rate, '0', 10);
                    if (bccomp($rate, '0', 10)<0) {
                        return JsonResponse::make()->error('价格不正确');
                    }
                    
                    if ($id==1) {
                        $form->contract_address = env('USDT_ADDRESS');
                        $form->price = '1';
                        $form->is_sync = 0;
                        $form->contract_address_lp = $form->contract_address;
                        $form->rate =1;
                    }
                    
                    if ($id==2) {
                        $form->contract_address = env('WBNB_ADDRESS');
                        $form->contract_address_lp = env('WBNB_ADDRESS_LP');
                    }
          
                    if ($id==3) {
                        $form->contract_address = env('DOGBEE_ADDRESS');
                        $form->contract_address_lp = env('DOGBEE_ADDRESS_LP');
                    }
                    
                    if ($form->contract_address!=env('USDT_ADDRESS'))
                    {
                        //SPACEX代币特殊处理
                        if (!in_array($id, [2])) 
                        {
                            if ($form->is_sync==1)
                            {
                                $MainCurrency = new MainCurrency();
                                if ($form->pancake_cate==1) //V2
                                {
                                    $isLp = $MainCurrency->getLpInfo($form->contract_address_lp);
                                    if (!$isLp) {
                                        return JsonResponse::make()->error('LP信息不存在22');
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
                                        
                                        $reserve0 = @bcadd($isLp['reserve0'], '0', 10);
                                        $reserve1 = @bcadd($isLp['reserve1'], '0', 10);
                                        if (bccomp($reserve0, '0', 10)<=0 || bccomp($reserve1, '0', 10)<=0) {
                                            return JsonResponse::make()->error('LP信息有误');
                                        }
                                        
                                        if ($token1==$usdtContractAddress || $token1==$busdContractAddress) {
                                            $coin_price = @bcdiv($reserve1, $reserve0, 10);
                                        } else {
                                            $coin_price = @bcdiv($reserve0, $reserve1, 10);
                                        }
                                        
                                        if (bccomp($coin_price, '0', 10)>0) {
                                            $form->rate = $coin_price;
                                        }
                                    }
                                }
                                else //V3
                                {
                                    $isLp = $MainCurrency->getLpInfov3($form->contract_address_lp);
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
                                        
                                        if (bccomp($isLp['amountOut'], '0', 10)<=0) {
                                            return JsonResponse::make()->error('LP信息有误');
                                        }
                                        //                                     if ($token0==$usdtContractAddress || $token0==$busdContractAddress) {
                                        //                                         $form->is_fan = 2;
                                        //                                     }
                                        //                                     if ($token1==$usdtContractAddress || $token1==$busdContractAddress) {
                                        //                                         $form->is_fan = 1;
                                        //                                     }
                                        }
                                }
                            }
                        }
                    }
                }
            });
                
            $form->disableViewButton();
            $form->disableDeleteButton();
            $form->disableResetButton();
            $form->disableViewCheck();
            $form->disableEditingCheck();
            $form->disableCreatingCheck();
        });
    }
    
    /**
     * 删除
     */
    public function destroy($id)
    {
        return JsonResponse::make()->success('删除成功')->location('main_currency');
    }
}
