<?php

namespace app\api\controller;

use think\facade\Db;
use think\facade\Request;

class Base
{
    protected static $user;
    protected static $site_id;

    public function __construct()
    {
        if (Request::action() == 'login') {
            return;
        }
        $token = Request::header('x-token');
        if ($token) {
            session_id($token);
        }
        session_start();
        if (empty($_SESSION['user'])) {
            die(json_encode(['errno' => 403, 'message' => '请登录']));
        }
        $user = json_decode($_SESSION['user'], true);
        if (empty($user['openid'])) {
            die(json_encode(['errno' => 403, 'message' => '请登录']));
        }

        self::$user = $user;
        self::$site_id = $user['site_id'];
    }

    public function import()
    {
        $json = '';

        $arr = explode('},{', $json);
        foreach($arr as $v) {
            $v = ltrim($v, '{');
            $v = rtrim($v, '}');
            $v = '{' . $v . '}';
            $v = json_decode($v, true);
            /*if(empty($data)) {
                print_r($v);
                echo "\n\n\n";
            }*/

            try {
                Db::name('write_prompts')
                    ->insert([
                        'topic_id' => Db::name('write_topic')
                            ->where('code', $v['Community'])
                            ->value('id'),
                        'activity_id' => Db::name('write_activity')
                            ->where('code', $v['Category'])
                            ->value('id'),
                        'topic_code' => $v['Community'],
                        'activity_code' => $v['Category'],
                        'title' => $v['Title'],
                        'desc' => $v['Teaser'],
                        'prompt' => $v['Prompt'],
                        'hint' => $v['PromptHint'],
                        'type_no' => $v['PromptTypeNo'],
                        'usages' => $v['Views'],
                        'views' => $v['Views'],
                        'votes' => $v['Votes']
                    ]);
            } catch (\Exception $e) {
                echo json_encode($v);
                echo "\n";
                echo $e->getMessage();
                echo "\n\n\n";
            }

        }


    }
}
