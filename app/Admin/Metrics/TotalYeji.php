<?php

namespace App\Admin\Metrics;

use App\Models\User;
use App\Models\PowerOrder;
use Dcat\Admin\Widgets\Metrics\Card;
use Illuminate\Contracts\Support\Renderable;
use Illuminate\Http\Request;

class TotalYeji extends Card
{
    /**
     * 卡片底部内容.
     *
     * @var string|Renderable|\Closure
     */
    protected $footer;

    protected $height = 100;

    protected $chartMarginTop = 10;

    /**
     * 初始化卡片.
     */
    protected function init()
    {
        parent::init();
        $this->title('业绩总量');
    }

    /**
     * 处理请求.
     *
     * @param Request $request
     *
     * @return void
     */
    public function handle(Request $request)
    {
        $num = User::query()->sum('self_yeji');
        $num = @bcadd($num, '0', 2);
        $this->content($num);
    }

    /**
     * 渲染卡片内容.
     *
     * @return string
     */
    public function renderContent()
    {
        $content = parent::renderContent();

        return <<<HTML
<div class="d-flex justify-content-between align-items-center mt-1" style="margin-bottom: 2px">
    <h3 class="ml-1 font-lg-1">{$content}</h3>
</div>
HTML;
    }

    /**
     * 渲染卡片底部内容.
     *
     * @return string
     */
    public function renderFooter()
    {
        return $this->toString($this->footer);
    }
}
