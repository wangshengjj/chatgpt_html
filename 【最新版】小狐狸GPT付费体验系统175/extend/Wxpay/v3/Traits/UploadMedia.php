<?php
namespace Wxpay\v3\Traits;

trait UploadMedia
{
    /**
     * uploadImg 图片上传
     * @return array
     * @throws WxException
     */
    public function uploadImage($imgpath)
    {
        $url = self::WXAPIHOST . '/merchant/media/upload';

        $meta['filename'] = pathinfo($imgpath)['basename'];
        $meta['sha256'] = hash_file('sha256', $imgpath);
        $json_meta = json_encode($meta);
        $token = $this->makeHeaderToken($url, $json_meta);

        // 获取图片MIME类型
        $fi = new \finfo(FILEINFO_MIME_TYPE);
        $mime_type = $fi->file($imgpath);
        $boundary = uniqid(); //分割符号
        // 请求头
        $header[] = 'User-Agent:' . $_SERVER['HTTP_USER_AGENT'];
        $header[] = 'Accept:application/json';
        $header[] = 'Authorization:WECHATPAY2-SHA256-RSA2048 ' . $token;
        $header[] = 'Content-Type:multipart/form-data;boundary=' . $boundary;

        $boundaryStr = "--{$boundary}\r\n";
        $out = $boundaryStr;
        $out .= 'Content-Disposition: form-data; name="meta"' . "\r\n";
        $out .= 'Content-Type: application/json' . "\r\n";
        $out .= "\r\n";
        $out .= $json_meta . "\r\n";
        $out .= $boundaryStr;
        $out .= 'Content-Disposition: form-data; name="file"; filename="' . $meta['filename'] . '"' . "\r\n";
        $out .= 'Content-Type:'.$mime_type . "\r\n";
        $out .= "\r\n";
        $out .= (fread(fopen($imgpath, 'rb'), filesize($imgpath))) . "\r\n";
        $out .= "--{$boundary}--\r\n";

        $back = $this->httpRequest($url, $header, $out);
        $info = json_decode($back, JSON_UNESCAPED_UNICODE);
        return $info;
    }
}