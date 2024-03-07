<?php

/* 
class to deal with mysql databases 
cloned from MD on 1 aug 2021
cloned from BMMO on 1 Apr 2023
@modified 2023-06-05 - Max: rows_by_id(), sanitize(), if brackets, php 8 compat
*/

//Insert and Update takes care of sanitizing.
//select and where clauses need to be $db->sanitize(..) by user

class mysqldb {
  /**array of error number and msgs*/
  var $error;
  /**the last error array*/
  var $last_error;
  /**resource mysql connection*/
  var $conn;
  /**Db hostname*/
  var $host;
  /**mysql username*/
  var $user;
  /**mysql password*/
  var $pass;
  /**mysql selected database*/
  var $dbname;
  /**resource mysql db selected*/
  var $db;
  /** resource result handler*/
  var $last_qres;
  /**array of last query to array results*/
  var $last_q2a_res;
  /**manu - tine query-uri pt debug*/
  var $dbg;

  var $num_rows;
  
  /**chew - Added a charset variable */
  var $charset;
  
  //chk persistent connection
  var $persistent;

  var $autoconnect;

  var $last_id;

  var $beverbose;
  
  public function __construct($dbname,$dbhost='localhost',$dbuser='root',$dbpass=''){ # most common config ?
    $this->host   = $dbhost;
    $this->user   = $dbuser;
    $this->pass   = $dbpass;
    $this->dbname = $dbname;
    $this->charset = "utf8";
	$this->persistent   = TRUE; //chk
    $this->autoconnect= TRUE;
    $this->open();
    $this->beverbose  = FALSE;
  }

  public function open(){  # only for convenience and because backport of sqlitedb
    return $this->check_conn('active');
  }

  public function close(){
    return $this->check_conn('kill');
  }
  /**
   Select the database to work on (it's the same as the use db command or mysql_select_db function)
   @param string $dbname
   @return bool
  */
  function select_db($dbname=null){
    if(! ($dbname ||$this->dbname) ){
      return FALSE;
    }
    if($dbname){
      $this->dbname = $dbname;
    }
    if(! $this->db = mysqli_select_db($this->conn,$this->dbname)){
      $this->verbose("FATAL ERROR CAN'T CONNECT TO database ".$this->dbname);
      $this->set_error();
      return FALSE;
    }else{
      return $this->db;
    }
  }
  /**
   check and activate db connection
   @param string $action (active, kill, check) active by default
  */
  function check_conn($action = ''){
   // return true;
   //if($this->conn){
	if(true || !$host = @mysqli_get_host_info($this->conn)){
      switch ($action){
        case 'kill':
          return $host;
          break;
        case 'check':
          return $host;
          break;
        default:
        case 'active':
          if($this->persistent){
			  if(! $this->conn = mysqli_connect($this->host,$this->user,$this->pass)){
				$this->verbose("CONNECTION TO {$this->host} FAILED");
				return FALSE;
			  }
		  }else{
			  if(! $this->conn = mysqli_connect($this->host,$this->user,$this->pass)){
				$this->verbose("CONNECTION TO {$this->host} FAILED");
				return FALSE;
			  }
		  }
          $this->verbose("CONNECTION TO {$this->host} ESTABLISHED");
          $this->select_db();
          mysqli_set_charset($this->conn,$this->charset);
          return @mysqli_get_host_info($this->conn);
          break;
      }
    }else{
      switch($action){
        case 'kill':
          mysqli_close($this->conn);
          $this->conn = $this->db = null;
          return true;
          break;
        case 'check':
          return $host;
          break;
        default:
        case 'active':
          return $host;
          break;
      }
    }
  // }
  }
  /**
   send a select query to $table with arr $fields requested (all by default) and with arr $conditions
   sample conds array is array(0=>'field1 = field2','ORDER'=>'field desc','GROUP'=>'fld')
   @param string|array $Table
   @param string|array $fields
   @param string|array $conditions
   @param MYSQL_CONST $res_type MYSQL_ASSOC, MYSQL_NUM et MYSQL_BOTH
   @Return  array | false
  **/
  function select_to_array($tables,$fields = '*', $conds = null,$result_type = MYSQLI_ASSOC){
    //we make the table list for the Q_str
    if(! $tb_str = $this->array_to_str($tables)){
      return FALSE;
    }
    //we make the fields list for the Q_str
    if(! $fld_str =  $this->array_to_str($fields)){
      $fld_str = '*';
    }
    //now the WHERE str
    $conds_str = "";
    if($conds){
      $conds_str = $this->process_conds($conds);
    }
    $Q_str = "SELECT {$fld_str} FROM {$tb_str} {$conds_str}";
    return $this->query_to_array($Q_str,$result_type);
  }

    function select_to_array_or_false($tables,$fields = '*', $conds = null,$result_type = MYSQLI_ASSOC){
        //we make the table list for the Q_str
        if(! $tb_str = $this->array_to_str($tables)){
            return FALSE;
        }
        //we make the fields list for the Q_str
        if(! $fld_str =  $this->array_to_str($fields)){
            $fld_str = '*';
        }
        //now the WHERE str
        $conds_str = "";
        if($conds){
            $conds_str = $this->process_conds($conds);
        }
        $Q_str = "SELECT {$fld_str} FROM {$tb_str} {$conds_str}";
        return $this->query_to_array_or_false($Q_str,$result_type);
    }

    /**
   Same as select_to_array but return only the first row.
   equal to $res = select_to_array followed by $res = $res[0];
   @see select_to_array for details
   @return array of fields
  */
  function select_single_to_array($tables,$fields = '*', $conds = null,$result_type = MYSQLI_ASSOC){
    if(!stristr($conds, "LIMIT")){
        $conds = $conds." LIMIT 1"; //allways append limit to single select
    }
    if(! $res = $this->select_to_array_or_false($tables,$fields,$conds,$result_type)){
      return FALSE;
    }
    return $res[0];
  }
  /**
  * just a quick way to do a select_to_array followed by a associative_array_from_q2a_res
  * see both thoose method for more information about parameters or return values
  */
  function select2associative_array($tables,$fields='*',$conds=null,$index_field='id',$value_fields=null,$keep_index=FALSE){
    if(! $this->select_to_array_or_false($tables,$fields,$conds)){
      return FALSE;
    }
    return $this->associative_array_from_q2a_res($index_field,$value_fields,null,$keep_index);
  }
  /**
  * select a single value in database
  * @param string $table
  * @param string $field the field name where to pick-up value
  * @param mixed conds
  * @return mixed or FALSE
  */
  function select_single_value($table,$field,$conds=null){
    if($res = $this->select_single_to_array($table,$field,$conds,MYSQLI_NUM)){
      return $res[0];
    }else{
      return FALSE;
    }
  }
  /**
  * return the result of a query to an array
  * @param string $Q_str SQL query
  * @return array | false if no result
  */
  function query_to_array($Q_str,$result_type=MYSQLI_ASSOC){
	unset($this->last_q2a_res);
    # echo "$Q_str\n";
    if(! $this->query($Q_str)){
      # echo "QSTR $Q_str\n";
      # echo "return FALSE\n";
       $this->set_error();
      return FALSE;
    }
    while($res[]=mysqli_fetch_array($this->last_qres,$result_type));
    unset($res[count($res)-1]);//unset last empty row

    $this->num_rows = mysqli_affected_rows($this->conn);
    return $this->last_q2a_res = count($res)?$res:FALSE;
  }

    /**
     * Special method that returns false if there is no results
     * @param $Q_str
     * @param int $result_type
     * @return array|bool|false
     */
    function query_to_array_or_false($Q_str,$result_type=MYSQLI_ASSOC){
        $result = $this->query_to_array($Q_str, $result_type);

        if($result === false) {
            return false;
        }else{
            return (count($result) ? $result : false);
        }
    }

    
  //v.1.4 - added singlevalue param to allow compat with select and radio fields
  //v.1.3 - results are returned as array so it is compatible with multiple records per id col. To retrieve default for when id col is id, use [0]
  //v.1.2 - supports alias column names like 'count(*) as nr'
  //v.1.1 - updated to be inside the db class
  //get rows from db and reorganize them by id col
  //if colname is array, it will get only those cols. If its string, it will assign just that value to the id keyas array (to keep compat with code expecting multiple rows)
  //if singlevalue param is true, it will assign just one value per key, compat with select and radio boxes, or when data is expected to be one per id anyway
  function rows_by_id($table,$colname,$id='id',$where='',$singlevalue=false){

    if(is_array($colname)){

        $real_colnames = array();
        foreach($colname as $col){
          if(strpos($col, " as ") !== false){
            $asname_parts = explode(" as ",$col);
            $real_colnames[] = $asname_parts[1];
          }elseif(strpos($col, " AS ") !== false){
            $asname_parts = explode(" AS ",$col);
            $real_colnames[] = $asname_parts[1];
          } 
        }

        $colname_sql = implode(", ",$colname).", ";
    
    }elseif($colname){
    
      $real_colname = $colname;
      if(strpos($colname, " as ") !== false){
        $asname_parts = explode(" as ",$colname);
        $real_colname = $asname_parts[1];
      }elseif(strpos($colname, " AS ") !== false){
        $asname_parts = explode(" AS ",$colname);
        $real_colname = $asname_parts[1];
      }

      $colname_sql = $colname.", ";
    
    }else{
        $colname_sql = "*, ";
    }

    $real_id = $id;
    if(strpos($id, " as ") !== false){
      $idname_parts = explode(" as ",$id);
      $real_id = $idname_parts[1];
    }elseif(strpos($id, " AS ") !== false){
      $idname_parts = explode(" AS ",$id);
      $real_id = $idname_parts[1];
    }

    //die("select  {$colname_sql} {$id} from {$table} {$where}");
    $rows = $this->select_to_array($table,"{$colname_sql} {$id}","{$where}");
    $rows_by_id = array();
    if($rows){
        foreach($rows as $k => $v){
            if(is_array($colname)){
                $filtered_arr = array();
                foreach($v as $clnm => $clv){
                    if(in_array($clnm,$real_colnames)){
                        $filtered_arr[$clnm] = $clv;
                    }
                }
                if($singlevalue){
                    $rows_by_id[$v["{$real_id}"]] = $filtered_arr;
                }else{
                    $rows_by_id[$v["{$real_id}"]][] = $filtered_arr;
                }
            }elseif($colname){
                if($singlevalue){
                    $rows_by_id[$v["{$real_id}"]] = $v["{$real_colname}"];
                }else{
                    $rows_by_id[$v["{$real_id}"]][] = $v["{$real_colname}"];
                }
            }else{
                if($singlevalue){
                    $rows_by_id[$v["{$real_id}"]] = $v;
                }else{
                    $rows_by_id[$v["{$real_id}"]][] = $v;
                }
            }
        }
    }
    return $rows_by_id;
  }

    /**
  * Send an insert query to $table
  * @param string $table
  * @param array $values (arr(FLD=>VALUE,)
  * @param bool $return_id the function will return the inserted_id if $return_id is true (the default value), else it'll return only true or false.
  * @return insert id or FALSE
  **/
  function insert($table,$values,$return_id=TRUE){
    if(!is_array($values)){
      return FALSE;
    }
    foreach( $values as $k=>$v){
      $fld[]= "`$k`";
      $val[]= "'".$this->sanitize($v)."'";
    }
    $Q_str = "INSERT INTO {$table} (".$this->array_to_str($fld).") VALUES (".$this->array_to_str($val).")";
    # echo $Q_str;
	if(! $this->query_affected_rows($Q_str)){
      # echo $Q_str;
      return FALSE;
    }
    $this->last_id = mysqli_insert_id($this->conn);
    return $return_id?$this->last_id:TRUE;
  }
  /**
  * Send a delete query to $table
  * @param string $table
  * @param mixed $conds
  * @return int affected_rows
  **/
  function delete($table,$conds){
    //now the WHERE str
    if($conds){
      $conds_str = $this->process_conds($conds);
    }else{
        $conds_str = '';
    }
    $Q_str = "DELETE FROM {$table} {$conds_str}";
	# echo $Q_str;
    return $this->query_affected_rows($Q_str);
  }
  /**
  * Send an update query to $table
  * @param string $table
  * @param string|array $values (arr(FLD=>VALUE,)
  * @return int affected_rows
  **/
  function update($table,$values,$conds = null){
    if(is_array($values)){
      foreach( $values as $k=>$v)
          //EDIT BY CHEW: if update being passed is null (make sure you use ===) then store NULL
          if($v === null) {
              $str[]= " `$k` = NULL";
          }else{
              $str[]= " `$k` = '".$this->sanitize($v)."'";
          }

    }elseif(! is_string($values)){
      return FALSE;
    }
	
	$conds_str = "";
	
    # now the WHERE str
    if($conds){
      $conds_str = $this->process_conds($conds);
    }

    $Q_str = "UPDATE {$table} SET ".(is_array($str)?$this->array_to_str($str):$values)." {$conds_str}";
	return $this->query_affected_rows($Q_str);
  }
  /**
  * perform a query on the database
  * @param string $Q_str
  * @return= result id | FALSE
  **/
  function query($Q_str){
    if(! $this->db ){
      if(! ($this->autoconnect && $this->check_conn('check'))){
        return FALSE;
      }
    }
    # echo "\n**SQL QUERY on $this->db :\n$Q_str\n";
    $this->dbg[] = $Q_str;

    if(! $this->last_qres = mysqli_query($this->conn,$Q_str)){
      $this->set_error();
      return false;
    }

   
    if(is_a($this->last_qres, 'mysqli_result')){
        //var_dump($this->last_qres->num_rows); 
        if(get_class($this->last_qres) == 'mysqli_result' && $this->last_qres->num_rows > 0){
            return $this->last_qres;
        }else{
            return false;
        }
    }else{
        return false;
    }
  }
  /**
  * perform a query on the database like query but return the affected_rows instead of result
  * give a most suitable answer on query such as INSERT OR DELETE
  * @param string $Q_str
  * @return int affected_rows
  */
  function query_affected_rows($Q_str){
    if(! $this->db ){
      if(! ($this->autoconnect && $this->check_conn('check'))){
        return FALSE;
      }
    }
    # echo "\n**SQL QUERY on $this->db :\n$Q_str\n";
    $this->dbg[] = $Q_str;
	$this->last_qres = mysqli_query($this->conn,$Q_str);
    $num = mysqli_affected_rows($this->conn);
    if( $num == -1){
      $this->set_error();
    }else{
      return $num;
    }
  }
  /**
   return the list of field in $table
   
   KNOWN BUG doesn't work on empty table
   @param string $table name of the sql table to work on
   @param bool $extended_info will return the result of a show field query in a query_to_array fashion
  */
  function get_fields($table,$extended_info=FALSE){
    if(! $res = $this->query_to_array_or_false("SHOW FIELDS FROM $table"))
      return FALSE;
    if($extended_info)
      return $res;
    foreach($res as $row){
      $res_[]=$row['Field'];
    }
    return $res_;
  }
  /**
   get the number of row in $table
   @param string $table table name
   @param string $conds conditions
   @return int
  */
  function get_count($table, $conds = NULL){
    return $this->select_single_value($table,'count(*) as c', $conds);
  }
  /**
   return an array of databases names on server
   @return array
  */
  function list_dbs(){
    if(! $dbs = $this->query_to_array_or_false("SHOW databases",MYSQLI_NUM))
      return FALSE;
    # showvar($dbs);
    foreach($dbs as $db){
      $dbs_[]=$db[0];
    }
    return $dbs_;
  }
  /**
   get the table list from $this->dbname
   @return array
  */
  function list_tables(){
    if(! $tables = $this->query_to_array_or_false('SHOW tables',MYSQLI_NUM) )
      return FALSE;
    foreach($tables as $v){
      $ret[] = $v[0];
    }
    return $ret;
  }
  /**
   get the fields list of table
   @param string $table
   @param bool $indexed_by_name the return array will be indexed by the fields name if set to true (default is FALSE)
   @return array
   @TODO prendre en compte le second argument qui pose probleme
  */
  function list_fields($table,$indexed_by_name=FALSE){
    if(! $this->query_to_array_or_false("Show fields from $table"))
      return FALSE;
    return $this->associative_array_from_q2a_res('Field',null,null,TRUE);
  }
  function show_table_keys($table){
    return $this->query_to_array_or_false("SHOW KEYS FROM $table");
  }
  /**
   dump the database to a file
   @param string $out_file name of the output file
   @param bool $droptables add 'drop table'  if set to true (defult=TRUE)
   @param bool $gziped (default = TRUE) if set to true output will be compressed
   @param gtkprogress &$progress is an optional progressbar to trace activity (will received a value between 0 to 100)
  */
  function dump_to_file($out_file,$droptables=TRUE,$gziped=TRUE){
    if($gziped){
      if(! $fout = gzopen($out_file,'w'))
        return FALSE;
    }else{
      if(! $fout = fopen($out_file,'w'))
        return FALSE;
    }
    $entete = "# PHP class mysqldb SQL Dump\n#\n# Host: $this->host\n# generate on: ".date("Y-m-d")."\n#\n# Db name: `$this->dbname`\n#\n#\n# --------------------------------------------------------\n\n";
    if($gziped){
      gzwrite($fout,$entete);
    }else{
      fwrite($fout,$entete);
    }
    $tables = $this->list_tables();
    foreach($tables as $table){
      $table_create = $this->query_to_array_or_false("SHOW CREATE TABLE $table",MYSQLI_NUM);
      $table_create = $table_create[0]; # now we have the create statement
      $create_str = "\n\n#\n# Table Structure `$table`\n#\n\n".($droptables?"DROP TABLE IF EXISTS {$table};\n":'').$table_create[1].";\n";
      if($gziped){
        gzwrite($fout,$create_str);
      }else{
        fwrite($fout,$create_str);
      }
      $i=0;#initialiser au debut d'une table compteur de ligne
      if($tabledatas = $this->select_to_array_or_false($table)){ # si on a des donn�es ds la table on les mets
        if($gziped){
          gzwrite($fout,"\n# `$table` DATAS\n\n");
        }else{
          fwrite($fout,"\n# `$table` DATAS\n\n");
        }
        unset($stringsfields);$z=0;
        
        foreach($tabledatas as $row){
          unset($values,$fields);
          foreach($row as $field=>$value){
            if($i==0){ # on the first line we get fields 
              $fields[] = "`$field`";
              if( mysql_field_type($this->last_qres,$z++) == 'string') # will permit to correctly protect number in string fields
                $stringsfields[$field]  = TRUE;
            }
            if(preg_match("!^-?\d+(\.\d+)?$!",$value) && !$stringsfields[$field]){
              $value = $value;
            }elseif($value==null){
              $value =  $stringsfields[$field]?"''":"NULL";
            }else{
              $value = "'".$this->sanitize($value)."'";
            }
            $values[] = $value;
          }
          $insert_str = ($i==0 ? "INSERT INTO `{$table}` (".implode(',',$fields).")\n       VALUES ":",\n")."(".implode(',',$values).')';
          if($gziped){
            gzwrite($fout,$insert_str);
          }else{
            fwrite($fout,$insert_str);
          }
          $i++; # increment line number
        }
        if($gziped)
          gzwrite($fout,";\n\n");
        else
          fwrite($fout,";\n\n");
      }
    }
    if($gziped){
      gzclose($fout);
    }else{
      fclose($fout);
    }
  }
  /**
  *return an associative array indexed by $index_field with values $value_fields from
  *a mysqldb->select_to_array result
  *@param string $index_field default value is id
  *@param mixed $value_fields (string field name or array of fields name default is null so keep all fields
  *@param array $res the mysqldb->select_to_array result
  *@param bool $keep_index if set to true then the index field will be keep in the values associated (unused if $value_fields is string)
  *@param bool $sort_keys will automaticly sort the array by key if set to true @deprecated argument
  *@return array
  */
  function associative_array_from_q2a_res($index_field='id',$value_fields=null,$res = null,$keep_index=FALSE,$sort_keys=FALSE){
    if($res===null){
      $res = $this->last_q2a_res;
    }
    if(! is_array($res)){
      $this->verbose("[error] mysqldb::associative_array_from_q2a_res with invalid result\n");
      return FALSE;
    }
    # then verify index exists
    if(!isset($res[0][$index_field])){
      $this->verbose("[error] mysqldb::associative_array_from_q2a_res with invalid index field '$index_field'\n");
      return FALSE;
    }
    # then we do the trick
    if(is_string($value_fields)){
      foreach($res as $row){
          $associatives_res[$row[$index_field]] = $row[$value_fields];
      }
    }elseif(is_array($value_fields)||$value_fields===null){
      foreach($res as $row){
        $associatives_res[$row[$index_field]] = $row;
        if(!$keep_index)
          unset($associatives_res[$row[$index_field]][$index_field]);
      }
    }
    if(! count($associatives_res)){
      return FALSE;
    }
    if($sort_keys){
      ksort($associatives_res); 
    }
    return $this->last_q2a_res = $associatives_res;
  }

  //warning for mysqli_real_escape_string(): if you make a large number of calls to mysql_real_escape_string it will slow down your database server. Moreover if you mistakenly call the function twice on the same data you will end up with incorrect information in your database.
  //when character set-unaware escaping is used (for example, addslashes() in PHP), it is possible to bypass the escaping in some multi-byte character sets (for example, SJIS, BIG5 and GBK)
  function sanitize($str){
    if($str){
        return mysqli_real_escape_string($this->conn,$str);
    }else{
        return '';
    }
  }
  /*########## INTERNAL METHOD ##########*/
  /**
   used by other methods to parse the conditions param of a QUERY
   @param string|array $conds
   @return string
   @private
  */
  function process_conds($conds=null){
    if(is_array($conds)){
      $WHERE = ($conds['WHERE']?'WHERE '.$this->array_to_str($conds['WHERE']):'');
      $WHERE.= ($WHERE?' ':'').$this->array_to_str($conds);
      $GROUP = ($conds['GROUP']?'GROUP BY '.$this->array_to_str($conds['GROUP']):'');
      $ORDER = ($conds['ORDER']?'ORDER BY '.$this->array_to_str($conds['GROUP']):'');
      $LIMIT = ($conds['LIMIT']?'LIMIT '.$conds['LIMIT']:'');
      $conds_str = "$WHERE $ORDER $GROUP $LIMIT";
    }elseif(is_string($conds)){
      $conds_str = $conds;
      //sanitizing needs to be done when passing the params to the select func
    }
    return $conds_str;
  }

  /**
   * Handle mysql Error
   */
  private function set_error(){
    static $i=0;
    if(! $this->db ){
      $this->error[$i]['nb'] =$this->error['nb'] = null;
      $this->error[$i]['str'] =$this->error['str'] = '[ERROR] No Db Handler';
    }else{
      $this->error[$i]['nb'] = $this->error['nb'] = mysqli_errno($this->conn);
      $this->error[$i]['str']= $this->error['str'] = mysqli_error($this->conn);
    }
    $this->last_error = $this->error[$i];
    $this->verbose($this->error[$i]['str']);
    print_r($this->last_error['str']);
    $i++;
  }
  function array_to_str($var,$sep=','){
    if(is_string($var)){
      return $var;
    }elseif(is_array($var)){
      return implode($sep,$var);
    }else{
      return FALSE;
    }
  }

  /**
   print a msg on STDOUT if $this->beverbose is true
   @param string $string
   @private
  */
  function verbose($string){
    if($this->beverbose){
      echo $string;
    }
  }
}
?>