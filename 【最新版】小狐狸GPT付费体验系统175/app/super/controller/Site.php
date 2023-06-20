<?php

namespace app\super\controller;

use think\facade\Db;

class Site extends Base
{
    public function getList()
    {
        $page = input('page', 1, 'intval');
        $pagesize = input('pagesize', 10, 'intval');
        try {
            $list = Db::name('site')
                ->where('is_delete', 0)
                ->order('id desc')
                ->page($page, $pagesize)
                ->select()->each(function($item) {
                    if ($item['expire_time']) {
                        $item['expire_time'] = date('Y-m-d H:i', $item['expire_time']);
                    }
                    if ($item['last_time']) {
                        $item['last_time'] = date('Y-m-d H:i', $item['last_time']);
                    }
                    $item['create_time'] = date('Y-m-d H:i', $item['create_time']);
                    return $item;
                });
            $count = Db::name('site')
                ->where('is_delete', 0)
                ->count();
        } catch (\Exception $e) {
            return errorJson($e->getMessage());
        }

        return successJson([
            'list' => $list,
            'count' => $count
        ]);
    }

    /**
     * @return string
     * 取单个站点
     */
    public function getInfo()
    {
        $id = input('id', 0, 'intval');

        try {
            $info = Db::name('site')
                ->where('id', $id)
                ->field('id,title,phone,password,remark')
                ->find();
            if (!$info) {
                return errorJson('没有找到数据，请刷新页面重试');
            }
            return successJson($info);
        } catch (\Exception $e) {
            return errorJson($e->getMessage());
        }
    }

    /**
     * @return string
     * 更新或新建站点
     */
    public function saveInfo()
    {
        $id = input('id', 0, 'intval');
        $title = input('title', '', 'trim');
        $phone = input('phone', '', 'trim');
        $password = input('password', '', 'trim');
        $remark = input('remark', '', 'trim');


        try {
            $data = [
                'title' => $title,
                'phone' => $phone,
                'password' => $password,
                'remark' => $remark
            ];
            if ($id) {
                Db::name('site')
                    ->where('id', $id)
                    ->update($data);
            } else {
                $data['create_time'] = time();
                Db::name('site')
                    ->insert($data);
            }
            return successJson('', '保存成功');
        } catch (\Exception $e) {
            return errorJson('保存失败：' . $e->getMessage());
        }
    }

    /**
     * @return string
     * 删除站点 - 软删除
     */
    public function del()
    {
        $id = input('id', 0, 'intval');
        try {
            Db::name('site')
                ->where('id', $id)
                ->update([
                    'is_delete' => 1
                ]);
            return successJson('', '删除成功');
        } catch (\Exception $e) {
            return errorJson('删除失败：' . $e->getMessage());
        }
    }

    /**
     * @return string
     * 设置上架状态
     */
    public function setStatus()
    {
        $id = input('id', 0, 'intval');
        $status = input('status', 0, 'intval');
        try {
            Db::name('site')
                ->where('id', $id)
                ->update([
                    'status' => $status
                ]);
            return successJson('', '设置成功');
        } catch (\Exception $e) {
            return errorJson('设置失败：' . $e->getMessage());
        }
    }

    /**
     * @return string
     * 设置过期时间
     */
    public function setExpireTime()
    {
        $id = input('id', 0, 'intval');
        $expire_time = input('expire_time', 0, 'intval');
        try {
            Db::name('site')
                ->where('id', $id)
                ->update([
                    'expire_time' => $expire_time
                ]);
            return successJson('', '设置成功');
        } catch (\Exception $e) {
            return errorJson('设置失败：' . $e->getMessage());
        }
    }

    /**
     * @return string
     * 获取一键登录token
     */
    public function getLoginToken()
    {
        $id = input('id', 0, 'intval');
        $site = Db::name('site')
            ->where('id', $id)
            ->find();
        if (!$site) {
            return errorJson('站点不存在，请刷新页面重试');
        }
        $token = uniqid() . rand(1000, 9999);
        try {
            Db::name('site')
                ->where('id', $id)
                ->update([
                    'token' => $token
                ]);
            return successJson([
                'token' => $token
            ]);
        } catch (\Exception $e) {
            return errorJson('登录失败：' . $e->getMessage());
        }
    }

    public function getSelectSiteList()
    {
        $list = Db::name('site')
            ->where('is_delete', 0)
            ->field('id,title')
            ->order('id asc')
            ->select()->toArray();

        return successJson($list);
    }
}
