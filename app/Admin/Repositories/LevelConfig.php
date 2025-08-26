<?php

namespace App\Admin\Repositories;

use App\Models\LevelConfig as Model;
use Dcat\Admin\Repositories\EloquentRepository;

class LevelConfig extends EloquentRepository
{
    /**
     * Model.
     *
     * @var string
     */
    protected $eloquentClass = Model::class;
}
