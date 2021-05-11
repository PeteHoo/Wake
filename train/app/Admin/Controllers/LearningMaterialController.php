<?php

namespace App\Admin\Controllers;

use App\Admin\Repositories\LearningMaterial;
use App\Models\Industry;
use App\Models\Mechanism;
use App\Models\Occupation;
use App\Utils\Constants;
use Dcat\Admin\Admin;
use Dcat\Admin\Form;
use Dcat\Admin\Grid;
use Dcat\Admin\Layout\Content;
use Dcat\Admin\Show;
use Dcat\Admin\Http\Controllers\AdminController;

class LearningMaterialController extends AdminController
{

    public function index(Content $content)
    {
        return $content
            ->title($this->title())
            ->body($this->grid());
    }

    /**
     * Make a grid builder.
     *
     * @return Grid
     */

    protected function grid()
    {
        return Grid::make(new LearningMaterial(), function (Grid $grid) {
            if (Admin::user()->isRole('mechanism')) {
                $grid->model()->where('mechanism_id', Admin::user()->id);
            }
            $grid->column('id')->sortable();
            $grid->column('title');
            $grid->column('description');
            $grid->column('mechanism_id')->display(function ($mechanism_id) {
                return Mechanism::getMechanismDataDetail($mechanism_id);
            });
            $grid->column('industry_id')->display(function ($industry_id) {
                return Industry::getIndustryDataDetail($industry_id);
            });
            $grid->column('occupation_id')->display(function ($occupation_id) {
                return Occupation::getOccupationDataDetail($occupation_id);
            });

            $grid->column('picture')->image();
            $grid->column('is_open')->if(function ($column) {
                if ($this->mechanism_id != Admin::user()->id) {
                    $column->display(function ($status) {
                        return Constants::getStatusType($status);
                    });
                } else {
                    $column->switch();
                }
            });

            if (Admin::user()->isRole('mechanism')) {
                $grid->column('status')->help('需要平台审核')->display(function ($status) {
                    return Constants::getStatusType($status);
                });
            } elseif (Admin::user()->isRole('administrator')) {
                $grid->column('status')->switch();
            }
            $grid->column('sort')->editable();
//            $grid->column('created_at');
//            $grid->column('updated_at')->sortable();
            $grid->actions(function ($actions){
                if (Admin::user()->isRole('administrator')) {
                    if($actions->row->mechanism_id!=1){
                        $actions->disableEdit();
                    }
                }
            });
            $grid->filter(function (Grid\Filter $filter) {
                $filter->equal('mechanism_id')->select(Mechanism::getMechanismData());

            });
        });
    }

    /**
     * Make a show builder.
     *
     * @param mixed $id
     *
     * @return Show
     */
    protected function detail($id)
    {
        return Show::make($id, new LearningMaterial(), function (Show $show) {
            $show->field('id');
            $show->field('title');
            $show->field('description');
            $show->field('mechanism_id')->as(function ($mechanism_id) {
                return Mechanism::getMechanismDataDetail($mechanism_id);
            });
            $show->field('industry_id')->as(function ($industry_id) {
                return Industry::getIndustryDataDetail($industry_id);
            });
            $show->field('occupation_id')->as(function ($occupation_id) {
                return Occupation::getOccupationDataDetail($occupation_id);
            });
            $show->field('picture')->image();
            $show->field('is_open')->as(function ($status) {
                return Constants::getStatusType($status);
            });
            $show->field('status')->as(function ($status) {
                return Constants::getStatusType($status);
            });
            $show->field('sort');
            $show->field('created_at');
            $show->field('updated_at');
        });
    }

    /**
     * Make a form builder.
     *
     * @return Form
     */
    protected function form()
    {
        return Form::make(new LearningMaterial(), function (Form $form) {
            $form->display('id');
            $form->text('title');
            $form->textarea('description');
            if (Admin::user()->isRole('administrator')) {
                $form->select('mechanism_id')->options(Mechanism::getMechanismData())->required();
            } elseif (Admin::user()->isRole('mechanism')) {
                $form->hidden('mechanism_id')->default(Admin::user()->id);
            }
            $form->select('industry_id')->options(Industry::getIndustryData())->load('occupation_id', 'api-occupation')->required();
            $form->select('occupation_id')->required();
            $form->image('picture');  //可删除
            $form->switch('is_open');
            $form->hidden('status')->default(Constants::CLOSE);
            $form->number('sort');

            $form->display('created_at');
            $form->display('updated_at');
        });
    }
}
