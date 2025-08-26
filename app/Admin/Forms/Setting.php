<?php

namespace App\Admin\Forms;

use App\Admin\Forms\Config\Website;
use App\Models\Config;
use Dcat\Admin\Widgets\Card;
use Dcat\Admin\Widgets\Form;
use Dcat\Admin\Widgets\Tab;
use Illuminate\Support\Facades\Cache;

class Setting extends Form
{
    /**
     * Handle the form request.
     *
     * @param array $input
     *
     * @return mixed
     */
    public function handle(array $input)
    {
         foreach ($input as $k=>$v){
             Config::where('key',$k)->update([
                 'value' => $v
             ]);

             Cache::put('config:'.$k,$v);
         }

        return $this
				->response()
				->success('操作成功.')
				->refresh();
    }

    /**
     * Build a form here.
     */
    public function form()
    {

        //查看有哪些组
        $groups = Config::query()->orderBy('ts','desc')->groupBy(['group','ts'])->pluck('group');

        foreach ($groups as $group){
            $list = Config::query()->where('group',$group)->get()->toArray();

            $this->tab($group,function (Form $form) use ($list){

                foreach ($list as $v) 
                {
                    $ac = $v['ac'];

                    if ($ac == 'file') {
                        $form->$ac($v['key'], $v['desc'])->chunked()->uniqueName()->maxSize(204800)->required()->disk('oss')->autoUpload();
                    } elseif ($ac == 'image') {
                        $form->$ac($v['key'], $v['desc'])->disk('oss')->uniqueName()->maxSize(204800)->accept('jpg,png,gif,jpeg')->required()->autoUpload();
                    } else {
                        $form->$ac($v['key'], $v['desc'])->required();
                    }
                }

            });


        }
    }

    /**
     * The data of the form.
     *
     * @return array
     */
    public function default()
    {
        $config = Config::get()->toArray();

        $default = [];
        foreach ($config as $v){
            $default[$v['key']] = $v['value'];
        }

        return $default;
    }
}
