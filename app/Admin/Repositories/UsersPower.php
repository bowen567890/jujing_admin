<?php

namespace App\Admin\Repositories;

use App\Models\UsersPower as Model;
use Dcat\Admin\Repositories\EloquentRepository;

class UsersPower extends EloquentRepository
{
    /**
     * Model.
     *
     * @var string
     */
    protected $eloquentClass = Model::class;
}
