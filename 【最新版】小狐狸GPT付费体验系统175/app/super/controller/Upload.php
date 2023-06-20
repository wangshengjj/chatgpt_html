<?php

namespace app\super\controller;

use think\facade\Filesystem;

class Upload extends Base
{
    /**
     * 上传图片
     */
    public function image()
    {
        $data = input('data', '', 'trim');
        $data = @json_decode($data, true);
        $type = isset($data['type']) ? $data['type'] : '';

        $file = request()->file('file');
        $path = Filesystem::disk('public')->putFile('image', $file, 'uniqid');
        $ext = strrchr($path, '.');
        if (!in_array($ext, ['.jpg', '.png', '.gif'])) {
            return errorJson('只能上传jpg/png/gif格式的图片');
        }

        return successJson([
            'type' => $type,
            'path' => mediaUrl('/upload/' . $path, true)
        ]);
    }

    /**
     * 上传证书文件
     */
    public function pem()
    {
        $file = request()->file('file');
        $path = Filesystem::disk('public')->putFile('cert', $file, 'uniqid');
        $ext = strrchr($path, '.');
        if ($ext != '.pem') {
            return errorJson('只能上传.pem格式的文件');
        }
        $path = mediaUrl('/upload/' . $path);

        return successJson($path);
    }
}
