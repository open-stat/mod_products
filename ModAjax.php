<?php
use Core2\Mod\Proxy;

require_once DOC_ROOT . "core2/inc/ajax.func.php";


/**
 * Class ModAjax
 */
class ModAjax extends ajaxFunc {


    /**
     * @param array $data
     * @return xajaxResponse
     * @throws Zend_Db_Adapter_Exception
     * @throws Exception
     */
    public function axSaveProducts(array $data): xajaxResponse {

        $fields = [
            'section'  => 'req',
            'category' => 'req',
            'title'    => 'req',
        ];

        if ($this->ajaxValidate($data, $fields)) {
            return $this->response;
        }



        $this->saveData($data);

        if (empty($this->error)) {
            $this->response->script("CoreUI.notice.create('Сохранено');");
            $this->response->script("load('index.php?module=products');");
        }


        $this->done($data);
        return $this->response;
    }
}
