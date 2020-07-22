<?php


namespace App\Models\Traits;


use Illuminate\Support\Facades\Config;

trait AgentMenuTrait
{
    /**
     * 与角色的多对多关系
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function roles()
    {
        return $this->belongsToMany(Config::get('admin.role'), Config::get('admin.menu_role_table'),
            Config::get('admin.menu_foreign_key'), Config::get('admin.role_foreign_key'));
    }
    /**
     * 与权限的多对多关系
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function perms()
    {
        return $this->belongsToMany(Config::get('admin.permission'), Config::get('admin.permission_menu_table'),
            Config::get('admin.menu_foreign_key'), Config::get('admin.permission_foreign_key'));
    }

    /**
     * 把权限转成角色ID,并放到到数组的roleIds
     * @param $menu
     * @return mixed
     */
    private static function transRoleIds($menu) {
        if (!empty($menu['roles'])) {
            $menu['roleIds'] = array_map(function ($item){
                return $item['id'];
            }, $menu['roles']);
        }
        else
        {
            $menu['roleIds'] = [];
        }
        return $menu;
    }

    /**
     * 把权限转成权限ID,并放到到数组的permIds
     * @param $menu 菜单数组
     * @return mixed
     */
    private static function transPermIds($menu) {
        if (!empty($menu['perms'])) {
            $menu['permIds'] = array_map(function ($item){
                return $item['id'];
            }, $menu['perms']);
        }
        else
        {
            $menu['permIds'] = [];
        }
        return $menu;
    }
    /**
     * 转成树结构
     * @param array $elements 树数组
     * @param int $parentId 上级ID
     * @return array
     */
    public static function toTree(array $elements = [], $parentId = 0, $roleIds = [])
    {
        $branch = [];

        if (empty($elements)) {
            $elements = static::with('roles', 'perms')->orderByRaw('`order_num` = 0,`order_num`')->get()->toArray();
        }

        foreach ($elements as $element) {
            if ($element['parent_id'] == $parentId) {
                $element = static::transRoleIds($element);
                $element = static::transPermIds($element);

                if (!empty($element['roleIds']) && !empty($roleIds))
                {
                    $_roles = array_intersect($element['roleIds'], $roleIds);
                    if (empty($_roles)) continue;
                }

                $children = static::toTree($elements, $element['id'], $roleIds);

                if ($children) {
                    $element['children'] = $children;
                }

                $branch[] = $element;
            }
        }

        return $branch;
    }
}