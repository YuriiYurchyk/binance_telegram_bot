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
        $this->addNewsCountByPeriodsInQuery($q, $datePeriods4);

        $datePeriods24 = $this->getDatePeriods(24);
        $this->addNewsCountByPeriodsInQuery($q, $datePeriods24);

        $grid->column('id', 'Id')->sortable();
        $grid->column('name', 'Name')->sortable()
             ->expand(function (Coin $model) use ($datePeriods4, $datePeriods24) {
                 $tableData = [];
                 $tableData[] = ['24 HOURS'];
                 foreach ($datePeriods24 as $period) {
                     $tableData[] = [
                         'periodStart' => (string) $period->getStartDate(),
                         'periodEnd' => (string) $period->getEndDate(),
                         'newsCount' => $model->getAttribute($model->getK($period->getStartDate(), 24)),
                     ];
                 }

                 $tableData[] = ['4 HOURS'];
                 foreach ($datePeriods4 as $period) {
                     $tableData[] = [
                         'periodStart' => (string) $period->getStartDate(),
                         'periodEnd' => (string) $period->getEndDate(),
                         'newsCount' => $model->getAttribute($model->getK($period->getStartDate(), 24)),
                     ];
                 }

                 return new Table(['periodStart', 'periodEnd', 'newsCount'], $tableData);
             });

        $grid->column('googleAlertsNewsCountLastWeek', 'News Last Week')->sortable();
        $grid->column('googleAlertsNewsCountAll', 'News All Time')->sortable();

        return $this->content->description(trans('admin.list'))
                             ->body($grid);
    }

    private function addNewsCountByPeriodsInQuery($q, $datePeriods)
    {
        foreach ($datePeriods as $period) {
            $q->withCount([
                "googleAlertsNews as " . $this->getK($period->getStartDate(), 24) => function (Builder $query) use (
                    $period
                ) {
                    $query->where('news_published_at', '>=', (string) $period->getStartDate());
                    $query->where('news_published_at', '<=', (string) $period->getEndDate());
                },
            ]);
        }
    }

    private function getK(Carbon|CarbonInterface $date, int $stepHours): string
    {
        return "K" . $date->getTimestamp() . 'stepHours' . $stepHours;
    }

    /**
     * @return array<int, CarbonPeriod>
     */
    public function getDatePeriods(int $stepHours): array
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
