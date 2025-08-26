<?php

namespace App\Admin\Repositories;

use App\Models\Invest as Model;
use Dcat\Admin\Repositories\EloquentRepository;

class Invest extends EloquentRepository
{
    /**
     * Model.
     *
     * @var string
     */
    protected $eloquentClass = Model::class;
}
