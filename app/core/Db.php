<?php
namespace app\core;
header("content-type:text/html;charset=utf-8");
use \PDO;
class Db
{
    private $db = [
        'dsn'=>'mysql:host=localhost;dbname=zhifu;port=3306;charset=utf8',
        'host'=>'127.0.0.1',
        'username'=>'root',
        'password'=>'',
        'dbname'=>'zhifu',
        'port'=>3306,
        'charset'=>'utf8',
        ''
    ];
    private $options = array(
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, //默认是PDO::ERRMODE_SILENT, 0, (忽略错误模式)
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC, // 默认是PDO::FETCH_BOTH, 4
    );
    //数据库表前缀
    private static $ex = 'cmf_';
    //记录当前使用的表名
    private static $table;
    //记录当前WHERE条件
    private $whereStr;
    //私有的静态属性
    private $pdo=false;
    //定义一个sql
    private static $sql;
    //返回sql语句标识 true 直接返回sql语句 false 执行查询
    private $returnSql = false;
    //私有的构造方法
    public function __construct(){

        try{
            $this->pdo = new PDO($this->db['dsn'], $this->db['username'], $this->db['password'], $this->options);
        }catch(PDOException $e){
            die('数据库连接失败:' . $e->getMessage());
        }
    }
    //私有的克隆方法
    private function __clone(){}
    //公用的静态方法
    public function name($name=''){
        // self::$sql .= self::$ex.$name;
        self::$table = self::$ex.$name;
        return $this;
    }
    //执行语句
    public function query($sql){
        $query=$this->pdo->query($sql);
        return $query;
    }
    /**
     * 获取一行
     */
    public function getOne($sql){
        $stm = $this->query($sql);
        $result = $stm->fetch();
        return $result;
    }
    /**
     * 获取全部
     */
    public function getAll($sql){
        $stm = $this->query($sql);
        $result = $stm->fetchAll();
        return $result;
    }
    /**
     * 定义添加数据的方法
     * @param table
     * @param $data [数据]
     * @return int 最新添加的id
     * @throws
     */
    public function insert($data){
        if(!is_array($data)){
            throw new Exception("insert参数类型为数组");
        }
        $name = '';
        $value = '';
        foreach($data as $k =>$v){
            $name .= $k.",";
            if(is_numeric($v)){
                $value .= $v.",";
            } else{
                $value .= "'{$v}',";
            }
        }
        $name = rtrim($name,',');
        $value = rtrim($value,',');
        self::$sql = "INSERT INTO `".self::$table."` ($name) VALUES ($value)";
        if($this->returnSql){
            return self::$sql;
        }
        $result = $this->query(self::$sql);
        //返回上一次增加操做产生ID值
        return $this->pdo->lastInsertId();
    }
    /**
     * 删除一条数据
     * @param table
     * @where where 条件
     * @return int 返回影响行数
     */
    public function deleteOne($table, $where){
        if(is_array($where)){
            foreach ($where as $key => $val) {
                $condition = $key.'='.$val;
            }
        } else {
            $condition = $where;
        }
        $sql = "delete from $table where $condition";
        $result = $this->query($sql);
        //返回受影响的行数
        return $result->rowCount();
    }
    /**
    * 删除多条数据方法
    * @param1 $table, $where 表名 条件
    * @return 受影响的行数
    */
    public function deleteAll($table, $where){
        if(is_array($where)){
            foreach ($where as $key => $val) {
                if(is_array($val)){
                    $condition = $key.' in ('.implode(',', $val) .')';
                } else {
                    $condition = $key. '=' .$val;
                }
            }
        } else {
            $condition = $where;
        }
        $sql = "delete from $table where $condition";
        $result = $this->query($sql);
        //返回受影响的行数
        return $result->rowCount();
    }
    /**
     * 删除函数 （连贯操作）
     * where条件语句为空时，不允许删除表中数据
     */
    public function delete(){
        if(empty($this->whereStr)){
            throw new Exception("delete方法必须添加条件");
        }
        self::$sql  = "DELETE FROM `".self::$table."` ".$this->whereStr;
        if($this->returnSql){
            return self::$sql;
        }
        $result = $this->query(self::$sql);
        //返回受影响的行数
        return $result->rowCount();
    }
    /**
     * [修改操作description] (连贯操作)
     * @param [type] $data 更新的数据数组
     * @return int 返回影响行数
     * @throws
     */
    public function update($data){
        if(!is_array($data)){
            throw new Exception("update方法参数为数组");
        }
        $str = "";
        foreach($data as $k => $v){
            $str .= "$k='$v',";
        }
        $str = rtrim($str,",");
        self::$sql = "UPDATE `" . self::$table ."` SET {$str} ".$this->whereStr;
        if($this->returnSql){
            return self::$sql;
        }
        $result = $this->query(self::$sql);
        //返回受影响的行数
        return $result->rowCount();
    }
    /**
     * @param $data
     * @return $this
     *  (连贯操作)
     */
    public function where($data){
        $where = " WHERE ";
        if(is_array($data)){
            foreach($data as $k =>$v){
                if(!is_array($v)) {
                    if(is_numeric($v)){
                        $where .= "{$k}={$v} AND ";
                    }else{
                        $where .= "{$k}='{$v}' AND ";
                    }
                }else{
                    $where .= $k." ".$this->turn(strtoupper($v[0]));
                    if(!is_array($v[1])){
                        if(is_numeric($v[1])){
                            $where .= " {$v[1]}";
                        }else{
                            $where .= " '{$v[1]}'";
                        }
                    }else{
                        if($v[0]=='IN'){
                            $where .= " $v[0] (".implode(",",$v[1]).")";
                        }else{
                            $where .= " ".$v[1][0]." AND ".$v[1][1];
                        }
                    }
                }
            }
        }else{
            $where .= $data;
        }
        $where = rtrim($where,"AND ");
        self::$sql .= $where;
        $this->whereStr = $where ;
        return $this;
    }
    /**
     * @param string $val
     * @return array
     * (连贯操作)
     */
    public function select($val=''){
        if(empty($val)){
            self::$sql = "SELECT * FROM ".self::$sql;
        }else{
            self::$sql = "SELECT {$val} FROM ".self::$sql;
        }
        if($this->returnSql){
            return self::$sql;
        }
        $result = $this->getAll(self::$sql);
        return $result;
    }
    /**
     * @param string $val
     * @return mixed
     *  （连贯操作）
     */
    public function find($val=''){
        if(empty($val)){
            self::$sql = "SELECT * FROM ".self::$sql." limit 1";
        }else{
            self::$sql = "SELECT {$val} FROM ".self::$sql." limit 1";
        }

        if($this->returnSql){
            return self::$sql;
        }
        $result = $this->getOne(self::$sql);
        return $result;
    }
    /**
     * @param string $val
     * @return $this
     *  (连贯操作)
     */
    public function order($val=''){
        if(!empty($val)){
            self::$sql .= " ORDER BY {$val}";
        }
        return $this;
    }
    /**
     * @param string $val
     * @return mixed
     * 统计符合的记录总条数（返回数字字符串）
     */
    public function count($val='total'){
        self::$sql = "SELECT count(*) as {$val} FROM ".self::$sql;
        $result = $this->query(self::$sql);
        $result = $result->fetch();
        return $result[$val];
    }
    /**
     * @param val
     * @return result
     * @throws
     */
    public function sum($val){
        if(!empty($val)){
            self::$sql = "SELECT sum({$val}) as {$val} FROM ".self::$sql;
            $result = $this->query(self::$sql);
            $result = $result->fetch();
            return $result[$val];
        }else{
            throw new Exception("sum方法参数必填");
        }
    }
    /**
     * @param $str
     * @return string
     * 转换SQLwhere语句中的字符串
     */
    public function turn($str){
        switch(strtoupper($str)){
            case 'GT':{return ">";};break;
            case 'EGT':{return ">=";};break;
            case 'LT':{return "<";};break;
            case 'ELT':{return "<=";};break;
            case 'EQ':{return "=";};break;
            case 'NEQ':{return "!=";};break;
            case 'HEQ':{return "===";};break;
            case 'NHEQ':{return "!==";};break;
        }
    }
    /**
     * 定义事务开始
     *  表类型：InnoDB
     *  使用形式：Db::name()->Transaction()
     */
    public function Transaction(){
        $this->pdo->setAttribute(PDO::ATTR_ERRMODE,PDO::ERRMODE_EXCEPTION);
        $this->pdo->beginTransaction();
    }
    /**
     * 事务回滚
     * 表类型：InnoDB
     *  使用形式：Db::name()->rollback()
     */
    public function rollback(){
        $this->pdo->rollback();
    }
    /**
     * 提交事务
     * 表类型：InnoDB
     * 使用形式：Db::name()->commit()
     */
    public function commit(){
        $this->pdo->commit();
    }
    /**
     * join 关联表
     *
     */
    public function join($table,$on,$way='INNER'){
        $str =  " ".strtoupper($way)." JOIN ".self::$ex.$table." ON ".$on;
        self::$sql .= $str ;
        return $this;
    }
    /**
     * 关联表时，起别名
     */
    public function alias($val){
        if(!empty($val)){
            self::$sql .= " AS $val";
        }else{
            throw new Exception("关联操作别名不能为空");
        }
        return $this;
    }
    /**
     *
     * 返回上一条操作的sql语句
     *
     */
    public function getLastsql(){
        return self::$sql;
    }
    /**
     *  limit
     */
    public function limit($s,$e=0){
        if(empty($s)){
            throw new Exception("参数错误");
        }
        if($e==0){
            self::$sql .= " LIMIT 0,$s";
        }else{
            self::$sql .= " LIMIT $s,$e";
        }
        return $this;
    }
    /**
     * @param bool $item
     * @return Object
     */
    public function returnSql($item=false){
        $this->returnSql = $item;
        return $this;
    }
}