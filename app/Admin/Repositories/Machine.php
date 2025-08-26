<?php

namespace App\Admin\Repositories;

use App\Models\Machine as Model;
use Dcat\Admin\Repositories\EloquentRepository;

class Machine extends EloquentRepository
{
    /**
     * Model.
     *
     * @var string
     */
    protected $eloquentClass = Model::class;
}
