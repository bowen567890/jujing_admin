<?php

namespace App\Admin\Repositories;

use App\Models\Coin as Model;
use Dcat\Admin\Repositories\EloquentRepository;

class Coin extends EloquentRepository
{
    /**
     * Model.
     *
     * @var string
     */
    protected $eloquentClass = Model::class;
}
