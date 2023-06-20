<?php

namespace FoxFilter;
class words
{
    private $words;
    private $replaceChar;
    private $fuzzyMatch;
    private $txtDir = './static/words';

    public function __construct($replaceChar = '*', $fuzzyMatch = false)
    {
        $this->replaceChar = $replaceChar;
        $this->fuzzyMatch = $fuzzyMatch;
        $this->setWords($this->txtDir);
    }

    public function setWords($dir)
    {
        $txtArr = $this->readTxtFiles($dir);
        $wordArr = explode(',', implode(',', $txtArr));
        foreach ($wordArr as $k => $v) {
            if (empty(trim($v))) {
                unset($wordArr[$k]);
            }
        }
        $this->words = array_chunk($wordArr, 100);
    }

    /**
     * @param $folderPath
     * @return array
     * 遍历读取文件夹下txt内容
     */
    private function readTxtFiles($folderPath)
    {
        $arr = [];
        if (is_dir($folderPath)) {
            $files = scandir($folderPath);
            foreach ($files as $file) {
                $filePath = $folderPath . '/' . $file;
                if (is_file($filePath) && pathinfo($filePath, PATHINFO_EXTENSION) === 'php') {
                    $content = file_get_contents($filePath);
                    $content = str_replace(['<?php', '/*', '*/'], '', $content);
                    $content = trim($content);
                    $arr[$file] = $content;
                }
            }
        }
        return $arr;
    }
    public function filter($text)
    {
        $result = $text;
        foreach($this->words as $arr) {
            foreach($arr as $word) {
                $star = str_repeat('*', mb_strlen($word, 'utf-8'));
                $result = str_replace($word, $star, $result);
            }
        }

        return $result;
    }
}

