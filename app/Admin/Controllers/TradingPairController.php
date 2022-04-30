<?php

namespace App\Admin\Controllers;

use Illuminate\Routing\Controller;
use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Show;
use \App\Models\TradingPair;
use Encore\Admin\Layout\Content;
use App\Models\ParsedNews;

class TradingPairController extends Controller
{
    protected $title = 'Title';

    protected $description = [
        //        'index'  => 'Index',
        //        'show'   => 'Show',
        //        'edit'   => 'Edit',
        //        'create' => 'Create',
    ];

    protected function title()
    {
        return $this->title;
    }

    public function index(Content $content)
    {
        $grid = new Grid(new TradingPair());

        $grid->column('id', __('Id'));
        $grid->column('status', __('Status'));
        $grid->column('base_coin', __('Base coin'))->sortable();
        $grid->column('quote_coin', __('Quote coin'))->sortable();
        $grid->column('binance_added_at', __('Binance added at'))->editable('datetime');

        $grid->parsedNews()->display(function ($roles) {
            $roles = array_map(function ($role) {
                return "<a target='_blank' href=\"{$role['url']}\">{$role['url']}</a>";
            }, $roles);

            return join('&nbsp;', $roles);
        });

        $grid->column('created_at', __('Created at'))->hide();
        $grid->column('updated_at', __('Updated at'))->hide();

        $grid->filter(function (\Encore\Admin\Grid\Filter $filter) {
            $filter->disableIdFilter();

            $filter->like('base_coin', 'base_coin');
            $filter->like('quote_coin', 'quote_coin');


            $filter->where(function ($query) {
                switch ($this->input) {
                    case 'yes':
                        // custom complex query if the 'yes' option is selected
                        $query->has('parsedNews');
                        break;
                    case 'no':
                        $query->doesntHave('parsedNews');
                        break;
                }
            }, 'withNews', 'withNews')
                   ->radio([
                       '' => 'All',
                       'yes' => 'Only with news',
                       'no' => 'Only without news',
                   ]);
        });


        return $content
            ->title($this->title())
            ->description($this->description['index'] ?? trans('admin.list'))
            ->body($grid);
    }

    public function show($id, Content $content)
    {
        return $content
            ->title($this->title())
            ->description($this->description['show'] ?? trans('admin.show'))
            ->body($this->detail($id));
    }

    public function edit(TradingPair $tradingPair, Content $content)
    {
        return $content
            ->title($this->title())
            ->description($this->description['edit'] ?? trans('admin.edit'))
            ->body($this->form($tradingPair)->edit($tradingPair->id));
    }

    public function create(Content $content)
    {
        return $content
            ->title($this->title())
            ->description($this->description['create'] ?? trans('admin.create'))
            ->body($this->form());
    }

    public function update($id)
    {
        return $this->form()->update($id);
    }

    public function store()
    {
        return $this->form()->store();
    }

    public function destroy($id)
    {
        return $this->form()->destroy($id);
    }

    protected function detail($id)
    {
        $show = new Show(TradingPair::findOrFail($id));

        $show->field('id', __('Id'));
        $show->field('status', __('Status'));
        $show->field('binance_added_at', __('Binance added at'));
        $show->field('created_at', __('Created at'));
        $show->field('updated_at', __('Updated at'));
        $show->field('base_coin', __('Base coin'));
        $show->field('quote_coin', __('Quote coin'));

        return $show;
    }

    /**
     * Make a form builder.
     *
     * @return Form
     */
    protected function form(TradingPair $tradingPair = null)
    {
        $form = new Form(new TradingPair());

        $form->column(10, function (Form $form) {
            $form->switch('status', __('Status'));
            $form->datetime('binance_added_at', __('Binance added at'))->default(date('Y-m-d H:i:s'));
            $form->number('base_coin', __('Base coin'));
            $form->number('quote_coin', __('Quote coin'));
        });

        if ($tradingPair) {
            $news = $tradingPair->parsedNews;
            /** @var ParsedNews $item */

            $links = '';
            foreach ($news as $item) {
                $links .= $item->url . PHP_EOL;
            }

            $form->html($links);
        }

        return $form;
    }
}
