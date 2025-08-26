<?php

namespace App\Admin\Forms;

use Dcat\Admin\Widgets\Form;
use App\Admin\Imports\BonusExcelImport;
use Maatwebsite\Excel\Facades\Excel;


class BonusExcel extends Form
{
    public function handle(array $input)
    {
        try {
            //上传文件位置，这里默认是在storage中，如有修改请对应替换
            $file = public_path('uploads/' . $input['file']);
            Excel::import(new BonusExcelImport(), $file);
            return $this->response()->success('数据导入成功')->refresh();
        } catch (\Exception $e) {
            return $this->response()->error($e->getMessage().'ssss');
        }
    }
    
    public function form()
    {
        $this->file('file', '上传数据（Excel）')->rules('required', ['required' => '文件不能为空'])->move('admin/upload/');
        
    }
}
