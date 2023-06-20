<?php

namespace app\web\controller;

use think\facade\Db;

class Group extends Base
{
    public function getGroupList()
    {
        $page = input('page', 1, 'intval');
        $pagesize = input('pagesize', 20, 'intval');
        $where = [
            ['site_id', '=', self::$site_id],
            ['user_id', '=', self::$user['id']],
            ['is_delete', '=', 0]
        ];

        try {
            $list = Db::name('msg_group')
                ->where($where)
                ->field('id,title')
                ->order('id desc')
                ->page($page, $pagesize)
                ->select()->toArray();
            $count = Db::name('msg_group')
                ->where($where)
                ->count();

            if (empty($list)) {
                $title = '新的会话';
                $group_id = Db::name('msg_group')
                    ->insertGetId([
                        'site_id' => self::$site_id,
                        'user_id' => self::$user['id'],
                        'title' => '新的会话',
                        'create_time' => time()
                    ]);
                $list = [
                    [
                        'id' => $group_id,
                        'title' => $title,
                    ]
                ];
                $count = 1;
            }

            return successJson([
                'list' => $list,
                'count' => $count
            ]);
        } catch (\Exception $e) {
            return errorJson($e->getMessage());
        }


    }

    public function getGroup()
    {
        $id = input('id', 0, 'intval');
        try {
            $info = Db::name('msg_group')
                ->where([
                    ['site_id', '=', self::$site_id],
                    ['user_id', '=', self::$user['id']],
                    ['is_delete', '=', 0],
                    ['id', '=', $id]
                ])
                ->field('id, title')
                ->find();
            if (!$info) {
                return errorJson('没有找到数据，请刷新页面重试');
            }

            return successJson($info);
        } catch (\Exception $e) {
            return errorJson($e->getMessage());
        }
    }

    public function saveGroup()
    {
        $id = input('id', 0, 'intval');
        $title = input('title', '', 'trim');

        try {
            $data = [
                'title' => $title,
            ];
            if ($id) {
                Db::name('msg_group')
                    ->where([
                        ['site_id', '=', self::$site_id],
                        ['user_id', '=', self::$user['id']],
                        ['is_delete', '=', 0],
                        ['id', '=', $id]
                    ])
                    ->update($data);
                $message = '更新成功';
            } else {
				$data['site_id'] = self::$site_id;
				$data['user_id'] = self::$user['id'];
                $data['create_time'] = time();
                $id = Db::name('msg_group')
                    ->insertGetId($data);
                $message = '创建成功';
            }
            return successJson([
                'group_id' => $id
            ], $message);
        } catch (\Exception $e) {
            return errorJson('提交失败：' . $e->getMessage());
        }
    }

    /**
     * @return string
     * 删除服务
     */
    public function delGroup()
    {
        $id = input('id', 0, 'intval');
        try {
            Db::name('msg_group')
                ->where([
                    ['site_id', '=', self::$site_id],
                    ['user_id', '=', self::$user['id']],
                    ['id', '=', $id]
                ])
                ->update([
                    'is_delete' => 1
                ]);
            return successJson('', '删除成功');
        } catch (\Exception $e) {
            return errorJson('删除失败：' . $e->getMessage());
        }
    }
}
