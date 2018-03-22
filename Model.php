<?php

$config = include 'config.php';
var_dump($config);
//封装的model类
class Model
{
	//主机名
	protected $host;
	//用户名
	protected $user;
	//密码
	protected $pwd;
	//数据库的名字
	protected $dbname;
	//字符集
	protected $charset;
	//数据表的前缀
	protected $prefix;
	//数据连接的资源
	protected $link;
	//数据表名
	protected $tableName = 'user';
	//sql语句
	protected $sql;
	//缓存的字段
	protected $fields;
	//options数组，存放查询条件的数组
	protected $options;

	//构造方法，初始化成员变量
	function __construct($config)
	{
		//初始化这些成员变量
		$this->host = $config['DB_HOST'];
		$this->user = $config['DB_USER'];
		$this->pwd = $config['DB_PWD'];
		$this->dbname = $config['DB_NAME'];
		$this->charset = $config['DB_CHARSET'];
		$this->prefix = $config['DB_PREFIX'];


		//连接数据库，将连接成功的资源保存起来$link,已经成功
		$this->link  = $this->connect();
		//表名，如果你传过来的话，我就使用你传的，或者自己获取
		$this->tableName = $this->getTableName();
		//得到缓存的字段，将其存放在$this->fields
		$this->fields = $this->getCacheFields();
		//初始化options数组,把一些查询条件给整理一下
		$this->initOptions();
		
	}
	protected function connect()
	{
		$link = mysqli_connect($this->host, $this->user, $this->pwd);
		if (!$link) {
			die('数据库连接失败');
		}
		mysqli_select_db($link, $this->dbname);
		mysqli_set_charset($link, $this->charset);
		return $link;
	}
	protected function getTableName()
	{
		//如果设置了，就直接使用
		if (!empty($this->tableName)) {
			return $this->prefix . $this->tableName;
		}
		//通过类名，一个model类对应一个数据表，UserModel，对应的数据表，user
		//写了一个类UserModel  继承Mode，  通过这个名字来吧user给他拿出来
		
		//获取类名
		$className = get_class($this);
		//UserModel  GoodsModel PhoneModel  user, goods ,phone
		$table = strtolower(substr($className, 0, -5));
		return $this->prefix . $table;
	}
	protected function getCacheFields()
	{
		//拼接缓存文件的路径
		
		$cacheFile = './cache/' . $this->tableName . '.php';
		//判断文件是否存在，如果存在就直接include进来，如果不存在就生成这个文件
		if (file_exists($cacheFile)) {
			return include $cacheFile;
		}

		
		//拼接sql语句  desc
		$sql = 'desc ' . $this->tableName; 
		//通过执行sql语句打印一下数据，让大家看一下到底是啥玩意？
		//这个query方法，下				面也要用
		$result = $this->query($sql);
		
		//得到结果集
		foreach ($result as $key => $value) {
			$fileds[] = $value['Field'];

			//得到主键，专门的处理
			 if ($value['Key'] == 'PRI') {
			 	$fileds['PRI'] = $value['Field'];

			 }
		}
		//将上面的数组，变成一个字符串，然后写到文件里面
		$str = var_export($fileds, true);
		$str = "<?php\n\n return " . $str . ';' ;
		file_put_contents($cacheFile, $str);
		return $fileds;
	}
	protected function query($sql)
	{
		$result = mysqli_query($this->link, $sql);
		if ($result && mysqli_affected_rows($this->link)) {
			while ($data = mysqli_fetch_assoc($result)) {
				$newData[] = $data;
			}
			//是一个二维数组
			return $newData;
		}
		return false; 

	}
	//初始化options数组，将里面的值全部设置为空，将fileds设置为缓存字段，将table设置为默认的table名
	protected function initOptions()
	{
		$arr = ['where', 'table', 'filed', 'order', 'group', 'having', 'limit'];
		foreach ($arr as $key => $value) {
			//将options里面键对应的值设置为空
			//$options['where'] = '';
			//$options['table'] = '';
			$this->options[$value] = '';
			//这个filed默认的字段就是咱们缓存的字段
			if ($value == 'filed') {
				$this->options[$value] = join(',', array_unique($this->fields));
			} else if ($value == 'table'){
				$this->options[$value] = $this->tableName;
			}
		}
	}
	//where条件
	 function where($where)
	{
		if (!empty($where)) {
			//$sql = select * from user;
			$this->options['where'] = 'where '. $where;
		}
		return $this;

	}
	//table函数
	 function table($table)
	{
		if (!empty($table)){
			$this->options['table'] = $table;
		}
		return $this;
	}
	//filed函数,  
	function field($filed)
	{
		if (!empty($filed)) {
			if (is_string($filed)) {
				$this->options['filed'] = $filed;
			}else if (is_array($filed)) {
				$this->options['filed'] = join(',', $filed);
			}
		}
		return $this;

	}
	//group函数
	function group($group)
	{
		if (!empty($group)) {
			//$sql = select * from user;
			$this->options['group'] = 'group by '. $group;
		}
		return $this;
	}
	//having函数
	function having($having)
	{
		if (!empty($having)) {
			//$sql = select * from user;
			$this->options['having'] = 'having '. $having;
		}
		return $this;
	}
	//order
	function order($order)
	{
		if (!empty($order)) {
			//$sql = select * from user;
			$this->options['order'] = 'order by '. $order;
		}
		return $this;
	}
	//limit  '3,5'  [3,5]
	function limit($limit)
	{
		if (!empty($limit)) {
			//$sql = select * from user;
			if (is_string($limit)) {
				$this->options['limit'] = 'limit '. $limit;
			} else if (is_array($limit)) {
				$this->options['limit'] = 'limit '. join(',',$limit);
			}
			
		}
		return $this;
	}
	//查询函数
	function select()
	{
		//带有占位符的sql语句
		$sql = 'select %FIELD% from %TABLE% %WHERE% %GROUP% %HAVING% %ORDER% %LIMIT%';
		//将$this->options里面的值把占位符依次替换
		$sql = str_replace(
			['%FIELD%', '%TABLE%', '%WHERE%', '%GROUP%', '%HAVING%',' %ORDER%','%LIMIT%'],
			[$this->options['filed'], $this->options['table'], $this->options['where'], $this->options['group'],$this->options['having'],$this->options['order'],$this->options['limit']],
			$sql);
		$this->sql = $sql;
		//执行sql语句
		return $this->query($sql);
	}
	//增删改语句执行的函数
	function exec($sql, $insertId = false)
	{
		$result = mysqli_query($this->link, $sql);
		if ($result && mysqli_affected_rows($this->link)) {
			if ($insertId) {
				return mysqli_insert_id($this->link);
			} else {
				return mysqli_affected_rows($this->link);
			}

		}
		return false;
	}
	//insert函数 $data 关联数组，键名就是字段，值就是字段值
	//['username'=>'狗蛋', 'password'=>123456, ]
	function insert($data)
	{
		//$sql = 'insert into user(username, password) values('狗蛋', 123456)';
		//处理关联数组中的值，如果值是字符串的话，就加引号
		$data = $this->parseValue($data);
		//提取所有的键值
		$keys = array_keys($data);
		//开始提取所有的值
		$values = array_values($data);
		$sql = 'insert into %TABLE%(%FIELD%) values(%VALUES%)';
		$sql = str_replace(
			['%TABLE%','%FIELD%','%VALUES%'],
			[$this->options['table'], join(',', $keys), join(',', $values)],
			$sql);
		$this->sql = $sql;
		return $this->exec($sql, true);

	}
	//传值过来必须是一个数组,只是给数组中的是字符串值的加上引号
	//为什么加引号，？
	protected function parseValue($data)
	{
		foreach ($data as $key => $value) {
			if (is_string($value)) {
				$value = '"' . $value . '"';
			}
			$newData[$key] = $value;
		}
		return $newData;
	}
	//删除函数
	function delete()
	{
		$sql = 'delete from %TABLE% %WHERE%';
		$sql = str_replace(['%TABLE%','%WHERE%'],
			[$this->options['table'], $this->options['where']],
			$sql);
		$this->sql = $sql;
		return $this->exec($sql);
	}
	//修改的函数
	//获取sql语句
	//count  min max sum



}
$model = new Model($config);
//var_dump($model->field('id,username')->where('id < 48')->select());
// $data = ['username'=>'狗剩儿', 'password'=>789];
// var_dump($model->insert($data));
echo $model->where('id = 87')->delete();