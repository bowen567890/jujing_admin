<?php

namespace App\Admin\Actions\Grid;

use Dcat\Admin\Grid\RowAction;
use Dcat\Admin\Actions\Response;
use Dcat\Admin\Traits\HasPermissions;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Dcat\Admin\Widgets\Modal;

class SetPoolNum extends RowAction
{
    /**
     * @return string
     */
    protected $title = '设置池子';
    
    public function render()
    {
        // 实例化表单类并传递自定义参数
        $form = \App\Admin\Forms\SetPoolNum::make()->payload(['id' => $this->getKey()]);
        
        return Modal::make()
            ->lg()
            ->title($this->title)
            ->body($form)
            ->button($this->title);
    }
    
}