<?php

namespace App\Admin\Repositories;

use App\Models\MainCurrency as Model;
use Dcat\Admin\Repositories\EloquentRepository;

class MainCurrency extends EloquentRepository
{
    /**
     * Model.
     *
     * @var string
     */
    protected $eloquentClass = Model::class;
}
