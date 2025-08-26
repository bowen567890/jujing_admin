<?php

namespace App\Admin\Actions\Grid;

use Dcat\Admin\Actions\Response;
use Dcat\Admin\Grid\Tools\AbstractTool;
use Dcat\Admin\Traits\HasPermissions;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Dcat\Admin\Widgets\Modal;

class AddContributionOrder extends AbstractTool
{
    /**
     * @return string
     */
	protected $title = '添加贡献订单';
	protected $style = 'btn btn-primary';

	public function render()
	{
	    // 实例化表单类并传递自定义参数
	    $form = \App\Admin\Forms\AddContributionOrder::make()->payload([]);
	    
	    return Modal::make()
    	    ->lg()
    	    ->title($this->title)
    	    ->body($form)
    	    ->button('<a class="btn btn-primary btn-outline" style="color: #586cb1;">添加贡献订单</a>');
	}
}
