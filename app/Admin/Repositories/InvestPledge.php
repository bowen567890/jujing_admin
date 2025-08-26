<?php

namespace App\Admin\Repositories;

use App\Models\InvestPledge as Model;
use Dcat\Admin\Repositories\EloquentRepository;

class InvestPledge extends EloquentRepository
{
    /**
     * Model.
     *
     * @var string
     */
    protected $eloquentClass = Model::class;
}
