<?php

namespace App\Admin\Controllers;

use App\Models\Bulletin;
use Dcat\Admin\Form;
use Dcat\Admin\Grid;
use Dcat\Admin\Show;
use Dcat\Admin\Http\Controllers\AdminController;
use Dcat\Admin\Http\JsonResponse;

class BulletinController extends AdminController
{
    public $statusArr = [
        0 => '下架',
        1 => '上架',
    ];
    protected function grid()
    {
        return Grid::make(new Bulletin(), function (Grid $grid) {
            $grid->column('id')->sortable();
            $grid->column('title');
            $grid->column('content')->display('点击查看') // 设置按钮名称
            ->modal(function ($modal) {
                // 设置弹窗标题
                $modal->title($this->title);
                // 自定义图标
                return $this->content;
            });
            $grid->column('status')
            ->display(function () {
                $arr = [
                    0 => '下架',
                    1 => '上架',
                ];
                $msg = $arr[$this->status];
                $colour = $this->status == 1 ? '#21b978' : 'gray';
                return "<span class='label' style='background:{$colour}'>{$msg}</span>";
            });
            $grid->column('sort');
            $grid->column('created_at');
            $grid->column('updated_at')->sortable();

            $grid->model()->orderBy('id','desc');
            $grid->disableRowSelector();
            $grid->disableViewButton();
            $grid->scrollbarX();    			//滚动条
            $grid->paginate(10);				//分页

            $grid->filter(function (Grid\Filter $filter) {
                $filter->equal('status')->radio($this->statusArr);
                /* 
                $filter->equal('lang','语言')->radio([
                    'zh_CN'=>'中文',
                    'en'=>'英文'
                ]);
                 */
            });
        });
    }

    protected function form()
    {
        return Form::make(new Bulletin(), function (Form $form) {
            $form->text('title')->required();
            $form->text('title_en')->required();
//             $form->text('title_fr')->required();
            $form->textarea('content')->required();
            $form->textarea('content_en')->required();
//             $form->editor('content_fr')->required()->disk('admin')->height('600');
            $form->radio('status')->required()->options($this->statusArr)->default(1);
            $form->number('sort','排序')->required()->default(99)->min(0);
            
            $form->saved(function (Form $form, $result) {
                Bulletin::SetListCache();
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
        Bulletin::query()->where('id', $id)->delete();
        Bulletin::SetListCache();
        return JsonResponse::make()->success('删除成功')->location('bulletin');
    }
}
