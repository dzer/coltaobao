<?php
namespace dzer\coltaobao\basic;
/**
 * 基础请求类
 *
 * @package dzer\coltaobao\basic
 * @author dzer <d20053140@gmail.com>
 * @version 2.0
 */
class Request
{
    /**
     * @var int cURL允许执行的最长秒数
     */
    private $readTimeout = 30;

    /**
     * @var int 在发起连接前等待的时间
     */
    private $connectTimeout = 10;

    /**
     * @var array 回调方法
     */
    public $callback = array();

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
        //抓取跳转后的页面
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION,1);

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

    public function curlMulti($urlList, $maxRequestNum = '')
    {
        if (empty($urlList)) {
            return false;
        }
        if ($maxRequestNum == '') {
            $maxRequestNum = Config::get('maxRequestNum');
        }
        $mh = curl_multi_init(); //返回一个新cURL批处理句柄
        for ($i = 0; $i < count($urlList) && $i < $maxRequestNum; $i++) {
            if (!isset($urlList[$i])) {
                continue;
            }
            $ch = curl_init();  //初始化单个cURL会话
            curl_setopt($ch, CURLOPT_HEADER, 0);
            curl_setopt($ch, CURLOPT_URL, $urlList[$i]);
            //curl_setopt($ch, CURLOPT_COOKIE, self::$user_cookie);
            curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 6.1; WOW64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/55.0.2883.87 Safari/537.36');
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);
            curl_setopt($ch, CURLOPT_TIMEOUT, $this->readTimeout);
            $requestMap[$i] = $ch;
            curl_multi_add_handle($mh, $ch);  //向curl批处理会话中添加单独的curl句柄
        }
        $rs = array();
        do {
            //运行当前 cURL 句柄的子连接
            while (($cme = curl_multi_exec($mh, $active)) == CURLM_CALL_MULTI_PERFORM) ;
            if ($cme != CURLM_OK) {
                break;
            }
            //获取当前解析的cURL的相关传输信息
            while ($done = curl_multi_info_read($mh)) {
                $info = curl_getinfo($done['handle']);
                $tmp_result = curl_multi_getcontent($done['handle']);
                $error = curl_error($done['handle']);

                if ($tmp_result === false) {
                    Log::getInstance()->error("请求失败！" . $error . "\r\n"  . var_export($info, true));
                    continue;
                }
                if (!empty($this->callback)) {
                    $tmp_result = call_user_func($this->callback, $tmp_result, $info['url']);
                }
                $rs[] = $tmp_result;
                //保证同时有$max_size个请求在处理
                if (isset($urlList[$i]) && $i < count($urlList)) {
                    $ch = curl_init();
                    curl_setopt($ch, CURLOPT_HEADER, 0);
                    curl_setopt($ch, CURLOPT_URL, $urlList[$i]);
                    //curl_setopt($ch, CURLOPT_COOKIE, self::$user_cookie);
                    curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 6.1; WOW64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/55.0.2883.87 Safari/537.36');
                    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
                    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
                    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);
                    curl_setopt($ch, CURLOPT_TIMEOUT, $this->readTimeout);
                    $requestMap[$i] = $ch;
                    curl_multi_add_handle($mh, $ch);
                    $i++;
                }

                curl_multi_remove_handle($mh, $done['handle']);
            }
            /*if ($active) {
                curl_multi_select($mh, 10);
            }*/
            //没有执行数据就会sleep，避免CPU过高
            if($active && curl_multi_select($mh) === -1){
                usleep(100);
            }
        } while ($active);

        curl_multi_close($mh);
        return $rs;
    }

}
