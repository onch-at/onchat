<?php
namespace hypergo\ai;

use hypergo\http\HttpPost;
use hypergo\ai\IAI;

class TuringAI extends HttpPost implements IAI {
    public $apikey;
    public $url = "http://openapi.tuling123.com/openapi/api/v2";

    public function __construct($apikey = "050497f06f3c4659b6093b02edda1423") {
        $this->setApikey($apikey);
    }

    public function dialog($question) {
        if (str_replace(" ", "", $question) == "") return false;
        $params = [
            "reqType" => 0, //0-文本(默认)、1-图片、2-音频,
            "perception" => [
                "inputText" => [ "text" => $question ],
                // "inputImage" => [ "url" => "url" ],
                // "selfInfo" => [ "location" => [ "city" => "city", "province" => "province", "street" => "street" ] ]
            ],
            "userInfo" => [
                "apiKey" => $this->getApikey(),
                "userId" => "050497f06f3c4659b6093b02edda1423"
            ]
        ];

        $response = json_decode($this->doHttpPost($this->url, $params, "json"));

        if ($response->intent->code == 4003) return false;

        return $response->results[0]->values->text;
    }

    public function getApikey() {
        return $this->apikey;
    }

    public function setApikey($apikey) {
        $this->apikey = $apikey;
    }
}
?>