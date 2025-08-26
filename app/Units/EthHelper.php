<?php


namespace App\Units;

use BitWasp\Bitcoin\Crypto\Random\Random;
use BitWasp\Bitcoin\Key\Factory\HierarchicalKeyFactory;
use BitWasp\Bitcoin\Mnemonic\Bip39\Bip39Mnemonic;
use BitWasp\Bitcoin\Mnemonic\Bip39\Bip39SeedGenerator;
use BitWasp\Bitcoin\Mnemonic\MnemonicFactory;

use GuzzleHttp\Client;
use Illuminate\Support\Facades\Log;

class EthHelper
{
    public static function signVerify($message,$address,$signMessage)
    {
        
//         if ($signMessage == md5($message) && env("APP_DEBUG") == true){
//             return true;
//         }
        
        $signVerify = false;
        try {
            $client = new Client();
            $response = $client->post("http://127.0.0.1:9090/v1/bnb/signVerify",[
                'form_params' => [
                    'message' => $message,
                    'address' => $address,
                    'sign_message' => $signMessage,
                ]
            ]);
            $result = json_decode($response->getBody()->getContents(),true);
            Log::channel('signLog')->info('校验结果',$result);
            if (isset($result['code']) && $result['code']==200 && $result['data']['valid'] == true){
                $signVerify = $result['data']['valid'];
            }
        }catch (\Exception $e){
//             Log::channel('signLog')->info('校验结果'.$e->getMessage());
        }
        return $signVerify;
    }
    
    /**
     * 创建随机助记词
     * @return string
     * @throws \BitWasp\Bitcoin\Exceptions\RandomBytesFailure
     */
    public function createMnemonic(){
        $random = new Random();
        // 生成随机数(initial entropy)
        $entropy = $random->bytes(Bip39Mnemonic::MIN_ENTROPY_BYTE_LEN);
        $bip39 = MnemonicFactory::bip39();
        // 通过随机数生成助记词
//        $bip39->entropyToMnemonic()
//
        return $bip39->entropyToMnemonic($entropy);
    }

    /**
     * 根据助记词获取种子
     * @param $mnemonic  string 助记词
     * @return array
     * @throws \Exception
     */
    public function getSpeedByMnemonic($mnemonic){
        $seedGenerator = new Bip39SeedGenerator();
        $seed = $seedGenerator->getSeed($mnemonic);
        $hdFactory = new HierarchicalKeyFactory();
        $master = $hdFactory->fromEntropy($seed);
        $hardened = $master->derivePath("44'/60'/0'/0/0");
        $privateKey = $hardened->getPrivateKey()->getHex();
        return [
            'seed' => $seed->getHex(),
            'mnemonic' => $mnemonic,
            'privateKey' => $privateKey
        ];
    }

    /**
     * 导出私钥和公钥
     * @param $seed
     * @return array
     * @throws \Exception
     */
    public function importKey($seed){
        $hdFactory = new HierarchicalKeyFactory();
        $master = $hdFactory->fromEntropy($seed);
        $hardened = $master->derivePath("44'/60'/0'/0/0");
        $publicKey = $hardened->getPublicKey()->getHex();
        $privateKey = $hardened->getPrivateKey()->getHex();

        $bip39 = MnemonicFactory::bip39();
        return compact('publicKey','privateKey');
    }
}
