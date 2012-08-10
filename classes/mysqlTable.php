<?
class mysqlTable extends mysql{
	var $customFieldList = '*';
	function __construct($table,$section_control = true,$sort_control = true){
		parent::__construct();
		
		//фильтр по разделам сайта
		//установить true - если в таблице храняться данные из нескольких разделов (id раздела - поле section)
		$this->section_control = $section_control;
		$this->section_field = 'section';
		$this->id_field = 'id';
		
		//контроль значений поля сортировки при удалении записи
		$this->sort_control = $sort_control;
		$this->sort_type = '';
		
		$this->setTable($table);
		/*
		$this->conn = mysql_connect(MYSQL_HOST,MYSQL_USERNAME,MYSQL_PASSWORD) or die ("can't connect to database server");
		mysql_select_db(MYSQL_DATABASE_NAME,$this->conn) or die ("no database");
		mysql_query('set charset utf8');
		
		mysql_query("SET NAMES 'utf8';");
		mysql_query("SET SESSION collation_connection = 'utf8_general_ci';");
		*/
	}
	function getTable(){return $this->table;}
	function setTable($val){$this->table = $this->getTableName($val);}
	function setSortType($val){$this->sort_type = $val;}
	function setId($val){$this->id_field = $val;}
	function setSection($val){$this->section_field = $val;}
	function setCustomFieldList($fields,$clean = true){
		$this->customFieldList = (!$clean && $this->getCustomFieldList() ? $this->getCustomFieldList().',' : '').$fields;
	}
	function getCustomFieldList(){return $this->customFieldList;}
	function getRow(&$row_set,$condition=null,$limit=null,$sort=null){
		$query='select '.$this->customFieldList.' from `'.$this->table.'`';
		if($condition) $query.=' where '.$condition;
		if($sort) $query.=' order by '.$sort;
		elseif($this->sort_control) $query.=' order by sort'.($this->sort_type!='' ? ' '.$this->sort_type : '');
		if($limit) $query.=' limit '.$limit;
		if(!($row_set=$this->query($query)))return false;
		return true;
	}
	function getNumRows($condition=null) {
		$query='select count(*) as num_rows from `'.$this->table.'`';
		if($condition!=null) $query.=' where '.$condition;
		if(!($row_set=$this->query($query)))return 0;
		$row = mysql_fetch_assoc($row_set);
		return $row['num_rows'];
	}	
	function getLimit($page_current,$page_size,$condition=null){
		$num_rows = $this->getNumRows($condition);
		$page_num = $num_rows ? ceil($num_rows/$page_size) : 1;
		if(intVal($page_current) < 1) $page_current = 1;
		elseif(intVal($page_current) > $page_num) $page_current = $page_num;
		$limit_start = ($page_current-1)*$page_size;
		
		return $limit = array(
			'num_rows'=>$num_rows,
			'page_num'=>$page_num,
			'page_size'=>$page_size,
			'page_current'=>$page_current,
			'limit_start'=>$limit_start,
			'limit_string'=>$limit_start.','.$page_size
		);
	}
	
}
?>