<?php


namespace App\Admin\Forms;


use App\Models\Industry;
use App\Models\Occupation;
use App\Models\TestQuestion;
use App\Utils\Constants;
use Dcat\Admin\Admin;
use Dcat\Admin\Widgets\Form;
use Dcat\EasyExcel\Excel;

/** excel导入授权
 * Class EmpowerExcelForm
 * @package App\Admin\Forms
 */
class TestQuestionExcelForm extends Form
{
    public static $js = [
        // js脚本不能直接包含初始化操作，否则页面刷新后无效
        '/js/testQuestionForm.js',
    ];

    public function render()
    {
        Admin::js(static::$js);
        return parent::render(); // TODO: Change the autogenerated stub
    }

    public function handle(array $input)
    {
        $mechanism_id=Admin::user()->id;
        $occupation_id=$input['occupation_id'];
        $final_data = array();
        try {
            foreach (Excel::import($input['file'])->disk('admin')->first()->toArray() as $k=> $v) {
                $type=Constants::getQuestionKey($v['类型']);
                if(!in_array($type,[Constants::SINGLE_CHOICE,Constants::JUDGMENT])){
                    return $this->response()->error('第'.($k+1).'行类型不正确');
                }
                if(strlen($v['描述'])>100){
                    return $this->response()->error('第'.($k+1).'行描述超过100字');
                }
                $data=array();
                $data['type']=$type;
                $data['description']=$v['描述'];
                if($type==Constants::SINGLE_CHOICE){
                    if(!($v['选项A']&&$v['选项B']&&$v['选项C']&&$v['选项D'])){
                        return $this->response()->error('第'.($k+1).'行选项格式不正确');
                    }
                    $answer_single_option=array();
                    $answer_single_option['A']=$v['选项A'];
                    $answer_single_option['B']=$v['选项B'];
                    $answer_single_option['C']=$v['选项C'];
                    $answer_single_option['D']=$v['选项D'];
                    $data['answer_single_option']=json_encode($answer_single_option);
                    $data['true_single_answer']=$v['答案'];
                    $data['true_judgment_answer']='';
                }elseif($type==Constants::JUDGMENT){
                    $data['answer_single_option']='';
                    $data['true_single_answer']='';
                    $data['true_judgment_answer']=$v['答案'];
                }
                $data['mechanism_id']=$mechanism_id;
                $data['occupation_id']=$occupation_id;
                $data['created_at']=dateNow();
                $data['updated_at']=dateNow();
                $final_data[] = $data;

            }
                TestQuestion::insert($final_data);
            //导入完成删除文件
            unlink(public_path('uploads') . '/' . $input['file']);

        } catch (\Exception $e) {
            return $this->response()->error('导入文件数据格式不正确');
        }
        return $this->response()->success('导入成功')->location('/test-question');
    }

    /**
     * Build a form here.
     */
    public function form()
    {
        //导入excel表单
        $this->divider();
        $this->html('<a href="#" onclick="downloadTestQuestionExcel()"><img style="border:none;" src="' . config('app.url') . '/image/downloads.png"> 模板下载 </a>');
        $this->select('occupation_id')->options(Occupation::getOccupationData())->required();
        $this->file('file');
    }
}
