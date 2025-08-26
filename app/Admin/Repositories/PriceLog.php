<?php

namespace App\Admin\Repositories;

use App\Models\PriceLog as Model;
use Dcat\Admin\Repositories\EloquentRepository;

class PriceLog extends EloquentRepository
{
    /**
     * Model.
     *
     * @var string
     */
    protected $eloquentClass = Model::class;
}
