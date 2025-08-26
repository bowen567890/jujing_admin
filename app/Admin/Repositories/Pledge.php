<?php

namespace App\Admin\Repositories;

use App\Models\Pledge as Model;
use Dcat\Admin\Repositories\EloquentRepository;

class Pledge extends EloquentRepository
{
    /**
     * Model.
     *
     * @var string
     */
    protected $eloquentClass = Model::class;
}
