<?php

namespace App\Admin\Controllers;

use Illuminate\Routing\Controller;
use Encore\Admin\Grid;
use Encore\Admin\Layout\Content;
use App\Models\Coin;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Encore\Admin\Widgets\Table;
use Carbon\CarbonPeriod;
use Carbon\CarbonInterface;


class CoinController extends Controller
{
    public function __construct(private Content $content)
    {
        $this->content = $content->title('Articles');
    }

    public function index()
    {
        $model = (new Coin())
            ->setConnection('mysql_prod');
        $grid = new Grid($model);
        $q = $grid->model()
                  ->where('google_alerts', 1)
                  ->withCount([
                      'googleAlertsNews as googleAlertsNewsCountLastWeek' => function (Builder $query) {
                          $query->where('news_published_at', '>', (string) Carbon::now()->subDay());
                      },
                  ])
                  ->withCount('googleAlertsNews as googleAlertsNewsCountAll');

        $datePeriods4 = $this->getDatePeriods(4);
        $this->addNewsCountByPeriodsInQuery($q, $datePeriods4, 4);

        $datePeriods24 = $this->getDatePeriods(24);
        $this->addNewsCountByPeriodsInQuery($q, $datePeriods24, 24);

        $grid->column('id', 'Id')->sortable();
        $grid->column('name', 'Name')->sortable()
             ->expand(function (Coin $model) use ($datePeriods24, $datePeriods4) {
                 $tableData24 = $model->getTableData($datePeriods24, 24);
                 $tableData4 = $model->getTableData($datePeriods4, 4);

                 return new Table(['periodStart', 'periodEnd', 'newsCount'], array_merge($tableData24, $tableData4));
             });

        $grid->column('googleAlertsNewsCountLastWeek', 'News Last Week')->sortable();
        $grid->column('googleAlertsNewsCountAll', 'News All Time')->sortable();

        return $this->content->description(trans('admin.list'))
                             ->body($grid);
    }

    private function addNewsCountByPeriodsInQuery($q, $datePeriods, $stepHours): void
    {
        foreach ($datePeriods as $period) {
            $q->withCount([
                "googleAlertsNews as " . Coin::getK($period->getStartDate(), $stepHours) => function (Builder $query
                ) use (
                    $period
                ) {
                    $query->where('news_published_at', '>=', (string) $period->getStartDate());
                    $query->where('news_published_at', '<=', (string) $period->getEndDate());
                },
            ]);
        }
    }

    /**
     * @return array<int, CarbonPeriod>
     */
    private function getDatePeriods(int $stepHours): array
    {
        $datePeriods = [];
        $startBig = Carbon::now()->subWeeks(2)->startOfDay()->addSecond(1);
        $endBig = Carbon::now()->endOfDay()->addSecond(1);
        $date = clone $endBig;
        while ($date->gte($startBig)) {
            $startSmall = $date->clone()->subHours($stepHours);
            $endSmall = $date->clone();
            if ($startSmall->gt(Carbon::now())) {
                $date->subHours($stepHours);
                continue;
            }

            $datePeriods[] = CarbonPeriod::create($startSmall, $endSmall->subSecond());
            $date->subHours($stepHours);
        }

        return $datePeriods;
    }

}
