<?php

namespace App\Admin\Repositories;

use App\Models\InvestDestroy as Model;
use Dcat\Admin\Repositories\EloquentRepository;

class InvestDestroy extends EloquentRepository
{
    /**
     * Model.
     *
     * @var string
     */
    protected $eloquentClass = Model::class;
}
