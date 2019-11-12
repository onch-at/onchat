<?php
namespace hypergo\ai;

use hypergo\http\HttpPost;
use hypergo\ai\IAI;

class TencentAI extends HttpPost implements IAI {
    public $appid; //app id
    public $appkey; //app key
    public $url = "https://api.ai.qq.com/fcgi-bin/nlp/nlp_textchat"; //api url

    public function __construct($appid = "2122308103", $appkey = "o054lK0TfpstSBfk") {
        $this->setAppid($appid);
        $this->setAppkey($appkey);
    }

    public function dialog($question) {
        if (str_replace(" ", "", $question) == "") return false;
        $params = [
            "app_id" => $this->appid, 
            "session" => "10000", 
            "question" => $question, 
            "time_stamp" => strval(time()), 
            "nonce_str" => strval(rand()), 
            "sign" => "", 
        ]; 
        $params["sign"] = $this->getReqSign($params, $this->appkey); 
        // 执行API调用 
        $response = json_decode($this->doHttpPost($this->url, $params)); 

        if ($response->ret !== 0) return false;

        return $response->data->answer;
    }

    public function getAppid() {
        return $this->appid;
    }

    public function setAppid($appid) {
        $this->appid = $appid;
    }

    public function getAppkey() {
        return $this->appkey;
    }

    public function setAppkey($appkey) {
        $this->appkey = $appkey;
    }

    // getReqSign ：根据 接口请求参数 和 应用密钥 计算 请求签名 
    // 参数说明 
    // - $params：接口请求参数（特别注意：不同的接口，参数对一般不一样，请以具体接口要求为准） 
    // - $appkey：应用密钥 
    // 返回数据
    // - 签名结果 
    public function getReqSign($params /* 关联数组 */, $appkey /* 字符串*/) { 
        // 1. 字典升序排序 
        ksort($params); 
        // 2. 拼按URL键值对 
        $str = ""; 
        foreach ($params as $key => $value) { 
            if ($value !== "") $str .= $key . "=" . urlencode($value) . "&";
        } 
        // 3. 拼接app_key 
        $str .= "app_key=" . $appkey; 
        // 4. MD5运算+转换大写，得到请求签名 
        $sign = strtoupper(md5($str)); 
        return $sign; 
    }
}
?>