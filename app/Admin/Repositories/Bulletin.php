<?php

namespace App\Admin\Repositories;

use App\Models\Bulletin as Model;
use Dcat\Admin\Repositories\EloquentRepository;

class Bulletin extends EloquentRepository
{
    protected $eloquentClass = Model::class;
}
