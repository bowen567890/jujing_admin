<?php

namespace App\Admin\Repositories;

use App\Models\Redemption as Model;
use Dcat\Admin\Repositories\EloquentRepository;

class Redemption extends EloquentRepository
{
    /**
     * Model.
     *
     * @var string
     */
    protected $eloquentClass = Model::class;
}
