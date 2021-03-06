<?php
namespace Admin\Controller;
use Think\Auth;
class PrivateController extends PublicController
{
    public $model = null;
    private $auth = null;

    public function _initialize()
    {
        parent::_initialize();
        $uid = session(C('UID'));
        if($uid == null){
            $this->redirect(C('DEFAULTS_MODULE').'/Public/login');
        }
        defined("UID") or define("UID", $uid);
        $UserName = session(C('USERNAME'));
        if(!empty($UserName)){
            $this -> assign('UserName',session(C('USERNAME')));
        }
        $this->_left_menu();
        $this->_top_menu();
        $this->_web_top_menu();
        $groupids = self::_rules();
        $iskey = S('check_iskey'.UID);
        $key = MODULE_NAME . '/' . CONTROLLER_NAME . '/' . ACTION_NAME;
        if($iskey == false){
            $where = array(
                'name' => $key,
                'status' => 1
            );
            $iskey = M('auth_cate')->where($where)->getField('id');
            S('check_iskey'.UID,$iskey);

        }
        if(UID == C('ADMINISTRATOR')){
            return true;
        }
        if($iskey != null){

            if(!in_array($iskey,$groupids)){
                $this->auth = new Auth();
                if(!$this->auth->check($key, UID)){
                    if(IS_AJAX){
                        session('[destroy]');
                        $data = array(
                            'statusCode' => 301,
                            'url'        => 'Admin/Public/login'
                        );
                        die(json_encode($data));
                    }else{
                        session('[destroy]');
                        $this->redirect('Admin/Public/login');
                    }
                }
            }
        }
    }

    /**
     * 添加编辑操作
     * @param string $model 要操作的表
     * @param string $url 要跳转的地址
     * @param int $typeid 0 为直接跳转 1为返回数组
     * @return boolean
     * @author 刘中胜  <996674366@qq.com>
     */
    protected function _modelAdd($url='',$typeid=0)
    {
        if(!$this->model){
            $this->error('请传入操作表名');
        }
        $data = $this->model->edit();
        if($typeid == 1){
            return $data;
        }
        $data ? $this->success($data['id'] ? '更新成功' : '添加成功', U($url)) : $this->error($this->model->getError());
    }

    /**
     * 查询总条数
     * @param string $model 要操作的表
     * @param array $where 查询的条件
     * @param int $type 类型 :type =1 分页用 type=2普通查询
     * @return mixed
     * @author 刘中胜  <996674366@qq.com>
     */
    protected function _modelCount($where = array(), $type = 1,$num='')
    {
        $count = $this->model->total($where);
        if($type == 1){
            if($num == ''){
                $num = C('PAGENUM');
            }
            $Page = self::_page($count,$num);
            return $Page;
        }else{
            return $count;
        }
    }

    /**
     * 查询多条数据
     * @param string $model 要操作的表
     * @param array $where 查询的条件
     * @param string $limit 分页
     * @param string $order 排序方式
     * @param string $field 要显示的字段
     * @return array
     * @author 刘中胜  <996674366@qq.com>
     */
    protected function _modelSelect($where, $order, $field = "*", $limit = '')
    {
        if(!$this->model){
            $this->error("表名未定义");
        }
        $list = $this->model->dataSet($where, $order, $field, $limit);
        return $list;
    }

    /**
     * 删除一条数据
     * @param string $url 跳转地址
     * @param int $type 如果为1则表示删除后还有其他操作
     * @return string 返回执行结果
     * @author 刘中胜  <996674366@qq.com>
     */
    protected function _del($url)
    {
        if(!$this->model){
            $this->error("表名未定义");
        }
        $id = I('get.id', 0, 'intval');
        $res = $this->model->del($id);
        if(!$res){
            $this->error($this->model->getError());
        }else{
            delTemp();
            $this->success('删除成功', U($url));
        }
    }

    /**
     * 查询一条数据
     * @param array $where 条件
     * @param $max 是否查询最大的排序字段
     * @param int $type 默认为1：分配到模板 ，其他返回
     * @return mixed
     * @author 刘中胜  <996674366@qq.com>
     */
    protected function _oneInquire($where, $type = 1)
    {
        if(!$this->model){
            $this->error("表名未定义");
        }
        $info = $this->model->oneInquire($where);
        if(!$info){
            $this->error($this->model->getError());
        }
        if($type == 1){
            return $this->assign('info', $info);
        }
        return $info;
    }


    /**
     * @param $url 要检测的权限
     * @param bool $type 是否退出 0是 1否
     * @return bool 成功返回true 否则跳转到登录页面
     */
    protected function _is_check_url($url){
        if(UID == C('ADMINISTRATOR')){
            return true;
        }
        $groupids = self::_rules();
        $url = strtolower($url);
        $url = MODULE_NAME.'/'.CONTROLLER_NAME.'/'.$url;
        $where = array(
            'name'  => $url,
            'status'=> 1
        );
        $id = M('auth_cate')->where($where)->getField('id');
        if($id){
            $this->auth = new Auth();
            if($this->auth->check($url, UID)){
                return true;
            }
            return false;
        }
        return true;

    }

    /**
     * isBbutton 控制页面添加按钮是否显示
     * @param string $title 弹出框标题
     * @param string $url 跳转地址
     * @param int $type 跳转类型: 1为弹出层 2为新窗口打开
     * @author 刘中胜
     * @time 2015-15-05
     **/
    protected function isBut($but = array())
    {
        $dataArr = array();
        foreach ($but as $Key => $value) {
            if(self::_is_check_url($value['url'])){
                if(!empty($value['parameter'])){
                    $url = U($value['url'],$value['parameter']);
                }else{
                    $url = U($value['url']);
                }
                $title = $value['title'];
                if($value['type'] == 1){
                    $href = 'JavaScript:;';
                    $target = 'popDialog';
                    $dataOpt = "{title:'" . "$title',url:'" . "$url'" . '}';
                }else{
                    $href = $url;
                    $target = '';
                    $dataOpt = '';
                }
                $dataArr[] = array(
                    'href'    => $href,
                    'target'  => $target,
                    'dataopt' => array(
                        'data-opt' => $dataOpt,
                        'content'  => $value['name']
                    )
                );
            }
        }
        $this->assign('editTag', $dataArr);
    }

    /**
     * isBbutton 控制分组页面按钮类型
     * @param string $title 弹出框标题
     * @param string $url 跳转地址
     * @param int $type 跳转类型: 1为添加 2为其他
     * @author 刘中胜
     * @time 2015-15-05
     **/
    protected function _catebut($url, $title, $id = 0, $msg = '', $type = 1)
    {
        $res = self::_is_check_url($url,1);
        if($res){
            if($id != 0){
                $where = array(
                    'id' => $id
                );
                $url = U($url, $where);
            }else{
                $url = U($url);
            }

            if($type == 1){
                $butArr = array(
                    'data-opt' => "{title:'" . "$title',url:'" . "$url'" . '}',
                    'title'    => '添 加',
                );
            }else{
                $butArr = array(
                    'data-opt' => "{title:'" . "$title',url:'" . "$url',msg:'" . "$msg'" . '}',
                    'title'    => '删 除',
                );
            }
        }
        return $butArr;
    }

    /**
     * page 分页
     * @param int $count 总条数
     * @param int $num 展示条数
     * @return array 返回组装好的结果
     * @author 刘中胜
     * @time 2015-15-05
     **/
    protected function _page($count, $num)
    {
        $showPageNum = 15;
        $totalPage = ceil($count / $num);
        $currentPage = I('post.currentPage', 1, 'intval');
        $searchValue = I('post.searchValue', '');
        if($currentPage > $totalPage){
            $currentPage = $totalPage;
        }
        if($currentPage < 1){
            $currentPage = 1;
        }
        $list = array(
            'pageNum'     => $num,
            'showPageNum' => $showPageNum,
            'currentPage' => $currentPage,
            'totalPage'   => $totalPage,
            'limit'       => ($currentPage - 1) * $num . "," . $num,
            'searchValue' => $searchValue,
            'pageUrl'     => ''
        );
        return $list;
    }

    /**
     * 左边菜单
     * @author 刘中胜
     * @time 2015-12-11
     **/
    public function _left_menu()
    {
        $str = self::_rules();
        $url = S('left_menu');
        if($url == false){
            $where = array(
                'status' => 1,
                'level'  => 1,
                'module' => MODULE_NAME
            );
            if(UID != C('ADMINISTRATOR')){
                $where['id'] = array('in', $str);
            }
            $url = M('auth_cate')->where($where)->select();
            foreach ($url as $key => &$value) {
                $urls = $value['name'] . '/index';
                $value['name'] = U($urls);
            }
            unset($value);
            S('left_menu'.UID,$url);
        }
        $this->assign('menu_url', $url);
    }

    /**
     * 列表上方菜单
     * @author 刘中胜
     * @time 2015-12-11
     **/
    public function _top_menu()
    {
        $str = self::_rules();
        $controller = CONTROLLER_NAME;
        $where = array(
            'status'     => 1,
            'level'      => 2,
            'is_menu'    => 0,
            'module'     => MODULE_NAME,
            'controller' => CONTROLLER_NAME
        );
        if(UID != C('ADMINISTRATOR')){
            $where['id'] = array('in', $str);
        }
        $url = M('auth_cate')->where($where)->field('module,controller,method,title,name')->order('sort DESC')->select();
        if($controller == 'Index'){
            $arr = array(
                'module'    => MODULE_NAME,
                'controller'=> 'Index',
                'method'    => 'index',
                'title'     => '站点信息',
                'name'     => MODULE_NAME.'/Index/index'
            );
            array_unshift($url,$arr);
        }
        $this->assign('top_menu_url', $url);
    }


    /**
     * 网站顶部菜单
     * @author 刘中胜
     * @time 2015-12-11
     **/
    public function _web_top_menu()
    {
        $str = self::_rules();
        $url = S('web_top_menu'.UID);
        if($url == false){
            $where = array(
                'status' => 1,
                'level'  => 0,
            );
            if(UID != C('ADMINISTRATOR')){
                $where['id'] = array('in', $str);
            }
            $dataArr = M('auth_cate')->where($where)->select();
            $module = array();
            foreach ($dataArr as $key => $value) {
                $where = array(
                    'pid'    => $value['id'],
                    'status' => 1
                );
                $res = M('auth_cate')->where($where)->getField('id');
                if($res){
                    $module[] = $value['id'];
                }
            }
            $where = array(
                'id'     => array('in', $module),
                'status' => 1
            );
            $url = M('auth_cate')->where($where)->field('id,title,module')->order('sort DESC')->select();
            foreach ($url as $key => &$value) {
                $where = array(
                    'pid'    => $value['id'],
                    'status' => 1
                );
                $str = M('auth_cate')->where($where)->getField('module');
                $value['url'] = U($str . '/Index/index',array('module'=>MODULE_NAME));
            }
            unset($value);
            S('web_top_menu'.UID,$url);
        }
        if(count($url) > 1){
            $this->assign('web_top_menu_url', $url);
        }
    }


    /**
     * 权限判断 所有一级菜单点击都进入这个方法
     * @author 刘中胜
     * @time 2015-12-11
     **/
    public function index()
    {
        $group = MODULE_NAME;
        $controller = CONTROLLER_NAME;
        $url = $group . '/' . $controller;
        $where = array(
            'name'   => $url,
            'level'  => 1,
            'status' => 1
        );
        $pid = M('auth_cate')->where($where)->getField('id');
        $where = array(
            'pid'    => $pid,
            'status' => 1
        );
        $info = M('auth_cate')->where($where)->getField('name');
        $this->redirect($info);
    }


    /**
     * 分类列表
     * @param string $model 要操作的表
     * @param string $cache 缓存名称
     * @author 刘中胜
     * @time 2016-01-21
     **/
    public function _cateList($model, $title, $sort='',$cache='')
    {
        $list = S($cache.UID);
        if($list == false){
            $this->model = D($model);
            $where = array(
                'status' => 1
            );
            $list = self::_modelSelect($where, $sort);
            if(!$list){
                $list = array();
            }
            $arr = array(
                'id'       => 0,
                'pid'      => null,
                'title'    => $title,
                'isParent' => true,
                'open'     => true,
            );
            array_unshift($list, $arr);
            $list = json_encode($list);
            S($cache.UID,$list);
        }
        $this->assign('list', $list);
    }

    /**
     * 列表右边操作按钮
     * 数组里第二个参数为跳转类型参数
     * type 1弹出层 2删除 3审核 4直接打开
     * @author 刘中胜
     **/
    protected function  _listBut($data)
    {
        $dataArr = array();
        foreach ($data as $key => $value) {
            if(self::_is_check_url($value[3])){
                $dataArr[$key]['name']=$value[0];
                $dataArr[$key]['opt']['title']=$value[2];
                $dataArr[$key]['opt']['url']=$value[4];
                switch ($value[1]) {
                    case 1://弹出层
                        $dataArr[$key]['target'] = 'popDialog';
                        break;
                    case 2:
                        $dataArr[$key]['opt']['msg']=$value[5];
                        $dataArr[$key]['target'] = 'ajaxDel';
                        break;
                    case 3:
                        $dataArr[$key]['opt']['msg']=$value[5];
                        $dataArr[$key]['target'] = 'ajaxTodo';
                        $dataArr[$key]['opt']['value'] = $value[7];
                        $dataArr[$key]['opt']['type'] = $value[6];
                        break;
                    default:
                        # code...
                        break;
                }
            }
        }
        return $dataArr;
    }

    /**
     * 删除分类
     * @author 普修米洛斯
     **/
    protected function _delcate($url)
    {
        if(!$this->model){
            $this->error("表名未定义");
        }
        $res = $this -> model ->delcate();
        if($res){
            $this -> success('操作成功',U($url));
        }else{
            $this -> error($this -> model->getError());
        }
    }
}
