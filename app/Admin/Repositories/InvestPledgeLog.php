<?php

namespace App\Admin\Repositories;

use App\Models\InvestPledgeLog as Model;
use Dcat\Admin\Repositories\EloquentRepository;

class InvestPledgeLog extends EloquentRepository
{
    /**
     * Model.
     *
     * @var string
     */
    protected $eloquentClass = Model::class;
}
