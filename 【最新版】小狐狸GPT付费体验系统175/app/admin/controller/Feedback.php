<?php

namespace app\admin\controller;

use think\facade\Db;

class Feedback extends Base
{
    public function getList()
    {
        $page = input('page', 1, 'intval');
        $pagesize = input('pagesize', 10, 'intval');
        $keyword = input('keyword', '', 'trim');

        $where = [
            ['site_id', '=', self::$site_id],
            ['is_delete', '=', 0]
        ];
        if (!empty($keyword)) {
            $where[] = ['content|phone', 'like', '%' . $keyword . '%'];
        }
        try {
            $list = Db::name('feedback')
                ->where($where)
                ->page($page, $pagesize)
                ->order('create_time desc')
                ->select()->each(function ($item) {
                    $item['create_time'] = date('Y-m-d H:i:s', $item['create_time']);
                    if ($item['user_id']) {
                        $user = Db::name('user')
                            ->where('id', $item['user_id'])
                            ->field('avatar,nickname')
                            ->find();
                        $item['avatar'] = $user['avatar'];
                        $item['nickname'] = $user['nickname'];
                    }

                    return $item;
                });

            $count = Db::name('feedback')
                ->where($where)
                ->count();
        } catch (\Exception $e) {
            return errorJson($e->getMessage());
        }


        return successJson([
            'count' => $count,
            'list' => $list
        ]);
    }

    /**
     * 设置为已处理
     */
    public function setState()
    {
        $id = input('id', 0, 'intval');
        $res = Db::name('feedback')
            ->where([
                ['site_id', '=', self::$site_id],
                ['id', '=', $id]
            ])
            ->update([
                'state' => 1
            ]);
        if ($res !== false) {
            return successJson('', '设置成功');
        } else {
            return errorJson('设置失败，请重试！');
        }
    }

    /**
     * 删除留言
     */
    public function del()
    {
        $id = input('id', 0, 'intval');
        $res = Db::name('feedback')
            ->where([
                ['site_id', '=', self::$site_id],
                ['id', '=', $id]
            ])
            ->update([
                'is_delete' => 1
            ]);
        if ($res !== false) {
            return successJson('', '删除成功');
        } else {
            return errorJson('删除失败，请重试！');
        }
    }
}
