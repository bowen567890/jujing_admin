<?php

namespace App\Admin\Repositories;

use App\Models\Delivery as Model;
use Dcat\Admin\Repositories\EloquentRepository;

class Delivery extends EloquentRepository
{
    /**
     * Model.
     *
     * @var string
     */
    protected $eloquentClass = Model::class;
}
