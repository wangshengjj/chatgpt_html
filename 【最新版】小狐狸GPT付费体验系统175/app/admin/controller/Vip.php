<?php

namespace app\admin\controller;

use think\facade\Db;

class Vip extends Base
{
    public function getList()
    {
        $page = input('page', 1, 'intval');
        $pagesize = input('pagesize', 10, 'intval');
        $where = [
            'site_id' => self::$site_id
        ];
        try {
            $list = Db::name('vip')
                ->where($where)
                ->field('id,title,price,market_price,num,weight,sales,is_default,status,create_time')
                ->order('weight desc, id asc')
                ->page($page, $pagesize)
                ->select()->each(function($item) {
                    $item['price'] = $item['price'] / 100;
                    $item['market_price'] = $item['market_price'] / 100;
                    $item['create_time'] = date('Y-m-d H:i:s', $item['create_time']);
                    return $item;
                });
            $count = Db::name('vip')
                ->where($where)
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
     * 取单个服务
     */
    public function getInfo()
    {
        $id = input('id', 0, 'intval');

        try {
            $info = Db::name('vip')
                ->where([
                    ['site_id', '=', self::$site_id],
                    ['id', '=', $id]
                ])
                ->find();
            if (!$info) {
                return errorJson('没有找到数据，请刷新页面重试');
            }
            $info['price'] = $info['price'] / 100;
            $info['market_price'] = $info['market_price'] / 100;
            return successJson($info);
        } catch (\Exception $e) {
            return errorJson($e->getMessage());
        }
    }

    /**
     * @return string
     * 更新或新增服务
     */
    public function saveInfo()
    {
        $id = input('id', 0, 'intval');
        $title = input('title', '', 'trim');
        $weight = input('weight', 100, 'intval');
        $price = input('price', 0, 'floatval');
        $market_price = input('market_price', 0, 'floatval');
        $num = input('num', 0, 'intval');

        try {
            $data = [
                'title' => $title,
                'price' => $price * 100,
                'market_price' => $market_price * 100,
                'weight' => $weight,
                'num' => $num
            ];
            if ($id) {
                Db::name('vip')
                    ->where([
                        ['site_id', '=', self::$site_id],
                        ['id', '=', $id]
                    ])
                    ->update($data);
            } else {
				$data['site_id'] = self::$site_id;
                $data['create_time'] = time();
                Db::name('vip')
                    ->insert($data);
            }
            return successJson('', '保存成功');
        } catch (\Exception $e) {
            return errorJson('保存失败：' . $e->getMessage());
        }
    }

    /**
     * @return string
     * 删除服务
     */
    public function del()
    {
        $id = input('id', 0, 'intval');
        try {
            Db::name('vip')
                ->where([
                    ['site_id', '=', self::$site_id],
                    ['id', '=', $id]
                ])
                ->delete();
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
            Db::name('vip')
                ->where([
                    ['site_id', '=', self::$site_id],
                    ['id', '=', $id]
                ])
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
     * 设置默认商品
     */
    public function setDefault()
    {
        $id = input('id', 0, 'intval');
        $is_default = input('is_default', 0, 'intval');
        try {
            if ($is_default) {
                Db::name('vip')
                    ->where([
                        ['site_id', '=', self::$site_id],
                        ['is_default', '=', 1]
                    ])
                    ->update([
                        'is_default' => 0
                    ]);
            }
            Db::name('vip')
                ->where([
                    ['site_id', '=', self::$site_id],
                    ['id', '=', $id]
                ])
                ->update([
                    'is_default' => $is_default ? 1 : 0
                ]);
            return successJson('', '设置成功');
        } catch (\Exception $e) {
            return errorJson('设置失败：' . $e->getMessage());
        }
    }
}
