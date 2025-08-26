<?php

namespace App\Admin\Controllers;

use App\Models\News;
use Dcat\Admin\Form;
use Dcat\Admin\Grid;
use Dcat\Admin\Show;
use Dcat\Admin\Http\Controllers\AdminController;

class NewsController extends AdminController
{
    /**
     * Make a grid builder.
     *
     * @return Grid
     */
    protected function grid()
    {
        return Grid::make(new News(), function (Grid $grid) {
            $grid->column('id')->sortable();
            $grid->column('title');
            $grid->column('content')->display('点击查看') // 设置按钮名称
            ->modal(function ($modal) {
                // 设置弹窗标题
                $modal->title($this->title);
                // 自定义图标
                return $this->content;
            });
            $grid->column('created_at');
            $grid->column('updated_at')->sortable();
            
            $grid->model()->orderBy('id','desc');
            $grid->disableRowSelector();
            $grid->disableViewButton();
        
            $grid->filter(function (Grid\Filter $filter) {
                $filter->equal('id');
        
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
        return Form::make(new News(), function (Form $form) {
            $form->text('title')->required();
            $form->editor('content')->required()->disk('admin')->height('600');
//             $form->radio('status','状态')->required()->options([0=>'下架',1=>'上架'])->default(1);
            //             $form->radio('lang','语言')->required()->options(['zh_CN'=>'中文','en'=>'英文']);
//             $form->number('sort','排序')->required()->default(0);

            $form->disableDeleteButton();
            $form->disableViewButton();
            $form->disableCreatingCheck();
            $form->disableEditingCheck();
            $form->disableViewCheck();
            $form->disableResetButton();
        });
    }
}
