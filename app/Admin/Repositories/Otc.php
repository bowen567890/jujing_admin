<?php

namespace App\Admin\Repositories;

use App\Models\Otc as Model;
use Dcat\Admin\Repositories\EloquentRepository;

class Otc extends EloquentRepository
{
    /**
     * Model.
     *
     * @var string
     */
    protected $eloquentClass = Model::class;
}
