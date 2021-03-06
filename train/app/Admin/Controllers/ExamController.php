<?php

namespace App\Admin\Controllers;


use App\Admin\Repositories\Exam;
use App\Admin\Table\TestQuestionTable;
use App\Models\ExamDetail;
use App\Models\Industry;
use App\Models\Mechanism;
use App\Models\Occupation;
use App\Models\TestQuestion;
use App\Utils\Constants;
use Dcat\Admin\Admin;
use Dcat\Admin\Form;
use Dcat\Admin\Grid;
use Dcat\Admin\Show;
use Dcat\Admin\Http\Controllers\AdminController;

class ExamController extends AdminController
{

    /**
     * Make a grid builder.
     *
     * @return Grid
     */
    protected function grid()
    {
        return Grid::make(new Exam(), function (Grid $grid) {
            $grid->model()->with('examDetail')->orderBy('id','DESC');
            if (Admin::user()->isRole('mechanism')) {
                $grid->model()->where('mechanism_id', Admin::user()->id);
            }
            $grid->column('id')->sortable();
            $grid->column('name');
            $grid->column('mechanism_id')->display(function ($mechanism_id) {
                return Mechanism::getMechanismDataDetail($mechanism_id);
            });
            $grid->column('industry_id')->display(function ($industry_id) {
                return Industry::getIndustryDataDetail($industry_id);
            });
            $grid->column('occupation_id')->display(function ($occupation_id) {
                return Occupation::getOccupationDataDetail($occupation_id);
            });
            $grid->column('score');
            $grid->column('question_count');
            $grid->actions(function ($actions){
                if (Admin::user()->isRole('administrator')) {
                    if($actions->row->mechanism_id!=1){
                        $actions->disableEdit();
                    }
                }
            });
            if (Admin::user()->isRole('mechanism')) {
                $grid->column('status')->help('需要平台审核')->display(function ($status) {
                    return Constants::getStatusType($status);
                });
            } elseif (Admin::user()->isRole('administrator')) {
                $grid->column('status')->switch();
            }
            if (Admin::user()->isRole('administrator')) {
                $grid->disableEditButton();
                $grid->disableCreateButton();
                $grid->disableQuickEditButton();
            }
            $grid->actions(function (Grid\Displayers\Actions $actions) {
                $actions->append('<a href="exam-detail?exam_id=' . $actions->row->id . '"><i class="fa fa-eye">题目详情</i></a>');

                    if (Admin::user()->isRole('administrator')) {
                        $actions->disableEdit();
                    }

            });
            $grid->filter(function (Grid\Filter $filter) {
                if (Admin::user()->isRole('administrator')) {
                    $filter->equal('mechanism_id')->select(Mechanism::getMechanismData());
                }
                $filter->equal('industry_id')->select(Industry::getIndustryData())->load('occupation_id', 'api-occupation');
                $filter->equal('occupation_id');
                $filter->like('name');
                $filter->equal('examDetail.question_id','题目id');

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
        return Show::make($id, new Exam(), function (Show $show) {
            $show->disableEditButton();
            $show->row(function (Show\Row $show) {
                $show->width(12)->field('name');
            });
            $show->row(function (Show\Row $show) {
                $show->width(4)->field('mechanism_id')->as(function ($mechanism_id) {
                    return Mechanism::getMechanismDataDetail($mechanism_id);
                });
                $show->width(4)->field('industry_id')->as(function ($industry_id) {
                    return Industry::getIndustryDataDetail($industry_id);
                });
                $show->width(4)->field('occupation_id')->as(function ($occupation_id) {
                    return Occupation::getOccupationDataDetail($occupation_id);
                });
            });
            $show->row(function (Show\Row $show) {
                $show->width(4)->field('score');
                $show->width(4)->field('question_count');
                $show->width(4)->field('status')->as(function ($status) {
                    return Constants::getStatusType($status);
                });
            });
        });
    }

    /**
     * Make a form builder.
     *
     * @return Form
     */
    protected function form()
    {
        Admin::script(
            <<<JS
$( "select[name='occupation_id']").on('change', function () {
    console.log('文件发生变动', this.value);
    var single=$('template.content')[0].innerHTML;
    var single_start=single.indexOf("data-url=");
    var single_end=single.indexOf("style=");
    var single_str=single.substring(single_start+10,single_end-2);
    var single_change=single_str+"&amp;occupation_id="+this.value;
    var single_result=single.replace(single_str,single_change);
    $('template.content')[0].innerHTML=single_result;

     var jugement=$('template.content')[1].innerHTML;
     var jugement_start=jugement.indexOf("data-url=");
     var jugement_end=jugement.indexOf("style=");
     var jugement_str=jugement.substring(jugement_start+10,jugement_end-2);
     var jugement_change=jugement_str+"&amp;occupation_id="+this.value;
     var jugement_result=jugement.replace(jugement_str,jugement_change);
     $('template.content')[1].innerHTML=jugement_result;
});
JS
        );
        return Form::make(new Exam(), function (Form $form) {
            $form->display('id');
            $form->text('name')->required();
            $form->hidden('mechanism_id')->value(Admin::user()->id);
            $form->select('industry_id')->options(Industry::getIndustryData())->load('occupation_id', 'api-occupation');
            $form->select('occupation_id');
            $form->hidden('status')->default(Constants::CLOSE);
            $form->display('created_at');
            $form->display('updated_at');
            $form->hidden('score');
            $form->hidden('question_count');
            $form->multipleSelectTable('single_item')
                ->title('选择题')
                ->dialogWidth('50%')// 弹窗宽度，默认 800px
                ->from(TestQuestionTable::make(['type' => Constants::SINGLE_CHOICE, 'mechanism_id' => Admin::user()->id, 'occupation_id' => $form->hidden_occupation_id]))// 设置渲染类实例，并传递自定义参数
                ->model(TestQuestion::class, 'id', 'name')->required()->savingArray(); // 设置编辑数据显示
            $form->multipleSelectTable('judgment_item')
                ->title('判断题')
                ->dialogWidth('50%')// 弹窗宽度，默认 800px
                ->from(TestQuestionTable::make(['type' => Constants::JUDGMENT, 'mechanism_id' => Admin::user()->id, 'occupation_id' => $form->model()->hidden_occupation_id]))// 设置渲染类实例，并传递自定义参数
                ->model(TestQuestion::class, 'id', 'name')->required()->savingArray(); // 设置编辑数据显示
            $form->saving(function (Form $form) {
                if($form->occupation_id){
                    $occupation = Occupation::find($form->occupation_id);
                    if ($occupation) {
                        $form->score = $occupation->choice_question_num * $occupation->choice_question_score + $occupation->judgment_question_num * $occupation->judgment_question_score;
                        $form->question_count = $occupation->choice_question_num + $occupation->judgment_question_num;
                    } else {
                        $form->score = 0;
                        $form->question_count = 0;
                    }
                }
                if (Admin::user()->isRole('mechanism')) {
                    $form->status=Constants::CLOSE;
                }
            });
            $form->saved(function ($form, $result) {
                if($form->isEditing()){
                    $result=$form->getKey();
                }
                if ($single_item = $form->single_item) {
                    $single_item = explode(',', $single_item);
                    $single_question_num = Occupation::find($form->occupation_id)->choice_question_num;
                    if ($single_question_num < count($single_item)) {
                        return $form->response()
                            ->error('选择题数量已经超出');
                    }
                    ExamDetail::where('exam_id', $result)->where('type', Constants::SINGLE_CHOICE)->delete();
                    foreach ($single_item as $k => $v) {
                        $examDetail = new ExamDetail();
                        $examDetail->exam_id = $result;
                        $examDetail->question_id = $v;
                        $examDetail->sort = 0;
                        $examDetail->type = Constants::SINGLE_CHOICE;
                        $examDetail->save();
                    }
                }
                if ($judgment_item = $form->judgment_item) {
                    $judgment_item = explode(',', $judgment_item);
                    $judgment_question_num = Occupation::find($form->occupation_id)->choice_question_num;
                    if ($judgment_question_num < count($judgment_item)) {
                        return $form->response()
                            ->error('判断题数量已经超出');
                    }
                    ExamDetail::where('exam_id', $result)->where('type', Constants::JUDGMENT)->delete();
                    foreach ($judgment_item as $k => $v) {
                        $examDetail = new ExamDetail();
                        $examDetail->exam_id = $result;
                        $examDetail->question_id = $v;
                        $examDetail->sort = 0;
                        $examDetail->type = Constants::JUDGMENT;
                        $examDetail->save();
                    }
                }
            });
            $form->deleted(function (Form $form, $result) {
                // 通过 $result 可以判断数据是否删除成功
                if (!$result) {
                    return $form->response()->error('数据删除失败');
                }
                // 获取待删除行数据，这里获取的是一个二维数组
                $data = $form->model()->toArray();
                foreach ($data as $k=>$v){
                    ExamDetail::where('exam_id', $v['id'])->delete();
                }
            });
        });
    }
}
