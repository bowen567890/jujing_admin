<?php

namespace App\Admin\Repositories;

use App\Models\DynamicRate as Model;
use Dcat\Admin\Repositories\EloquentRepository;

class DynamicRate extends EloquentRepository
{
    /**
     * Model.
     *
     * @var string
     */
    protected $eloquentClass = Model::class;
}
