<?php

use GuzzleHttp\Client;
use Illuminate\Support\Facades\Log;
use App\Models\NftConfig;

require __DIR__ . '/response.php';
require __DIR__ . '/calculator.php';

function getImageUrl($path)
{
    $host =  env('APP_URL').'/uploads/';
//     $host =  env('OSS_URL');
    return $host.$path;
}

/**
 * 根据路径生成一个图片标签
 *
 * @param string       $url
 * @param string $disk
 * @param int    $width
 * @param int    $height
 * @return string
 */
function image($url, $disk = 'public', int $width = 50, int $height = 50) : string
{
    if (is_null($url) || empty($url)) {

        $url = get404Image();
    } else {

        $url = assertUrl($url, $disk);
    }

    return "<img width='{$width}' height='{$height}' src='{$url}' />";
}

function assertUrl($url, $disk = 'public')
{
    static $driver  = null;

    if (is_null($url) || empty($url)) {

        return get404Image();
    }

    if (is_null($driver)) {
        $driver = Storage::disk($disk);
    }

    if (! \Illuminate\Support\Str::startsWith($url, 'http')) {
        $url = $driver->url($url);
    }

    return $url;
}

function get404Image()
{
    return asset('images/404.jpg');
}


/**
 * 把字符串变成固定长度
 *
 * @param     $str
 * @param     $length
 * @param     $padString
 * @param int $padType
 * @return bool|string
 */
function fixStrLength($str, $length, $padString = '0', $padType = STR_PAD_LEFT)
{
    if (strlen($str) > $length) {
        return substr($str, strlen($str) - $length);
    } elseif (strlen($str) < $length) {
        return str_pad($str, $length, $padString, $padType);
    }

    return $str;
}

/**
 * 价格保留两位小数
 *
 * @param $price
 * @return float|int
 */
function ceilTwoPrice($price)
{
    return round($price, 2);
}

/**
 * 或者设置的配置项
 *
 * @param $key
 * @param null $default
 * @return mixed|null
 */
function setting($key, $default = null)
{
    $val = \Illuminate\Support\Facades\Cache::get('config:'.$key);
    if (is_null($val)) {

        $val = \App\Models\Config::query()->where('key', $key)->value('value');
        if (is_null($val)) {
            return $default;
        }

        \Illuminate\Support\Facades\Cache::put('config:'.$key, $val);
    }

    return $val;
}

/**
 * 生成系统日志
 *
 * @param       $description
 * @param array $input
 */
function createSystemLog($description, $input = [])
{
    $operate = new \Encore\Admin\Auth\Database\OperationLog();
    $operate->path = config('app.url');
    $operate->method = 'GET';
    $operate->ip = '127.0.0.1';
    $operate->input = json_encode($input);
    $operate->description = $description;
    $operate->save();
}

function getWallet($userId){
    try {
        $url = env('DAPP_GET_COIN_ADDRESS',null);
        if (empty($url)){
            return null;
        }
        $client = new Client();
        $response = $client->post($url,[
            'form_params' => [
                'userName' => $userId,
                'coinToken' => env('DAPP_COIN_TOKEN',null),
                'mainChain' => env('DAPP_MAIN_CHAIN',null)
            ]
        ]);
        $response = $response->getBody();
        Log::channel('account')->info($userId.'获取到内容'.$response);
        $response = json_decode($response,true);
        return $response['obj']['address'];
    }catch (\Exception $e){
        Log::channel('account')->info($userId.'遇到错误'.$e->getMessage().$e->getLine());
        return null;
    }
}

function getRandStr($length){
    //字符组合
    $str = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
    $len = strlen($str)-1;
    $randstr = '';
    for ($i=0;$i<$length;$i++) {
        $num=mt_rand(0,$len);
        $randstr .= $str[$num];
    }
    return $randstr;
}

function getNftCode($length=8){
    //字符组合
    $str = 'ABCDEFGHJKLMNOPQRSTUVWXYZ0123456789';
    $len = strlen($str)-1;
    $randstr = '';
    for ($i=0;$i<$length;$i++) {
        $num=mt_rand(0,$len);
        $randstr .= $str[$num];
    }
    return $randstr;
}


/**
 * @param $email
 * @return string
 * 隐藏邮箱手机号
 */
function mail_hidden($str)
{
    if (empty($str)){
        return $str;
    }

    if (strpos($str, '@')) {
        $email_array = explode("@", $str);

        if (strlen($email_array[0]) <= 2) {
            $prevfix = substr_replace($email_array[0], '*', 1, 1);
            $rs = $prevfix . $email_array[1];
//                $prevfix = substr($str, 0, 1); //邮箱前缀
//                $count = 0;
//                $str = preg_replace('/([\d\w+_-]{0,100})@/', '*@', $str, -1, $count);
//                $rs = $prevfix . $str;
        } else if (strlen($email_array[0]) < 5) {
            $prevfix = substr_replace($email_array[0], '**', 1, 1);
            $rs = $prevfix . $email_array[1];
        } else {
            $prevfix = substr_replace($email_array[0], '***', 3, 1);
            $rs = $prevfix . $email_array[1];
        }

    } else {
        $pattern = '/(1[3458]{1}[0-9])[0-9]{4}([0-9]{4})/i';
        if (preg_match($pattern, $str)) {
            $rs = preg_replace($pattern, '$1****$2', $str); // substr_replace($name,'****',3,4);
        } else {
            $rs = substr($str, 0, 3) . "***" . substr($str, -1);
        }
    }
    return $rs;
}

function hiddenAddress($str){
    if (empty($str)) return '';
    return substr($str, 0, 4) . "*********" . substr($str, -4);
}

function logic($name){
    static $logic;
    if (!isset($logic[$name])){
        $path = '\\App\Logic\\'.ucfirst($name).'Logic';
        $logic[$name] = new $path;
    }
    return $logic[$name];
}

function toArray($obj){
    return get_object_vars($obj);
}

/**
 * @卡牌随机哈希值
 */
function randee($len=16)
{
    $chars='ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789abcdefghijklmnopqrstuvwxyz';
    $string=time();
    for(;$len>=1;$len--)
    {
        $position=rand()%strlen($chars);
        $position2=rand()%strlen($string);
        $string=substr_replace($string,substr($chars,$position,1),$position2,0);
    }
    return $string;
}


/**
 * 得到新订单号
 * @return  string
 */
function get_ordernum($prefix='') {
    return $prefix.date('ymdHis') . str_pad(mt_rand(1, 99999999), 8, '0', STR_PAD_LEFT);
}

/**
 * [手机号码验证]
 */
function checkPhoneNumber($phone_number){
    //@2017-11-25 14:25:45 https://zhidao.baidu.com/question/1822455991691849548.html
    //中国联通号码：130、131、132、145（无线上网卡）、155、156、185（iPhone5上市后开放）、186、176（4G号段）、175（2015年9月10日正式启用，暂只对北京、上海和广东投放办理）,166,146
    //中国移动号码：134、135、136、137、138、139、147（无线上网卡）、148、150、151、152、157、158、159、178、182、183、184、187、188、198
    //中国电信号码：133、153、180、181、189、177、173、149、199
    $g = "/^1[34578]\d{9}$/";
    $g2 = "/^19[89]\d{8}$/";
    $g3 = "/^166\d{8}$/";
    if(preg_match($g, $phone_number)){
        return true;
    }else  if(preg_match($g2, $phone_number)){
        return true;
    }else if(preg_match($g3, $phone_number)){
        return true;
    }
    
    return false;
}

function curl_post($url, $params = [], $headers = [])
{
    header("Content-Type:text/html;charset=utf-8");
    $ch = curl_init();//初始化
    curl_setopt($ch, CURLOPT_URL, $url);//抓取指定网页
    curl_setopt($ch, CURLOPT_HEADER, 0);//设置header
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);//要求结果为字符串且输出到屏幕上
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 60);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); // https请求 不验证证书和hosts
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
    if (!empty($headers)) {
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    }
    curl_setopt($ch, CURLOPT_POST, 1);//post提交方式
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($params));
    $data = curl_exec($ch);//运行curl
    curl_close($ch);
    return ($data);
}

/**
 * 发送短信 2019年3月19日14:18:48 HH
 * @return [type] [description]
 */
function toSendSms($mobile, $message)
{
    $message = "【紫光云】".$message;
    $url = "http://121.201.57.213/sms.aspx";
    $data = [
        'action'   => 'send',
        'userid'   => '1111',
        'account'  => 'yangsheng',
        'password' => '123456',
        'mobile'   => $mobile,
        'content'  => $message,
    ];
    //初始化
    $ch = curl_init();
    //
    $this_header = [
        "content-type: application/x-www-form-urlencoded;
            charset=UTF-8"
    ];
    
    $result = curl_post($url,$data,$this_header);
    $result = xmlToArray($result);
    if ($result['returnstatus'] == 'Success') {
        return true;
    } else {
        return false;
    }
}

function xmlToArray($xml)
{
    //禁止引用外部xml实体
    libxml_disable_entity_loader(true);
    $values = json_decode(json_encode(simplexml_load_string($xml, 'SimpleXMLElement', LIBXML_NOCDATA)), true);
    return $values;
}

/**
 * 公钥加密
 * @param string 明文
 * @return string 密文（base64编码）
 * http://web.chacuo.net/netrsakeypair
 * https://www.jianshu.com/p/7f3d1a8e0d8f   //VUE加密
 */
function rsaEncodeing($sourcestr)
{
    $path = base_path();
    $publicKey = file_get_contents($path . DIRECTORY_SEPARATOR . 'rsa_public.key');
    $pubkeyid    = openssl_get_publickey($publicKey);
    if (openssl_public_encrypt($sourcestr, $crypttext, $pubkeyid))
    {
        return base64_encode($crypttext);
    }
    return false;
}

/**
 * 私钥解密
 * @param string 密文（二进制格式且base64编码）
 * @param string 密文是否来源于JS的RSA加密
 * @return string 明文
 */
function rsaDecodeing($crypttext)
{
    $path = base_path();
    $privateKey = file_get_contents($path . DIRECTORY_SEPARATOR . 'rsa_private.key');
    $prikeyid = openssl_get_privatekey($privateKey);
    $crypttext = base64_decode($crypttext);
    if (openssl_private_decrypt($crypttext, $sourcestr, $prikeyid, OPENSSL_PKCS1_PADDING))
    {
        return $sourcestr;
    }
    return false;
}

function objectToArray($object) {
    //先编码成json字符串，再解码成数组
    return json_decode(json_encode($object), true);
}

/**
 * 获取用户真实 ip
 * @return array|false|mixed|string
 */
function getClientIp()
{
    if (getenv('HTTP_CLIENT_IP')) {
        $ip = getenv('HTTP_CLIENT_IP');
    }
    if (getenv('HTTP_X_REAL_IP')) {
        $ip = getenv('HTTP_X_REAL_IP');
    } elseif (getenv('HTTP_X_FORWARDED_FOR')) {
        $ip = getenv('HTTP_X_FORWARDED_FOR');
        $ips = explode(',', $ip);
        $ip = $ips[0];
    } elseif (getenv('REMOTE_ADDR')) {
        $ip = getenv('REMOTE_ADDR');
    } else {
        $ip = '0.0.0.0';
    }
    return $ip;
}

//波场地址校验
function checkBnbAddress($address)
{
    if (!is_string($address) || !$address || mb_strlen($address, 'UTF8')!=42) {
        return false;
    }
    
    $first = mb_substr($address, 0, 1, 'UTF8');
    $first2 = mb_substr($address, 1, 1, 'UTF8');
    if ($first!='0') {
        return false;
    }
    if ($first2!='x') {
        return false;
    }
    return true;
}

/**
* 获取币价
*/
function getTokenPrice($coin=1)
{
    $price = 0;
    if ($coin==1) {
        $price = @bcadd(config('hd_usdt_price'), '0', 6);
    }
    return $price;
}


/**
 * 根据LP数量和代币价格 求LP价值(USDT)
 * @param $lpNum     LP数量
 * @param $goinPrice 代币价格(代币/USDT)
 * $lpNum² = (coin1*n)*(coin2*n) 简化后 $lpNum² = 代币价格 * n²
 * 如果主币涨了 就赚
 */
function getLpValue($lpNum, $goinPrice)
{
    $powNum = @bcpow($lpNum, '2', 10);                //LP的二次方    bcmul($lpNum, $lpNum, 6) 也可以
    $squareNum = @bcdiv($powNum, $goinPrice, 10);
    $sqrtNum = @bcsqrt($squareNum, '10');             //二次方平方根
    $usdtNum = @bcmul($goinPrice, $sqrtNum, 10);    //LP质押的USDT数量
    $allUsdtNum = @bcmul($usdtNum, '2', 6);           //一个LP等于两个 币对半分
    return [
        'main' => $sqrtNum,
        'usdt' => $usdtNum,
        'allUsdt' => $allUsdtNum
    ];
}

/**
 * 根据LP数量和代币价格 求LP价值(USDT)
 * @param $lpNum            LP数量
 * @param $coinPrice1       代币1价格(代币1/USDT)
 * @param $coinPrice2       代币2价格(代币2/USDT) //默认为1是USDT
 * $lpNum² = (coin1*n)*(coin2*n) 简化后 $lpNum² = 代币1价格*代币2价格*n²
 * 如果主币涨了 就赚
 */
function getLpValue2($lpNum, $coinPrice1, $coinPrice2=1)
{
    $powNum = @bcpow($lpNum, '2', 10);                    //LP的二次方    bcmul($lpNum, $lpNum, 6) 也可以
    
    $squareNum = @bcdiv($powNum, @bcmul($coinPrice1, $coinPrice2, 10), 10);
    $sqrtNum = @bcsqrt($squareNum, '10');                 //n² 二次方平方根
    
    $coin1Num = @bcmul($coinPrice2, $sqrtNum, 10);      //求出代币1数量
    $coin2Num = @bcmul($coinPrice1, $sqrtNum, 10);      //求出代币2数量
    
    $coin1Usdt = bcmul($coin1Num, $coinPrice1, 10);      //代币1价值USDT
    //     $coin2Ust = bcmul($coin2Num, $coinPrice2, 6);    //代币2价值USDT
    $allUsdtNum = bcmul($coin1Usdt, '2', 6);              //两个代币价值相同 所有拿一个相乘就可以
    return [
        'coin1Num' => $coin1Num,
        'coin2Num' => $coin2Num,
        'allUsdt' => $allUsdtNum
    ];
}

function echoJson($code=200, $msg='success', $data=[]) {
    echo json_encode(['code'=>$code, 'msg'=>$msg, 'data'=>$data]);
    die;
}

//$text = "这是一段包含😊表情的文本";
function filterInput($text)
{
    //过滤表情符号
    $emojiPattern = '/[\x{1F300}-\x{1F5FF}\x{1F600}-\x{1F64F}\x{2700}-\x{27BF}]/u'; // Unicode范围内包含所有常见表情符号
    $text = preg_replace($emojiPattern, '', $text);
    $text = strip_tags($text);  //去除html标签
    $text = trim($text);        //去除空格
    return $text;
}

/**
 * [手机号码验证]
 */
function checkEmail($email){
    
    $pattern = "/^[_a-zA-Z0-9-]+([-_.][a-zA-Z0-9]+)*@([a-zA-Z0-9]+[-.])+([a-z]{2,5})$/";
    if (!preg_match($pattern, $email)) {
        return false;
    }
    
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        return false;
    }
    $emArr = explode("@",$email);
    if(!checkdnsrr(array_pop($emArr),"MX")) {
        return false;
    }
    
    return true;
}

/**
 * 获取赎回天数
 */
function getRedeemDay($beginTime='')
{
    $time = time();
    $timeSteam = strtotime($beginTime);
    $diff = $time-$timeSteam;
    return intval($diff/86400);
}


function getLang() {
    $txt = request()->header('lang', 'zh_CN');
    $lang = '';
    if (in_array($txt, ['tw','en','th','vi','ko','ja'])) {
        $lang = '_'.$txt;
    }
    return $lang;
}

function getNftName($lv=0, $NftConfig=[]) 
{
    if (!$NftConfig) {
        $NftConfig = NftConfig::GetListCache();
    }
    $NftConfig = array_column($NftConfig, null, 'lv');
    
    $lang = getLang();
    $nameField = 'name'.$lang;
//     $descField = 'desc'.$lang;
    $name = isset($NftConfig[$lv][$nameField]) ? $NftConfig[$lv][$nameField] : '';
    
    return $name;
}














