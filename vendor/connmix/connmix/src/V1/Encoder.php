<?php

namespace Connmix\V1;

class Encoder
{

    /**
     * @param $data
     * @return string
     */
    public function encode($data): string
    {
        return json_encode($data);
    }

}
