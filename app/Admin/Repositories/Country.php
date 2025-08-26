<?php

namespace App\Admin\Repositories;

use App\Models\Country as Model;
use Dcat\Admin\Repositories\EloquentRepository;

class Country extends EloquentRepository
{
    /**
     * Model.
     *
     * @var string
     */
    protected $eloquentClass = Model::class;
}
