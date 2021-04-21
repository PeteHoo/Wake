<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2021/4/17
 * Time: 15:56
 */

namespace App\Http\Requests;


class LearningMaterialRequest extends BaseRequest
{
    public function rules()
    {
        switch ($this->route()->uri) {
            case 'api/learning-material/list':
                return [
                    'perPage'=>['integer'],
                    'page'=>['integer'],
                ];
                break;
            case 'api/learning-material/detail':
                return [
                    'id'=>['required'],
                ];
                break;
            case 'api/learning-material/search':
                return [
                    'search_word'=>['required'],
                ];
                break;
            default;return [];
        }
    }

    public function attributes()
    {
        return [
            'perPage'=>'一页几个',
            'page'=>'第几页'
        ];
    }

    public function messages()
    {
        return [];
    }
}