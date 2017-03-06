<?php
namespace dzer\coltaobao\basic;
/**
 * 基础请求类
 *
 * @author dzer <d20053140@gmail.com>
 * @version 2.0
 */
class Request
{
    //cURL允许执行的最长秒数
    protected $readTimeout = 30;
    //在发起连接前等待的时间
    protected $connectTimeout = 10;

    /**
     * 获取网页内容
     * @param string $url 请求地址
     * @return string
     */
    public function get($url)
    {
        return file_get_contents($url);
    }

    /**
     * curl请求方法
     * 支持http和https请求
     * @param string $url 请求地址
     * @param array $headerFields 请求头参数
     * @param array $postFields 请求体参数
     * @return mixed
     * @throws \Exception
     */
    public function curl($url, $headerFields = null, $postFields = null)
    {
        $ch = curl_init();
        //请求url地址
        curl_setopt($ch, CURLOPT_URL, $url);
        //HTTP状态码
        curl_setopt($ch, CURLOPT_FAILONERROR, false);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        //设置cURL允许执行的最长秒数
        if ($this->readTimeout) {
            curl_setopt($ch, CURLOPT_TIMEOUT, $this->readTimeout);
        }
        //尝试连接等待时间
        if ($this->connectTimeout) {
            curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $this->connectTimeout);
        }
        //https 请求(当请求https的数据时，会要求证书，这时候，加上下面这两个参数，规避ssl的证书检查)
        if (strlen($url) > 5 && strtolower(substr($url, 0, 5)) == "https") {
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        }

        if (is_array($postFields) && 0 < count($postFields)) {
            $postBodyString = "";
            $postMultipart = false;
            foreach ($postFields as $k => $v) {
                if ("@" != substr($v, 0, 1)) {//判断是不是文件上传
                    $postBodyString .= "$k=" . urlencode($v) . "&";
                } else {
                    //文件上传用multipart/form-data，否则用www-form-urlencoded
                    $postMultipart = true;
                }
            }
            unset($k, $v);
            curl_setopt($ch, CURLOPT_POST, true);
            if ($postMultipart) {
                curl_setopt($ch, CURLOPT_POSTFIELDS, $postFields);
                curl_setopt($ch, CURLOPT_HTTPHEADER, $headerFields);
            } else {
                $contentType = "content-type: application/x-www-form-urlencoded; charset=UTF-8";
                array_push($headerFields, $contentType);
                curl_setopt($ch, CURLOPT_HTTPHEADER, $headerFields);
                curl_setopt($ch, CURLOPT_POSTFIELDS, substr($postBodyString, 0, -1));
            }
        }
        $response = curl_exec($ch);
        if (curl_errno($ch)) {
            throw new \Exception(curl_error($ch), 0);
        } else {
            $httpStatusCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            if (200 !== $httpStatusCode) {
                throw new \Exception($response, $httpStatusCode);
            }
        }
        curl_close($ch);
        return $response;
    }

}
