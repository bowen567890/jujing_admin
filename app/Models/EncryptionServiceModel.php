<?php

namespace App\Models;

use Illuminate\Contracts\Encryption\Encrypter;

class EncryptionServiceModel
{
    protected $encrypter;
    
    public function __construct(Encrypter $encrypter)
    {
        $this->encrypter = $encrypter;
    }
    
    /**
     * 加密数据
     */
    public function encryptData($data)
    {
        return $this->encrypter->encrypt($data, false);
    }
    
    /**
     * 解密数据
     */
    public function decryptData($data)
    {
        return $this->encrypter->decrypt($data, false);
    }
    
}
