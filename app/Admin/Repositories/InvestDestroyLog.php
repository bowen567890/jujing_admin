<?php

namespace App\Admin\Repositories;

use App\Models\InvestDestroyLog as Model;
use Dcat\Admin\Repositories\EloquentRepository;

class InvestDestroyLog extends EloquentRepository
{
    /**
     * Model.
     *
     * @var string
     */
    protected $eloquentClass = Model::class;
}
