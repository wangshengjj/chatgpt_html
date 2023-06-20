<?php


namespace FoxUpgrade;

use think\facade\Db;


class upgrade
{
    private $ApiHost = 'https://console.ttk.ink/api.php/upgrade';
    private $Product = 'fox_chatgpt';
    private $TempDir = './static/temp2/';

    /**
     * 获取更新
     * @param string $version 当前版本
     */
    public function getVersionList($version)
    {
        return $this->curl_post($this->ApiHost . '/getVersionList', [
            'product' => $this->Product,
            'host' => $_SERVER['HTTP_HOST'],
            'version' => $version
        ]);
    }

    /**
     * 获取历史版本
     * @param string $version 当前版本
     */
    public function getHistoryList($version, $page = 1, $pagesize = 10)
    {
        return $this->curl_post($this->ApiHost . '/getHistoryList', [
            'product' => $this->Product,
            'host' => $_SERVER['HTTP_HOST'],
            'version' => $version,
            'page' => $page,
            'pagesize' => $pagesize
        ]);
    }

    /**
     * 执行升级
     * @param string $version 要升级的版本
     */
    public function doUpgrade($param = [])
    {
        if (!empty($param['token'])) {
            if (!file_exists('./static/version.txt')) {
                return errorJson('非法操作');
            }
            @unlink('./static/version.txt');

            $url = $this->ApiHost . '/getPackage?' . http_build_query([
                    'product' => $this->Product,
                    'host' => $_SERVER['HTTP_HOST'],
                    'version' => $param['version'],
                    'token' => $param['token']
                ]);
            $content = $this->curl_get($url);
            if(is_array($content)) {
                $content = file_get_contents($url);
            }
            if (strpos($content, 'errno') !== false) {
                return errorJson(@json_decode($content, true)['message']);
            }
            if (!is_dir($this->TempDir)) {
                mkdir($this->TempDir, 0777, true);
            }
            $filename = uniqid();
            $zipfile = $this->TempDir . $filename . '.zip';
            file_put_contents($zipfile, $content);
            if (!file_exists($zipfile)) {
                $this->unlinkDir($this->TempDir);
                return errorJson('下载升级包失败');
            }
            // 执行升级
            $fileDir = $this->TempDir . $filename;
            if ($this->unzip($zipfile, $fileDir) === true) {
                try {
                    require_once($fileDir . '/' . 'update.php');
                    $result = (new \update(Db::class))->run();
                } catch (\Exception $e) {
                    file_put_contents('./upgrade.txt', '时间：' . date('Y-m-d H:i:s') . "\n", 8);
                    file_put_contents('./upgrade.txt', '版本：' . $param['version'] . "\n", 8);
                    file_put_contents('./upgrade.txt', '错误：' . $e->getMessage() . "\n\n", 8);
                    $this->unlinkDir($this->TempDir);
                    return errorJson($e->getMessage());
                }
                $this->unlinkDir($this->TempDir);

                if ($result['errno'] == 0) {
                    return successJson('升级成功');
                } else {
                    return errorJson($result['message']);
                }
            }
            $this->unlinkDir($this->TempDir);
            return errorJson('请检查/public/static目录权限');
        } else {
            file_put_contents('./static/version.txt', $param['version']);
            $this->unlinkDir($this->TempDir);

            return $this->curl_post($this->ApiHost . '/doUpgrade', [
                'product' => $this->Product,
                'host' => $_SERVER['HTTP_HOST'],
                'scheme' => 'https',
                'version' => $param['version']
            ]);
        }
    }

    public function checkUpdate($content)
    {
        try {
            if (!is_dir($this->TempDir)) {
                mkdir($this->TempDir, 0777, true);
            }
            $filename = uniqid();
            $file = $this->TempDir . $filename . '.php';
            file_put_contents($file, $content);
            require_once($file);
            $result = (new \update(Db::class))->run();
            $this->unlinkDir($this->TempDir);
            return successJson($result);
        } catch (\Exception $e) {
            $this->unlinkDir($this->TempDir);
            return errorJson($e->getMessage());
        }
    }

    /**
     * 解压zip压缩包
     * @param string $file 压缩文件路径
     * @param string $path 解压路径
     */
    private function unzip($file, $path = "./static/temp")
    {
        $zip = new \ZipArchive();
        $result = $zip->open($file);
        if ($result === true) {
            $zip->extractTo($path);
            $zip->close();
            return true;
        }
        return false;
    }

    /**
     * 删除文件夹
     * @param string $dir 路径
     * @return bool
     */
    private function unlinkDir($dir)
    {
        if (file_exists($dir)) {
            // 清空文件夹
            $dh = @opendir($dir);
            while ($file = readdir($dh)) {
                if ($file != "." && $file != "..") {
                    $fullpath = $dir . "/" . $file;
                    if (!is_dir($fullpath)) {
                        @unlink($fullpath);
                    } else {
                        $this->unlinkDir($fullpath);
                    }
                }
            }
            closedir($dh);

            if (rmdir($dir)) {
                return true;
            } else {
                return false;
            }
        }
        return true;
    }

    private function curl_get($url)
    {
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($curl, CURLOPT_TIMEOUT, 60);
        if (strpos($url, 'https://') !== false) {
            curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
        }

        $result = curl_exec($curl);

        if (curl_errno($curl)) {
            return [
                'errno' => 1,
                'message' => '网络错误'
            ];
        }
        curl_close($curl);

        return $result;
    }

    private function curl_post($url, $data = [])
    {
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_HEADER, false);
        curl_setopt($curl, CURLOPT_TIMEOUT, 60);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        if (!empty($data)) {
            $url .= '?r=' . rand(100000, 999999);
            curl_setopt($curl, CURLOPT_POST, true);
            curl_setopt($curl, CURLOPT_POSTFIELDS, $data);
        }

        if (strpos($url, 'https://') !== false) {
            curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
        }

        $result = curl_exec($curl);

        if (curl_errno($curl)) {
            return [
                'errno' => 1,
                'message' => '网络错误'
            ];
        }
        curl_close($curl);

        return @json_decode($result, true);
    }
}