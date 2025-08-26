<?php

namespace App\Admin\Repositories;

use App\Models\BlackHole as Model;
use Dcat\Admin\Repositories\EloquentRepository;

class BlackHole extends EloquentRepository
{
    /**
     * Model.
     *
     * @var string
     */
    protected $eloquentClass = Model::class;
}
