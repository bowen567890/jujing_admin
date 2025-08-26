<?php

namespace App\Admin\Controllers;

use App\Admin\Repositories\Banner;
use Dcat\Admin\Form;
use Dcat\Admin\Grid;
use Dcat\Admin\Show;
use Dcat\Admin\Http\Controllers\AdminController;

class BannerController extends AdminController
{
    /**
     * Make a grid builder.
     *
     * @return Grid
     */
    protected function grid()
    {
        return Grid::make(new Banner(), function (Grid $grid) {
            $grid->number();
            $grid->column('name');
            $grid->column('banner')->image(env('APP_URL').'/uploads/',100,100);
//             $grid->column('banner')->lightbox(env('APP_URL').'/uploads/',100,100);
            
//             $grid->column('vedio')->video(env('APP_URL').'/uploads/');
            /* 
            $grid->column('vedio')->video(function (\Abovesky\DcatAdmin\MediaPlayer\Grid\VideoDisplayer $video){
                // 自定义弹窗标题
                $video->title('视频');
                // 自定义按钮文字
//                 $video->button('按钮文字');
//                 $video->button('按钮文字');
                // 自定义按钮图标
                $video->icon('fa fa-play');
                // 自定义服务器地址
                $video->server(env('APP_URL').'/uploads/');
            });
             */
            $grid->column('status','状态')->switch();
            $grid->column('sort')->editable(true);
//             $grid->column('lang','语言')->using(['zh_CN'=>'中文','en'=>'英文']);
            $grid->column('created_at');
            $grid->column('updated_at')->sortable();

            $grid->model()->orderBy('id','desc');
//             $grid->disableViewButton();
            $grid->disableRowSelector();


            $grid->filter(function (Grid\Filter $filter) {
                $filter->equal('name');
                $filter->equal('status','状态')->radio([
                    0 => '下架',
                    1 => '上架'
                ]);
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
        return Show::make($id, new Banner(), function (Show $show) {
            $show->field('id');
            $show->field('name');
            $show->field('banner')->image(env('APP_URL').'/uploads/');
//             $show->field('banner')->lightbox(env('APP_URL').'/uploads/');
//             $show->field('vedio')->video(env('APP_URL').'/uploads/');
//             $show->field('vedio')->video2(env('APP_URL').'/uploads/');
            $show->disableDeleteButton();
            $show->disableEditButton();
        });
    }
    

    /**
     * Make a form builder.
     *
     * @return Form
     */
    protected function form()
    {
        return Form::make(new Banner(), function (Form $form) {
            $form->text('name')->required()->rules('max:250');
//             $form->image('banner','Banner')->disk('admin')->uniqueName()->maxSize(10240)->accept('jpg,png,gif,jpeg')->required()->autoUpload();
            $form->image('banner','轮播图')->disk('admin')->uniqueName()->maxSize(10240)->accept('jpg,png,gif,jpeg')->autoUpload();
//             $form->file('vedio')->disk('admin')->autoUpload();
            $form->radio('status','状态')->required()->options([0=>'下架',1=>'上架'])->default(1);
//             $form->radio('lang','语言')->required()->options(['zh_CN'=>'中文','en'=>'英文']);
            $form->number('sort','排序')->required()->default(0);
            
            $form->disableViewButton();
            $form->disableDeleteButton();
            $form->disableResetButton();
            $form->disableViewCheck();
            $form->disableEditingCheck();
            $form->disableCreatingCheck();
        });
    }
}
