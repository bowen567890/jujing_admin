<?php


namespace App\Http\Controllers\Api;


use App\Http\Controllers\Controller;
use App\Models\Banner;
use Illuminate\Support\Str;
use App\Models\BlackHole;
use App\Models\Bulletin;
use App\Models\FigureConf;
use App\Models\MainCurrency;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Intervention\Image\Facades\Image as UpImage;

class BasicController extends Controller
{
    /**
     * 上传文件
     * laravel8 文件上传 缩略图剪切 图片水印 https://blog.csdn.net/qq_34913864/article/details/120880760 使用composer腾讯镜像
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    /**
     * 上传文件
     * laravel8 文件上传 缩略图剪切 图片水印 https://blog.csdn.net/qq_34913864/article/details/120880760 使用composer腾讯镜像
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function upload(Request $request)
    {
        $in = $request->input();
        if (!isset($in['type']) && !in_array(intval($in['type']), [1])) {
            return responseValidateError(__('error.参数错误'));
        }
        $type = intval($in['type']);
        $folderArr = [1=>'headimgurl'];
        $folder = $folderArr[$type];
        
        $file = $request->file('file');
        if (empty($file)){
            return responseValidateError(__('error.请选择文件'));
        }
        
        $extension = $file->getClientOriginalExtension();
        if (!$extension || !in_array($extension, ['jpg','png','jpeg', 'jfif'])) {
            return responseValidateError(__('error.格式错误'));
        }
        
        $size = bcdiv($file->getSize(), '1024', 0); //KB
        if ($size>2048) {
            return responseValidateError(__('error.图片大小有误'));
        }
        
        //压缩图片
        //引入的类   use Intervention\Image\Facades\Image;
        if ($type==1) {
            $sadir = "./uploads/{$folder}";
            if (!is_dir($sadir)) {
                mkdir($sadir, 0777, true);
            }
            $img = UpImage::make($file)->resize(48,48);
            $path = $this->hashName('headimgurl', $extension);
            $img->save('uploads/'.$path);
            $upload = $path;
        }
        else
        {
            $folder = "$folder/".date('Ymd');
            $sadir = "./uploads/".$folder;
            if (!is_dir($sadir)) {
                mkdir($sadir, 0777, true);
            }
            $img = UpImage::make($file);
            $path = $this->hashName($folder, $extension);
            $img->save('uploads/'.$path, 20);
            $upload = $path;
        }
        
        return responseJson([
            'path' => $upload,
            'pathUrl' => assertUrl($upload,'admin')
        ]);
    }
    public function banner(){
        $list = Banner::query()->where('status',1)->where('lang',request()->header('lang','zh_CN'))->orderBy('sort','desc')->select(['name','banner'])->get();

        foreach ($list as $v){
            $v->banner = assertUrl($v->banner,'admin');
        }

        return responseJson($list);
    }


    public function bulletin()
    {
        $lang = request()->header('lang', 'zh_CN');
        $lang = $lang=='zh_CN' ? '' : ($lang ? '_'.$lang : '');
        $titleField = 'title'.$lang;
        $contentField = 'content'.$lang;
        
        $list = Bulletin::GetListCache();
        if ($list) 
        {
            foreach ($list as &$val)
            {
                $val['title'] = isset($val[$titleField]) ? $val[$titleField] : '';
                $val['content'] = isset($val[$contentField]) ? $val[$contentField] : '';
                unset($val['title_en'],$val['content_en'],$val['title_fr'],$val['content_fr']);
            }
        }

        return responseJson($list);

    }
    
    public function hashName($path = null, $extension)
    {
        if ($path) {
            $path = rtrim($path, '/').'/';
        }
        $hash = Str::random(40);
        if ($extension) {
            $extension = '.'.$extension;
        } else {
            return false;
        }
        return $path.$hash.$extension;
    }

}
