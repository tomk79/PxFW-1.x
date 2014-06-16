<?php
/**
 * class px_cores_dbh
 * 
 * PxFWのコアオブジェクトの1つ `$dbh` のオブジェクトクラスを定義します。
 * 
 * @author Tomoya Koyanagi <tomk79@gmail.com>
 */
/**
 * PxFW core object class: Database Handler
 * 
 * PxFWのコアオブジェクトの1つ `$dbh` のオブジェクトクラスです。
 * このオブジェクトは、PxFWの初期化処理の中で自動的に生成され、`$px` の内部に格納されます。
 * 
 * メソッド `$px->dbh()` を通じてアクセスします。
 * 
 * シンプルなファイル(例えばCSVやJSON、XMLなど)もデータとして捉えれば、データベースハンドラはファイル操作機能も持っているべきという考えから、
 * PxFWの `$dbh` は、データベース管理システムの操作機能の他に、ファイルやディレクトリを操作する機能も備えるようになりました。
 * 
 * @author Tomoya Koyanagi <tomk79@gmail.com>
 */
class px_cores_dbh{
	//  【ファイル内目次】
	//  ファイル/ディレクトリ操作関連：allabout_filehandle
	//  データベース操作関連：allabout_dbhandle
	//  パス処理系メソッド：path_operators
	//  その他：allabout_others

	/**
	 * $pxオブジェクト
	 */
	private $px;

	/**
	 * 自動トランザクション設定
	 */
	private $auto_transaction_flg = false;
	/**
	 * 自動コミット設定
	 */
	private $auto_commit_flg = false;
	/**
	 * 接続に挑戦する回数
	 */
	private $try2connect_count = 1;

	/**
	 * データベースとのコネクション
	 * 
	 * PDOを使用する場合は、PDOオブジェクト。
	 */
	private $connection = null;
	/**
	 * エラーリスト
	 */
	private $errorlist = array();
	/**
	 * RDBクエリの実行結果リソース
	 * 
	 * PDOを使用する場合は、PDOStatementオブジェクト。
	 */
	private $result = null;
	/**
	 * トランザクションフラグ
	 */
	private $transaction_flg = false;
	/**
	 * ファイルオープンリソースのリスト
	 */
	private $file = array();

	/**
	 * Slow Queryと判断されるまでの時間
	 */
	private $slow_query_limit = 0.5;

	/**
	 * コールバックメソッド
	 */
	private $method_eventhdl_connection_error;
	/**
	 * コールバックメソッド
	 */
	private $method_eventhdl_query_error;

	/**
	 * ファイルおよびディレクトリ操作時のデフォルトパーミッション
	 */
	private $default_permission = array('dir'=>0775,'file'=>0775);

	/**
	 * データベース設定
	 */
	private $db_conf = array(
		'dbms'=>null ,           //RDBMSの種類。mysql|postgresql|sqlite
		'host'=>null ,           //接続先ホスト名
		'port'=>null ,           //接続先ポート番号
		'database_name'=>null ,  //データベース名。(sqliteの場合はDBファイルのパス)
		'user'=>null ,           //ユーザー名
		'password'=>null ,       //パスワード
		'charset'=>null ,        //文字セット
	);

	/**
	 * コンストラクタ
	 * 
	 * @param object $px $pxオブジェクト
	 */
	public function __construct( $px ){
		$this->px = $px;

		if( strlen( $this->px->get_conf('system.file_default_permission') ) ){
			$this->default_permission['file'] = octdec($this->px->get_conf('system.file_default_permission'));
		}
		if( strlen( $this->px->get_conf('system.dir_default_permission') ) ){
			$this->default_permission['dir'] = octdec($this->px->get_conf('system.dir_default_permission'));
		}
	}

	#******************************************************************************************************************
	#	データベース操作関連
	#	anch: allabout_dbhandle

	/**
	 * データベースを設定する。
	 *
	 * @param array $db_conf 設定内容を格納した連想配列
	 * <dl>
	 *   <dt>$db_conf['dbms']</dt>
	 *     <dd>DBMS名(mysql|postgresql|sqlite)</dd>
	 *   <dt>$db_conf['host']</dt>
	 *     <dd>DBサーバーのホスト名</dd>
	 *   <dt>$db_conf['port']</dt>
	 *     <dd>ポート番号</dd>
	 *   <dt>$db_conf['database_name']</dt>
	 *     <dd>データベーススキーマ名</dd>
	 *   <dt>$db_conf['user']</dt>
	 *     <dd>ユーザー名</dd>
	 *   <dt>$db_conf['password']</dt>
	 *     <dd>パスワード</dd>
	 *   <dt>$db_conf['charset']</dt>
	 *     <dd>文字セット</dd>
	 * </dl>
	 * 
	 * @return bool 常に `true`
	 */
	public function set_db_conf( $db_conf ){
		foreach( $db_conf as $key=>$val ){
			$this->db_conf[$key] = $val;
		}
		return true;
	}

	/**
	 * データベース設定取り出す。
	 *
	 * @param string $key 設定項目名
	 * @return string 設定値
	 */
	public function get_db_conf( $key ){
		if( !array_key_exists($key, $this->db_conf) ){ return null; }
		return $this->db_conf[$key];
	}//get_db_conf()

	/**
	 * データベースに接続する。
	 * 
	 * @return bool 接続が成功した場合、既に確立された接続がある場合に `true`、接続に失敗した場合に `false` を返します。
	 */
	public function connect(){
		if( $this->check_connection() ){ return true; }

		if( class_exists('PDO') ){
			#--------------------------------------
			#	【 PDO 】
			$available_drivers = PDO::getAvailableDrivers();
			switch( $this->get_db_conf('dbms') ){
				case 'mysql':
					$this->connection = new PDO(
						'mysql:host='.$this->get_db_conf('host').(strlen($this->get_db_conf('port'))?':'.$this->get_db_conf('port'):'').'; dbname='.$this->get_db_conf('database_name').'' ,
						$this->get_db_conf('user') ,
						$this->get_db_conf('password')
					);
					return true;
					break;
				case 'postgresql':
					$this->connection = new PDO(
						'pgsql:dbname='.$this->get_db_conf('database_name').'; host='.$this->get_db_conf('host').'; port='.$this->get_db_conf('port').'' ,
						$this->get_db_conf('user') ,
						$this->get_db_conf('password')
					);
					return true;
					break;
				case 'sqlite':
					$this->connection = new PDO(
						'sqlite:'.$this->get_db_conf('database_name')
					);

					return true;
					break;
				default:
					break;
			}

		}elseif( $this->get_db_conf('dbms') == 'mysql' ){
			#--------------------------------------
			#	【 MySQL 】
			$server = $this->get_db_conf('host');
			if( strlen( $this->get_db_conf('port') ) ){
				$server .= ':'.$this->get_db_conf('port');
			}
			$try_counter = 0;
			while( $res = @mysql_connect( $server , $this->get_db_conf('user') , $this->get_db_conf('password') , true ) ){
				$try_counter ++;
				if( is_resource( $res ) ){
					break;
				}
				if( $try_counter >= $this->try2connect_count ){
					break;
				}
				sleep(1);
			}
			if( is_resource( $res ) ){
				$this->connection = $res;
				mysql_select_db( $this->get_db_conf('database_name') , $this->connection );
				if( strlen( $this->get_db_conf('charset') ) ){
					#	DB文字コードの指定があれば、
					#	SET NAMES を発行。
					@mysql_query( 'SET NAMES '.addslashes( $this->get_db_conf('charset') ).';' , $this->connection );
				}
				return	true;
			}else{
				$this->add_error( 'DB connect was faild. DB Type of ['.$this->get_db_conf('dbms').'] Server ['.$this->get_db_conf('host').']' , null , __FILE__ , __LINE__ );
				$this->eventhdl_connection_error( 'Database Connection Error.' , __FILE__ , __LINE__ );	//	DB接続エラー時のコールバック関数
				return	false;
			}

		}elseif( $this->get_db_conf('dbms') == 'postgresql' ){
			#--------------------------------------
			#	【 PostgreSQL 】
			$pg_connect_string = '';
			if( strlen( $this->get_db_conf('host') ) ){
				$pg_connect_string .= 'host='.$this->get_db_conf('host').' ';
			}
			if( strlen( $this->get_db_conf('port') ) ){
				$pg_connect_string .= 'port='.$this->get_db_conf('port').' ';
			}
			if( strlen( $this->get_db_conf('user') ) ){
				$pg_connect_string .= 'user='.$this->get_db_conf('user').' ';
			}
			if( strlen( $this->get_db_conf('password') ) ){
				$pg_connect_string .= 'password='.$this->get_db_conf('password').' ';
			}
			if( strlen( $this->get_db_conf('database_name') ) ){
				$pg_connect_string .= 'dbname='.$this->get_db_conf('database_name').' ';
			}

			$try_counter = 0;
			while( $res = @pg_connect( $pg_connect_string , true ) ){
				$try_counter ++;
				if( is_resource( $res ) ){
					break;
				}
				if( $try_counter >= $this->try2connect_count ){
					break;
				}
				sleep(1);
			}
			if( is_resource( $res ) ){
				$this->connection = $res;
				return	true;
			}else{
				$this->add_error( 'DB connect was faild. DB Type of ['.$this->get_db_conf('dbms').'] Server ['.$this->get_db_conf('host').']' , null , __FILE__ , __LINE__ );
				$this->eventhdl_connection_error( 'Database Connection Error. ' , __FILE__ , __LINE__ );	//	DB接続エラー時のコールバック関数
				return	false;
			}

		}elseif( $this->get_db_conf('dbms') == 'sqlite' ){
			#--------------------------------------
			#	【 SQLite 】
			$res = sqlite_open( $this->get_db_conf('database_name') , 0666 , $sqlite_error_msg );
			if( is_resource( $res ) ){
				$this->connection = $res;
				return	true;
			}else{
				$this->add_error( 'DB connect was faild. Because:['.$sqlite_error_msg.']. DB Type of ['.$this->get_db_conf('dbms').'] DB ['.$this->get_db_conf('database_name').']' , null , __FILE__ , __LINE__ );
				$this->eventhdl_connection_error( 'Database Connection Error. ' , __FILE__ , __LINE__ );	//	DB接続エラー時のコールバック関数
				return	false;
			}

		}
		$this->add_error( 'Pickles Framework は、現在 '.$this->get_db_conf('dbms').' をサポートしていません。' , 'connect' , __FILE__ , __LINE__ );
		$this->eventhdl_connection_error( 'Database Connection Error. Unknown DB Type ['.$this->get_db_conf('dbms').'] ' , __FILE__ , __LINE__ );	//	DB接続エラー時のコールバック関数
		return false;
	}//connect()

	/**
	 * すでに確立されたデータベース接続情報を外部から受け入れる。
	 * 
	 * 既に内部で接続が確立している場合は、受け入れが失敗し、`false` を返します。
	 *
	 * @param resource $con 接続リソース
	 * @return 受け入れられた場合に `true`、受け入れられなかった場合に `false` を返します。
	 */
	public function set_connection( $con ){
		if( $this->check_connection() ){
			#	内部の接続が有効であれば、
			#	外部からの接続情報は受け入れない
			return false;
		}
		$this->connection = $con;
		return true;
	}

	/**
	 * データベースコネクション$conが有効かどうか確認する。
	 * 
	 * @param resource $con データベースコネクション(省略時はオブジェクト内部のconnectionプロパティで調べる)
	 * @return bool 接続が有効な場合に `true`、無効な場合に `false` を返します。
	 */
	public function check_connection( $con = null ){
		if( !is_resource( $con ) && !is_object( $con ) ){
			$con = $this->connection;
		}
		if( !is_resource( $con ) && !is_object( $con ) ){ return false; }

		if( class_exists('PDO') ){
			#--------------------------------------
			#	【 PDO 】
			return true;
		}//if( class_exists('PDO') )

		if( $this->get_db_conf('dbms') == 'mysql' ){
			#--------------------------------------
			#	【 MySQL 】
			if( !@mysql_ping( $con ) ){
				return false;
			}
			return true;

		}elseif( $this->get_db_conf('dbms') == 'postgresql' ){
			#--------------------------------------
			#	【 PostgreSQL 】
			if( !@pg_ping( $con ) ){
				return false;
			}
			return true;

		}elseif( $this->get_db_conf('dbms') == 'sqlite' ){
			#--------------------------------------
			#	【 SQLite 】
			if( !is_resource( $con ) ){
				return false;
			}
			return true;

		}
		return true;
	}//check_connection()

	/**
	 * 直前のクエリで処理された件数を得る。
	 * 
	 * MySQLとPostgreSQLでは、渡すべきリソース $res の種類が異なります。
	 * MySQLには接続リソース、PostgreSQLには、前回のクエリの実行結果リソースを渡します。
	 * 
	 * PostgreSQLの場合には、前回のクエリの実行時に記憶した結果リソース `$this->result` を自動的に適用します。
	 * 
	 * このため、基本的に、引数 `$res` は指定しないで使うことを想定しています。
	 * 
	 * 明示的に `$res` を指定する場合は、呼び出し元側でデータベースの種類に応じた判断がされている必要があります。
	 * 
	 * @param resource|object $res 接続リソース(MySQL、SQLite)、またはリクエスト結果リソース(PostgreSQL)。省略時は自動的に処理する。
	 * @return int 処理された件数
	 */
	public function get_affected_rows( $res = null ){
		if( !is_null( $res ) && !is_resource( $res ) && !is_object( $res ) ){
			#	何かを渡してるのに、リソース型でもオブジェクト型でもなかったらダメ。
			return false;
		}

		if( class_exists('PDO') ){
			#--------------------------------------
			#	【 PDO 】
			$RTN = $this->result->rowCount();
			return $RTN;
		}//if( class_exists('PDO') )

		if( $this->get_db_conf('dbms') == 'mysql' ){
			#--------------------------------------
			#	【 MySQL 】
			if( !is_resource( $res ) ){
				#	MySQLは、接続リソースをとる。
				#	ゆえに、直前のクエリの結果しか知れない。
				$res = $this->connection;
			}
			return @mysql_affected_rows( $res );

		}elseif( $this->get_db_conf('dbms') == 'postgresql' ){
			#--------------------------------------
			#	【 PostgreSQL 】
			if( !is_resource( $res ) ){
				#	PostgreSQLは、リクエストの結果のリソースをとる。
				$res = $this->result;
			}
			return @pg_affected_rows( $res );

		}elseif( $this->get_db_conf('dbms') == 'sqlite' ){
			#--------------------------------------
			#	【 SQLite 】
			if( !is_resource( $res ) ){
				#	SQLiteは、接続リソースをとる。
				#	ゆえに、直前のクエリの結果しか知れない。
				$res = $this->connection;
			}
			return	@sqlite_changes( $res );

		}
		return false;
	}//get_affected_rows()

	/**
	 * トランザクションを開始する。
	 * 
	 * @return bool|null トランザクションが成功したら `true`、失敗したら `false`、前回のトランザクション中なら `null` を返します。
	 */
	public function start_transaction(){
		$this->connect();
		if( !$this->is_transaction() ){
			$this->transaction_flg = true;
			if( class_exists('PDO') ){
				#	PDOの処理
				return $this->connection->beginTransaction();
			}

			$sql = 'START TRANSACTION;';
			if( $this->get_db_conf('dbms') == 'sqlite' ){
				#	SQLiteの処理
				$sql = 'BEGIN TRANSACTION;';
			}
			$result = $this->execute_send_query( $sql , $this->connection );
			return $result;
		}
		return null;
	}

	/**
	 * トランザクション：コミットする。
	 *
	 * @return 成功したら `true`、失敗したら `false` を返します。
	 * トランザクションが開始されていない場合は、`true` を返します。
	 */
	public function commit(){
		$this->connect();
		if( !$this->is_transaction() ){
			#	トランザクション中じゃなかったらコミットしない。
			return true;
		}
		$this->transaction_flg = false;
		if( class_exists('PDO') ){
			#	PDOの処理
			return $this->connection->commit();
		}
		if( $this->get_db_conf('dbms') == 'sqlite' ){
			#	SQLiteの処理
			return $this->execute_send_query( 'COMMIT TRANSACTION;' , $this->connection );
		}
		return $this->execute_send_query( 'COMMIT;' , $this->connection );
	}

	/**
	 * トランザクション：ロールバックする。
	 *
	 * @return 成功したら `true`、失敗したら `false` を返します。
	 * トランザクションが開始されていない場合は、`true` を返します。
	 */
	public function rollback(){
		$this->connect();
		if( !$this->is_transaction() ){
			#	トランザクション中じゃなかったらロールバックもしない。
			return true;
		}
		$this->transaction_flg = false;
		if( class_exists('PDO') ){
			#	PDOの処理
			return $this->connection->rollBack();
		}
		if( $this->get_db_conf('dbms') == 'sqlite' ){
			#	SQLiteの処理
			return $this->execute_send_query( 'ROLLBACK TRANSACTION;' , $this->connection );
		}
		return $this->execute_send_query( 'ROLLBACK;' , $this->connection );
	}

	/**
	 * トランザクション：トランザクション中かどうか返す
	 *
	 * @return トランザクション中なら `true`、それ以外なら `false` を返します。
	 */
	public function is_transaction(){
		return	$this->transaction_flg;
	}

	/**
	 * データベースにクエリを送る。
	 * 
	 * @param string $querystring SQL文
	 * @return mixed クエリ送信に失敗した場合は `false` を返します。成功した場合は、リソースまたはオブジェクトを返します(使用するDBMSによって異なります)。
	 */
	public function &send_query( $querystring ){
		if( !is_string( $querystring ) ){ return false; }
		$this->connect();
		if( $this->auto_transaction_flg ){
			$this->start_transaction();
		}
		$this->result = $this->execute_send_query( $querystring );
		return $this->result;
	}//send_query()
	
	/**
	 * 実際にクエリを送信する
	 * 
	 * @param string $querystring SQL文
	 * @return mixed クエリ送信に失敗した場合は `false` を返します。成功した場合は、リソースまたはオブジェクトを返します(使用するDBMSによって異なります)。
	 */
	private function &execute_send_query( $querystring ){
		$this->connect();

		list( $microtime , $time ) = explode( ' ' , microtime() ); 
		$start_mtime = ( floatval( $time ) + floatval( $microtime ) );

		if( class_exists('PDO') ){
			#--------------------------------------
			#	【 PDO 】
			$RTN = $this->connection->prepare($querystring);
			if( $RTN ){
				if( !$RTN->execute() ){
					$RTN = false;
				}
			}
			unset($PDO_stmt);

		}elseif( $this->get_db_conf('dbms') == 'mysql' ){
			#--------------------------------------
			#	【 MySQL 】
			$RTN = @mysql_query( $querystring , $this->connection );	//クエリを投げる。

		}elseif( $this->get_db_conf('dbms') == 'postgresql' ){
			#--------------------------------------
			#	【 PostgreSQL 】
			$RTN = @pg_query( $this->connection , $querystring );	//クエリを投げる。

		}elseif( $this->get_db_conf('dbms') == 'sqlite' ){
			#--------------------------------------
			#	【 SQLite 】
			$RTN = @sqlite_query( $this->connection , $querystring );	//クエリを投げる。

		}else{
			#	【 想定外のDB 】
			$debug = debug_backtrace();
			$FILE = $debug[1]['file'];
			$LINE = $debug[1]['line'];

			$SQL2ErrorMessage = preg_replace( '/(?:\r\n|\r|\n|\t| )+/i' , ' ' , $querystring );
			$this->add_error( '['.$this->get_db_conf('dbms').']は、未対応のデータベースです。 SQL[ '.$SQL2ErrorMessage.' ]' , 'sendQuery' , $FILE , $LINE );
			$this->eventhdl_query_error( 'DB Query Error. ['.$this->get_db_conf('dbms').']は、未対応のデータベースです。 SQL[ '.$SQL2ErrorMessage.' ]' , $FILE , $LINE );	//	クエリエラー時のコールバック関数

			return false;
		}

		list( $microtime , $time ) = explode( ' ' , microtime() ); 
		$end_mtime = ( floatval( $time ) + floatval( $microtime ) );
		$exec_time = $end_mtime - $start_mtime;
		if( $exec_time >= $this->slow_query_limit ){
			#	1回のクエリに時間がかかっている場合。
			$debug = debug_backtrace();
			$FILE = $debug[1]['file'];
			$LINE = $debug[1]['line'];
			$this->add_error( ''.$this->get_db_conf('dbms').' Heavy Query ['.$exec_time.'] sec. on SQL[ '.preg_replace( '/(?:\r\n|\r|\n|\t| )+/i' , ' ' , $querystring ).' ]' , 'sendQuery' , $FILE , $LINE );
		}

		if( $RTN === false ){
			#	クエリに失敗したときのエラー処理
			$debug = debug_backtrace();
			$FILE = $debug[1]['file'];
			$LINE = $debug[1]['line'];

			$SQL2ErrorMessage = preg_replace( '/(?:\r\n|\r|\n|\t| )+/i' , ' ' , $querystring );
			$error_report = $this->get_sql_error();
			$DB_ERRORMSG = $error_report['message'];
			$this->add_error( ''.$this->get_db_conf('dbms').' Query Error! ['.$DB_ERRORMSG.'] on SQL[ '.$SQL2ErrorMessage.' ]' , 'sendQuery' , $FILE , $LINE );
			$this->eventhdl_query_error( ''.$this->get_db_conf('dbms').' Query Error. ['.$DB_ERRORMSG.'] on SQL[ '.$SQL2ErrorMessage.' ]' , $FILE , $LINE );	//	クエリエラー時のコールバック関数

		}

		return $RTN;

	}

	/**
	 * SQL文に値をバインドする。
	 * 
	 * @param string $sql SQLテンプレート
	 * @param array $vars バインドする値とキーの一覧
	 * @return string 完成されたSQL
	 */
	public function bind( $sql , $vars = array() ){
		#	数値型の場合 => :N:key
		#	文字列型の場合 => :S:key
		#	そのまま置き換え(テーブル名、フィールド名などの時に使用) => :D:key
		#	のルールで、メタ文字を仕込む。
		#	エラーがある場合は、falseを返す

		if( !is_array( $vars ) ){ return false; }

		$ptn = '/^(.*?)(:(s|S|n|N|d|D):([a-zA-Z0-9_-]+))(.*)$/sm';

		$RTN = '';
		while( strlen( $sql ) ){
			if( !preg_match( $ptn , $sql , $matches ) ){
				$RTN .= $sql;
				break;
			}

			$loopcounter ++;
			$RTN .= $matches[1];
			$value = $vars[$matches[4]];
			if( is_null( $value ) ){
				$RTN .= 'NULL';
			}else{
				switch( strtolower( $matches[3] ) ){
					case 'd':
						$RTN .= $value;
						break;
					case 'n':
						$RTN .= intval( $value );
						break;
					case 's':
						if( $this->get_db_conf('dbms') == 'sqlite' ){
							#	SQLiteの場合は、エスケープにバックスラッシュを使用せず、
							#	シングルクオートを重ねる仕様になっているらしい。
							#	よって、addslashes() では対応できない。
							$sqlite_valMemo = $value;
							$sqlite_valMemo = preg_replace( '/\'/' , '\'\'' , $sqlite_valMemo );
							$RTN .= '\''.$sqlite_valMemo.'\'';
							unset( $sqlite_valMemo );
						}else{
							$RTN .= '\''.addslashes( $value ).'\'';
						}
						break;
					default:
						$RTN .= $vars[$matches[2]];
						break;
				}
			}

			$sql = $matches[5];
			continue;
		}

		return $RTN;
	}//bind()

	/**
	 * クエリの実行結果を得る。
	 * 
	 * @param resource|object $res 接続リソース、またはクエリ実行リソース
	 * @return array 取得された行列データ
	 */
	public function get_results( $res = null ){
		$RTN = array();

		if( !$res ){ $res = $this->result; }
		if( is_bool( $res ) ){ return $res; }
		if( !is_resource( $res ) && !is_object( $res ) ){ return array(); }

		if( class_exists('PDO') ){
			#--------------------------------------
			#	【 PDO 】
			$RTN = $this->result->fetchAll(PDO::FETCH_ASSOC);
			return $RTN;
		}//if( class_exists('PDO') )

		if( $this->get_db_conf('dbms') == 'mysql' ){
			#--------------------------------------
			#	【 MySQL 】
			while( $Line = mysql_fetch_assoc( $res )){ array_push( $RTN , $Line ); }
			return $RTN;
		}elseif( $this->get_db_conf('dbms') == 'postgresql' ){
			#--------------------------------------
			#	【 PostgreSQL 】
			while( $Line = pg_fetch_assoc( $res )){ array_push( $RTN , $Line ); }
			return $RTN;
		}elseif( $this->get_db_conf('dbms') == 'sqlite' ){
			#--------------------------------------
			#	【 SQLite 】
			while( $Line = sqlite_fetch_array( $res , SQLITE_ASSOC )){ array_push( $RTN , $Line ); }
			return $RTN;
		}
		$this->add_error( $this->get_db_conf('dbms').'は、未対応のデータベースです。' , 'get_results' , __FILE__ , __LINE__ );
		return null;
	}//get_results()

	/**
	 * クエリの実行結果を1行ずつ得る。
	 * 
	 * @param resource|object $res 接続リソース、またはクエリ実行リソース
	 * @return array 取得された1行分の行列データ
	 */
	public function fetch_assoc( $res = null ){
		$RTN = array();
		if( !$res ){ $res = $this->result; }
		if( is_bool( $res ) ){ return $res; }
		if( !is_resource( $res ) && !is_object( $res ) ){ return array(); }

		if( class_exists('PDO') ){
			#--------------------------------------
			#	【 PDO 】
			$RTN = $this->result->fetch(PDO::FETCH_ASSOC);
			return $RTN;
		}//if( class_exists('PDO') )

		if( $this->get_db_conf('dbms') == 'mysql' ){
			#--------------------------------------
			#	【 MySQL 】
			$RTN = mysql_fetch_assoc( $res );
			return	$RTN;
		}elseif( $this->get_db_conf('dbms') == 'postgresql' ){
			#--------------------------------------
			#	【 PostgreSQL 】
			$RTN = pg_fetch_assoc( $res );
			return	$RTN;
		}elseif( $this->get_db_conf('dbms') == 'sqlite' ){
			#--------------------------------------
			#	【 SQLite 】
			$RTN = sqlite_fetch_array( $res , SQLITE_ASSOC );
			return	$RTN;
		}
		$this->add_error( $this->get_db_conf('dbms').'は、未対応のデータベースです。' , 'fetch_assoc' , __FILE__ , __LINE__ );
		return	null;
	}

	/**
	 * 直前のクエリのエラー報告を受けとる。
	 * 
	 * @return array エラー情報を格納した連想配列
	 */
	public function get_sql_error(){
		if( class_exists('PDO') ){
			#--------------------------------------
			#	【 PDO 】
			$errornum = null;
			$errormsg = null;
			if( $this->result ){
				$errornum = $this->result->errorCode();
				$errormsg = $this->result->errorInfo();
			}
			return	array( 'message'=>$errormsg , 'number'=>$errornum );
		}//if( class_exists('PDO') )

		if( $this->get_db_conf('dbms') == 'mysql' ){
			#--------------------------------------
			#	【 MySQL 】
			$errornum = mysql_errno( $this->connection );
			$errormsg = mysql_error( $this->connection );
			return	array( 'message'=>$errormsg , 'number'=>$errornum );

		}elseif( $this->get_db_conf('dbms') == 'postgresql' ){
			#--------------------------------------
			#	【 PostgreSQL 】
			$errormsg = pg_last_error( $this->connection );
			$result_error = pg_result_error( $this->result );
			return	array( 'message'=>$errormsg , 'number'=>null , 'result_error'=>$result_error );

		}elseif( $this->get_db_conf('dbms') == 'sqlite' ){
			#--------------------------------------
			#	【 SQLite 】
			$error_cd = sqlite_last_error( $this->connection );
			$errormsg = sqlite_error_string( $error_cd );
			return	array( 'message'=>$errormsg , 'number'=>$error_cd );

		}
		$this->add_error( $this->get_db_conf('dbms').'は、未対応のデータベースです。' , 'get_sql_error' , __FILE__ , __LINE__ );
		return	array( 'message'=>$this->get_db_conf('dbms').'は、未対応のデータベースです。' );
	}

	/**
	 * 直前のクエリ(INSERT)で挿入されたレコードのIDを得る。
	 * 
	 * @param resource|object $res 接続リソース、またはクエリ実行リソース
	 * @param string $seq_table_name 直前のクエリで挿入処理を行ったテーブルのテーブル名(PostgreSQLの場合のみ必要)
	 * @return array 取得された1行分の行列データ
	 */
	public function get_last_insert_id( $res = null , $seq_table_name = null ){
		#--------------------------------------
		#	$res のリソース型は、データベースによって異なります。
		#	これを判断するのは、呼び出し元の責任となります。
		#	省略した場合は、自動的に選択します。
		#--------------------------------------

		if( class_exists('PDO') ){
			#--------------------------------------
			#	【 PDO 】
			switch( $this->get_db_conf('dbms') ){
				case 'postgresql':
					$RTN = $this->connection->lastInsertId( $seq_table_name );
					break;
				default:
					$RTN = $this->connection->lastInsertId();
					break;
			}
			return $RTN;

		}//if( class_exists('PDO') )

		if( $this->get_db_conf('dbms') == 'mysql' ){
			#--------------------------------------
			#	【 MySQL 】
			if( !$res ){ $res = $this->connection; }
			$RTN = mysql_insert_id( $res );
			return	$RTN;

		}elseif( $this->get_db_conf('dbms') == 'postgresql' ){
			#--------------------------------------
			#	【 PostgreSQL 】
			if( !strlen( $seq_table_name ) ){ return false; }//PostgreSQLでは必須
			if( !$res ){ $res = $this->result; }

			$result = @pg_query( $this->connection , 'SELECT CURRVAL(\''.addslashes($seq_table_name).'\') AS seq' );
			$data = @pg_fetch_assoc( $result );
			$RTN = intval( $data['seq'] );
			return	$RTN;

		}elseif( $this->get_db_conf('dbms') == 'sqlite' ){
			#--------------------------------------
			#	【 SQLite 】
			if( !$res ){ $res = $this->connection; }
			$RTN = sqlite_last_insert_rowid( $res );
			return	$RTN;

		}
		$this->add_error( $this->get_db_conf('dbms').'は、未対応のデータベースです。' , 'get_last_insert_id' , __FILE__ , __LINE__ );
		return	array( 'message'=>$this->get_db_conf('dbms').'は、未対応のデータベースです。' );
	}

	#******************************************************************************************************************
	#	その他DB関連

	/**
	 * データベースの文字エンコードタイプを取得する。
	 * 
	 * @return string|bool 取得された文字エンコード名。失敗した場合に `false` を返します。
	 */
	public function get_db_encoding(){
		$this->connect();

		if( class_exists('PDO') ){
			#--------------------------------------
			#	【 PDO 】
			//  PDO非対応
			return	false;
		}//if( class_exists('PDO') )

		if( $this->get_db_conf('dbms') == 'mysql' ){
			#--------------------------------------
			#	【 MySQL 】
			return	mysql_client_encoding( $this->connection );

		}elseif( $this->get_db_conf('dbms') == 'postgresql' ){
			#--------------------------------------
			#	【 PostgreSQL 】
			return	pg_client_encoding( $this->connection );

		}elseif( $this->get_db_conf('dbms') == 'sqlite' ){
			#--------------------------------------
			#	【 SQLite 】
			return	sqlite_libencoding();

		}
		$this->add_error( '未対応のデータベースです。' , 'get_db_encoding' , __FILE__ , __LINE__ );
		return	false;
	}

	/**
	 * 文字セット名の形式をDBの呼び方からPHPの呼び方に変換する。
	 * 
	 * @param string $db_encoding DBMSで使用する文字セット名
	 * @return string PHPで使用される文字セット名
	 */
	public function translate_encoding_db2php( $db_encoding ){
		$db_encoding = strtolower( $db_encoding );
		switch( $db_encoding ){
			case 'unicode':
			case 'utf8_bin':
			case 'utf8_unicode_ci';
			case 'utf8_general_ci';
				return	'UTF-8';
			case 'euc_jp':
			case 'ujis_bin':
			case 'ujis_japanese_ci':
				return	'EUC-JP';
		}
		return	$db_encoding;
	}
	/**
	 * 文字セット名の形式をPHPの呼び方からDBの呼び方に変換する。
	 * 
	 * @param string $php_encoding PHPで使用する文字セット名
	 * @return string DBMSで使用される文字セット名
	 */
	public function translate_encoding_php2db( $php_encoding ){
		$php_encoding = strtolower( $php_encoding );
		if( preg_match( '/utf/i' , $php_encoding ) ){
			if( $this->get_db_conf('dbms') == 'postgresql' ){
				return	'UNICODE';
			}
			return	'utf8_unicode_ci';
		}elseif( preg_match( '/euc/i' , $php_encoding ) ){
			if( $this->get_db_conf('dbms') == 'postgresql' ){
				return	'EUC_JP';
			}
			return	'ujis_japanese_ci';
		}elseif( preg_match( '/sjis|shift_jis/i' , $php_encoding ) ){
			return	'ujis_japanese_ci';
		}
		return	$php_encoding;
	}

	/**
	 * テーブルの一覧を得る。
	 * 
	 * @param string $dbname データベース名
	 * @return array|bool テーブル名を格納した配列を返します。失敗時には `false` を返します。
	 */
	public function get_tablelist( $dbname = null ){
		if( !$dbname ){ $dbname = $this->get_db_conf('database_name'); }
		$this->connect();

		if( $this->get_db_conf('dbms') == 'mysql' ){
			#--------------------------------------
			#	【 MySQL 】
			$tablelist = $this->get_results( mysql_list_tables( $dbname ) );
			if( !is_array( $tablelist ) ){
				$tablelist = array();
			}
			$result = array();
			foreach( $tablelist as $Line ){
				foreach( $Line as $Line2 ){
					array_push( $result , $Line2 );
				}
			}
			return	$result;

		}elseif( $this->get_db_conf('dbms') == 'postgresql' ){
			#--------------------------------------
			#	【 PostgreSQL 】
			ob_start();?>
SELECT c.relname as "table", 'table' as  "type", u.usename as "Owner"
FROM pg_class c LEFT JOIN pg_user u ON c.relowner = u.usesysid
   WHERE c.relkind IN ('r') AND c.relname !~ '^pg_'
ORDER BY 1;
<?php
			$sql = @ob_get_clean();
			$res = $this->send_query( $sql );
			if( !$res ){
				return	false;
			}
			$tablelist = $this->get_results( $res );
			if( !is_array( $tablelist ) ){
				$tablelist = array();
			}
			$result = array();
			foreach( $tablelist as $Line ){
				array_push( $result , $Line['table'] );
			}
			return	$result;

		}elseif( $this->get_db_conf('dbms') == 'sqlite' ){
			#--------------------------------------
			#	【 SQLite 】
			ob_start();?>
SELECT name FROM sqlite_master WHERE type='table' ORDER BY name;
<?php
			$sql = @ob_get_clean();
			$res = $this->send_query( $sql );
			if( !$res ){
				return	false;
			}
			$tablelist = $this->get_results( $res );
			if( !is_array( $tablelist ) ){
				$tablelist = array();
			}
			$result = array();
			foreach( $tablelist as $Line ){
				array_push( $result , $Line['name'] );
			}
			return	$result;

		}
		$this->add_error( '未対応のデータベースです。' , 'get_tablelist' , __FILE__ , __LINE__ );
		return	false;
	}

	/**
	 * テーブルの定義を知る。
	 *
	 * @param string $tablename 調べる対象のテーブル名
	 * @return array|bool 成功時、テーブルの定義を格納した連想配列、失敗時 `false` を返します。
	 */
	public function get_table_definition( $tablename ){
		$this->connect();

		if( class_exists('PDO') ){
			#--------------------------------------
			#	【 PDO 】
			//  PDO非対応
			return	false;
		}//if( class_exists('PDO') )

		if( $this->get_db_conf('dbms') == 'mysql' ){
			#--------------------------------------
			#	【 MySQL 】
			$sql = 'SHOW COLUMNS FROM :D:table_name;';
			$sql = $this->bind( $sql , array( 'table_name'=>$tablename ) );
			$res = $this->send_query( $sql );
			$VALUE = $this->get_results( $res );
			if( !is_array( $VALUE ) ){ return false; }
			$RTN = array();
			$i = 0;
			foreach( $VALUE as $Key=>$Line ){
				$i ++;
				$RTN[$Line['Field']] = array();
				foreach( $Line as $Key2=>$Line2 ){
					$RTN[$Line['Field']][strtolower($Key2)] = $Line2;
				}
				$RTN[$Line['Field']]['num'] = $i;
				$RTN[$Line['Field']]['null'] = (bool)$Line['Null'];
				$RTN[$Line['Field']]['not null'] = empty( $Line['Null'] );
			}
			return	$RTN;

		}elseif( $this->get_db_conf('dbms') == 'postgresql' ){
			#--------------------------------------
			#	【 PostgreSQL 】
			if( !is_callable( 'pg_meta_data' ) ){ return false; }
			$VALUE = pg_meta_data( $this->connection , $tablename );
			if( !is_array( $VALUE ) ){ return false; }
			$RTN = array();
			foreach( $VALUE as $Key=>$Line ){
				$RTN[$Key] = array();
				$RTN[$Key]['field'] = $Key;
				foreach( $Line as $Key2=>$Line2 ){
					$RTN[$Key][strtolower( $Key2 )] = $Line2;
				}
				$RTN[$Key]['null'] = empty( $Line['not null'] );
				$RTN[$Key]['not null'] = (bool)$Line['not null'];
			}
			return	$RTN;

		}elseif( $this->get_db_conf('dbms') == 'sqlite' ){
			#--------------------------------------
			#	【 SQLite 】
			if( !is_callable( 'sqlite_fetch_column_types' ) ){ return false; }
			$VALUE = sqlite_fetch_column_types( $tablename , $this->connection );
			if( !is_array( $VALUE ) ){ return false; }
			$RTN = array();
			foreach( $VALUE as $Key=>$Line ){
				$RTN[$Key] = array();
				$RTN[$Key]['field'] = $Key;
				$RTN[$Key]['type'] = $Line;
			}
			return	$RTN;

		}
		$this->add_error( '未対応のデータベースです。' , 'get_table_definition' , __FILE__ , __LINE__ );
		return	false;
	}

	/**
	 * date型の値を、UNIXタイムスタンプに変換する。
	 * 
	 * @param string $time date型(YYYY-MM-DD) または datetime型(YYYY-MM-DD HH:ii:ss) の文字列
	 * @return int `$time` が示す日の0時0分0秒のUNIXタイムスタンプ
	 */
	public function date2int( $time ){
		if( !preg_match( '/^([0-9]+)-([0-9]+)-([0-9]+)(?: (?:[0-9]+):(?:[0-9]+):(?:[0-9]+))?$/' , $time , $res ) ){
			return	false;
		}
		return	mktime( 0 , 0 , 0 , intval($res[2]) , intval($res[3]) , intval($res[1]) );
	}
	/**
	 * datetime型の値を、UNIXタイムスタンプに変換する。
	 * 
	 * @param string $time date型(YYYY-MM-DD) または datetime型(YYYY-MM-DD HH:ii:ss) の文字列
	 * @return int `$time` が示す時刻のUNIXタイムスタンプ
	 */
	public function datetime2int( $time ){
		#	このメソッドは、PostgreSQLのtimestamp型文字列を吸収します。
		if( !preg_match( '/^([0-9]+)-([0-9]+)-([0-9]+)(?: ([0-9]+):([0-9]+):([0-9]+)(?:\.[0-9]+?)?)?$/' , $time , $res ) ){
			return	false;
		}
		return	@mktime( intval($res[4]) , intval($res[5]) , intval($res[6]) , intval($res[2]) , intval($res[3]) , intval($res[1]) );
	}
	/**
	 * UNIXタイムスタンプの値を、date型に変換
	 * 
	 * @param int $time UNIXタイムスタンプ
	 * @return string date型(YYYY-MM-DD)の文字列
	 */
	public function int2date( $time ){
		return	date( 'Y-m-d' , $time );
	}
	/**
	 * UNIXタイムスタンプの値を、datetime型に変換
	 * 
	 * @param int $time UNIXタイムスタンプ
	 * @return string datetime型(YYYY-MM-DD HH:ii:ss) の文字列
	 */
	public function int2datetime( $time ){
		return	date( 'Y-m-d H:i:s' , $time );
	}


	/**
	 * DBコネクションに失敗した時に実行されるメソッド
	 *
	 * @param string $errorMessage エラーメッセージ
	 * @param string $FILE エラーが発生したファイル
	 * @param int $LINE エラーが発生した行番号
	 * @return bool コールバック関数が利用可能な場合、その関数の返却値を返します。コールバック関数が登録されない場合、何もせずに `true` を返します。
	 */
	private function eventhdl_connection_error( $errorMessage = null , $FILE = null , $LINE = null ){
		$method = $this->method_eventhdl_connection_error;
		if( is_array( $method ) ){
			#	配列を受けていたら
			if( is_object( $method[0] ) && is_string( $method[1] ) ){
				#	オブジェクトとメソッドのセット
				return	eval( 'return	$method[0]->'.$method[1].'( $errorMessage , $FILE , $LINE );' );
			}elseif( is_string( $method[0] ) && is_string( $method[1] ) ){
				#	スタティッククラスとメソッドのセット
				return	eval( 'return	'.$method[0].'::'.$method[1].'( $errorMessage , $FILE , $LINE );' );
			}
		}elseif( is_string( $method ) ){
			#	グローバル関数または匿名関数
			return	$method( $errorMessage , $FILE , $LINE );
		}
		return	true;
	}
	/**
	 * DBコネクション失敗時のイベントハンドラをセットする。
	 * 
	 * @param mixed $method コールバック関数
	 * @return bool 常に `true` を返します。
	 */
	public function set_eventhdl_connection_error( $method ){
		$this->method_eventhdl_connection_error = $method;
		return	true;
	}

	/**
	 * SQLエラー時に実行されるメソッド
	 *
	 * @param string $errorMessage エラーメッセージ
	 * @param string $FILE エラーが発生したファイル
	 * @param int $LINE エラーが発生した行番号
	 * @return bool コールバック関数が利用可能な場合、その関数の返却値を返します。コールバック関数が登録されない場合、何もせずに `true` を返します。
	 */
	private function eventhdl_query_error( $errorMessage = null , $FILE = null , $LINE = null ){
		$method = $this->method_eventhdl_query_error;
		if( is_array( $method ) ){
			#	配列を受けていたら
			if( is_object( $method[0] ) && is_string( $method[1] ) ){
				#	オブジェクトとメソッドのセット
				return	eval( 'return	$method[0]->'.$method[1].'( $errorMessage , $FILE , $LINE );' );
			}elseif( is_string( $method[0] ) && is_string( $method[1] ) ){
				#	スタティッククラスとメソッドのセット
				return	eval( 'return	'.$method[0].'::'.$method[1].'( $errorMessage , $FILE , $LINE );' );
			}
		}elseif( is_string( $method ) ){
			#	グローバル関数または匿名関数
			return	$method( $errorMessage , $FILE , $LINE );
		}
		return	true;
	}
	/**
	 * SQLエラー時のイベントハンドラをセットする。
	 * 
	 * @param mixed $method コールバック関数
	 * @return bool 常に `true` を返します。
	 */
	public function set_eventhdl_query_error( $method ){
		$this->method_eventhdl_query_error = $method;
		return	true;
	}
	/**
	 * SQLエラー時のイベントハンドラを取得する。
	 * 
	 * @return bool SQLエラーハンドラ。
	 */
	public function get_eventhdl_query_error(){
		return $this->method_eventhdl_query_error;
	}

	/**
	 * SQLのLIMIT句を作成する。
	 * 
	 * @param int $limit 取得件数
	 * @param int $offset 取得する開始位置(省略時 `0`)
	 */
	public function mk_sql_limit( $limit , $offset = 0 ){
		$sql = '';
		if( $this->get_db_conf('dbms') == 'postgresql' ){
			#	【 PostgreSQL 】
			$sql .= ' OFFSET '.intval( $offset ).' LIMIT '.intval( $limit ).' ';
		}else{
			#	【 MySQL/SQLite 】
			$sql .= ' LIMIT '.intval( $offset ).','.intval( $limit ).' ';
		}
		return $sql;
	}

	/**
	 * 配列からINSERT文を生成する。
	 * 
	 * @param string $table_name 挿入対象のテーブル名
	 * @param array $insert_values 挿入する値の一覧
	 * @param array $column_define テーブル定義(省略時、`$insert_values` の最初の行から自動生成する)
	 * @return string 生成されたSQL
	 */
	public function mk_sql_insert( $table_name , $insert_values , $column_define = null ){
		if( !strlen( $table_name ) ){ return false; }
		if( !is_array( $insert_values ) ){ return false; }
		if( !count( $insert_values ) ){ return false; }
		if( !is_array( $column_define ) ){
			#	定義が空だったら、
			#	挿入値の0個目から定義を作成する
			$column_define = $insert_values[0];
			foreach( array_keys( $column_define ) as $key ){
				$column_define[$key] = 'S';
			}
		}
		if( !count( $column_define ) ){ return false; }

		$column_keys = array_keys( $column_define );
		$insert_values_sql = array();
		foreach( $insert_values as $val ){
			$insert_val_sql_MEMO = array();
			foreach( $column_define as $val_key=>$val_type ){
				$MEMO = ':'.$val_type.':value';
				$MEMO = $this->bind( $MEMO , array( 'value'=>$val[$val_key] ) );
				array_push( $insert_val_sql_MEMO , $MEMO );
			}
			array_push( $insert_values_sql , implode( ' , ' , $insert_val_sql_MEMO ) );
		}

		$SQL = '';
		$SQL .= 'INSERT INTO '.$table_name.'( ';
		$SQL .= implode( ' , ' , $column_keys );
		$SQL .= ' ) VALUES ( ';
		$SQL .= implode( ' ),( ' , $insert_values_sql );
		$SQL .= ' );';

		return	$SQL;
	}

	#******************************************************************************************************************
	#	ファイル/ディレクトリ操作関連
	#	anch: allabout_filehandle

	/**
	 * 書き込み/上書きしてよいアイテムか検証する。
	 * 
	 * @param string $path 検証対象のパス
	 * @return bool 書き込み可能な場合 `true`、不可能な場合に `false` を返します。
	 */
	public function is_writable( $path ){
		if( strlen( $this->px->get_conf('system.filesystem_encoding') ) ){
			$path = @t::convert_encoding( $path , $this->px->get_conf('system.filesystem_encoding') );
		}
		if( @file_exists( $path ) && !@is_writable( $path ) ){
			return	false;
		}
		return	true;
	}//is_writable()

	/**
	 * 読み込んでよいアイテムか検証する。
	 * 
	 * @param string $path 検証対象のパス
	 * @return bool 読み込み可能な場合 `true`、不可能な場合に `false` を返します。
	 */
	public function is_readable( $path ){
		if( strlen( $this->px->get_conf('system.filesystem_encoding') ) ){
			$path = @t::convert_encoding( $path , $this->px->get_conf('system.filesystem_encoding') );
		}
		if( !@is_readable( $path ) ){
			return	false;
		}
		return	true;
	}//is_readable()

	/**
	 * ファイルが存在するかどうか調べる。
	 * 
	 * @param string $path 検証対象のパス
	 * @return bool ファイルが存在する場合 `true`、存在しない場合、またはディレクトリが存在する場合に `false` を返します。
	 */
	public function is_file( $path ){
		if( strlen( $this->px->get_conf('system.filesystem_encoding') ) ){
			$path = @t::convert_encoding( $path , $this->px->get_conf('system.filesystem_encoding') );
		}
		return @is_file( $path );
	}//is_file()

	/**
	 * ディレクトリが存在するかどうか調べる。
	 * 
	 * @param string $path 検証対象のパス
	 * @return bool ディレクトリが存在する場合 `true`、存在しない場合、またはファイルが存在する場合に `false` を返します。
	 */
	public function is_dir( $path ){
		if( strlen( $this->px->get_conf('system.filesystem_encoding') ) ){
			$path = @t::convert_encoding( $path , $this->px->get_conf('system.filesystem_encoding') );
		}
		return @is_dir( $path );
	}//is_dir()

	/**
	 * ファイルまたはディレクトリが存在するかどうか調べる。
	 * 
	 * @param string $path 検証対象のパス
	 * @return bool ファイルまたはディレクトリが存在する場合 `true`、存在しない場合に `false` を返します。
	 */
	public function file_exists( $path ){
		if( strlen( $this->px->get_conf('system.filesystem_encoding') ) ){
			$path = @t::convert_encoding( $path , $this->px->get_conf('system.filesystem_encoding') );
		}
		return @file_exists( $path );
	}//file_exists()

	/**
	 * ディレクトリを作成する。
	 * 
	 * @param string $dirpath 作成するディレクトリのパス
	 * @param int $perm 作成するディレクトリに与えるパーミッション
	 * @return bool 成功時に `true`、失敗時に `false` を返します。
	 */
	public function mkdir( $dirpath , $perm = null ){
		if( strlen( $this->px->get_conf('system.filesystem_encoding') ) ){
			$dirpath = @t::convert_encoding( $dirpath , $this->px->get_conf('system.filesystem_encoding') );
		}

		if( @is_dir( $dirpath ) ){
			#	既にディレクトリがあったら、作成を試みない。
			$this->chmod( $dirpath , $perm );
			return	true;
		}
		$result = @mkdir( $dirpath );
		$this->chmod( $dirpath , $perm );
		clearstatcache();
		return	$result;
	}//mkdir()

	/**
	 * ディレクトリを作成する(上層ディレクトリも全て作成)
	 * 
	 * @param string $dirpath 作成するディレクトリのパス
	 * @param int $perm 作成するディレクトリに与えるパーミッション
	 * @return bool 成功時に `true`、失敗時に `false` を返します。
	 */
	public function mkdir_all( $dirpath , $perm = null ){
		if( strlen( $this->px->get_conf('system.filesystem_encoding') ) ){
			$dirpath = @t::convert_encoding( $dirpath , $this->px->get_conf('system.filesystem_encoding') );
		}

		if( @is_dir( $dirpath ) ){ return true; }
		if( @is_file( $dirpath ) ){ return false; }
		$patharray = explode( '/' , $this->get_realpath( $dirpath ) );
		$targetpath = '';
		foreach( $patharray as $Line ){
			if( !strlen( $Line ) || $Line == '.' || $Line == '..' ){ continue; }
			$targetpath = $targetpath.'/'.$Line;
			if( !@is_dir( $targetpath ) ){
				$targetpath = @t::convert_encoding( $targetpath , mb_internal_encoding() , $this->px->get_conf('system.filesystem_encoding') );
				$this->mkdir( $targetpath , $perm );
			}
		}
		return	true;
	}//mkdir_all()

	/**
	 * ファイルを保存する。
	 * 
	 * このメソッドは、`$filepath` にデータを保存します。
	 * もともと保存されていた内容は破棄され、新しいデータで上書きします。
	 * 
	 * ただし、`fopen()` したリソースは、1回の処理の間保持されるので、
	 * 1回の処理で同じファイルに対して2回以上コールされた場合は、
	 * 追記される点に注意してください。
	 * 1回の処理の間に何度も上書きする必要がある場合は、
	 * 明示的に `$dbh->fclose($filepath);` をコールし、一旦ファイルを閉じてください。
	 * 
	 * @param string $filepath 保存先ファイルのパス
	 * @param string $content 保存する内容
	 * @param int $perm 保存するファイルに与えるパーミッション
	 * @return bool 成功時に `true`、失敗時に `false` を返します。
	 */
	public function save_file( $filepath , $content , $perm = null ){

		$filepath = $this->get_realpath($filepath);

		if( strlen( $this->px->get_conf('system.filesystem_encoding') ) ){
			//PxFW 0.6.4 追加
			$filepath = @t::convert_encoding( $filepath , $this->px->get_conf('system.filesystem_encoding') );
		}

		if( !$this->is_writable( $filepath ) )	{ return false; }
		if( @is_dir( $filepath ) ){ return false; }
		if( @is_file( $filepath ) && !@is_writable( $filepath ) ){ return false; }
		if( !is_array( @$this->file[$filepath] ) ){
			$this->fopen( $filepath , 'w' );
		}elseif( $this->file[$filepath]['mode'] != 'w' ){
			$this->fclose( $filepath );
			$this->fopen( $filepath , 'w' );
		}

		if( !strlen( $content ) ){
			#	空白のファイルで上書きしたい場合
			if( @is_file( $filepath ) ){
				@unlink( $filepath );
			}
			@touch( $filepath );
			$this->chmod( $filepath , $perm );
			clearstatcache();
			return @is_file( $filepath );
		}

		$res = $this->file[$filepath]['res'];
		if( !is_resource( $res ) ){ return false; }
		fwrite( $res , $content );
		$this->chmod( $filepath , $perm );
		clearstatcache();
		return @is_file( $filepath );
	}//save_file()

	/**
	 * ファイルを上書き保存して閉じる。
	 * 
	 * @param string $filepath 保存先ファイルのパス
	 * @param string $content 保存する内容
	 * @param int $perm 保存するファイルに与えるパーミッション
	 * @return bool 成功時に `true`、失敗時に `false` を返します。
	 */
	public function file_overwrite( $filepath , $content , $perm = null ){
		if( $this->is_file_open( $filepath ) ){
			#	既に開いているファイルだったら、一旦閉じる。
			$this->fclose( $filepath );
		}

		if( strlen( $this->px->get_conf('system.filesystem_encoding') ) ){
			//PxFW 0.6.4 追加
			$filepath = @t::convert_encoding( $filepath , $this->px->get_conf('system.filesystem_encoding') );
		}

		#	ファイルを上書き保存
		$result = $this->save_file( $filepath , $content , $perm );

		#	ファイルを閉じる
		$this->fclose( $filepath );
		return	$result;
	}//file_overwrite()

	/**
	 * ファイルの中身を1行ずつ配列にいれて取得する。
	 * 
	 * @param string $path ファイルのパス
	 * @return array ファイル `$path` の内容を1行1要素で格納する配列
	 */
	public function file_get_lines( $path ){

		if( strlen( $this->px->get_conf('system.filesystem_encoding') ) ){
			$path = @t::convert_encoding( $path , $this->px->get_conf('system.filesystem_encoding') );
		}

		if( @is_file( $path ) ){
			if( !$this->is_readable( $path ) ){ return false; }
			return	@file( $path );
		}elseif( preg_match( '/^(?:http:\/\/|https:\/\/)/' , $path ) ){
			#	対象がウェブコンテンツの場合、
			#	それを取得しようと試みます。
			#	しかし、この使用方法は推奨されません。
			#	対象が、とてもサイズの大きなファイルだったとしても、
			#	このメソッドはそれを検証しません。
			#	また、そのように巨大なファイルの場合でも、
			#	ディスクではなく、メモリにロードします。
			#	( 2007/01/05 TomK )
			if( !ini_get( 'allow_url_fopen' ) ){
				#	PHP設定値 allow_url_fopen が無効な場合は、
				#	file() によるウェブアクセスができないため。
				$this->px->error()->error_log( 'php.ini value "allow_url_fopen" is FALSE. So, disable to get Web contents ['.$path.'] on $dbh->file_get_lines();' );
				return	false;
			}
			return @file( $path );
		}
		return false;
	}//file_get_lines()

	/**
	 * ファイルの中身を文字列型にして取得する。
	 * 
	 * @param string $path ファイルのパス
	 * @return string ファイル `$path` の内容
	 */
	public function file_get_contents( $path ){

		if( strlen( $this->px->get_conf('system.filesystem_encoding') ) ){
			$path = @t::convert_encoding( $path , $this->px->get_conf('system.filesystem_encoding') );
		}

		if( @is_file( $path ) ){
			if( !$this->is_readable( $path ) ){ return false; }
			return	file_get_contents( $path );
		}elseif( preg_match( '/^(?:http:\/\/|https:\/\/)/' , $path ) ){
			#	対象がウェブコンテンツの場合、それを取得しようと試みます。
			#	ただし、ウェブコンテンツをこのメソッドからダウンロードする場合は、
			#	注意が必要です。
			#	対象が、とてもサイズの大きなファイルだったとしても、
			#	このメソッドはそれを検証しません。
			#	また、そのように巨大なファイルの場合でも、
			#	ディスクではなく、メモリに直接ロードします。
			return	$this->get_http_content( $path );
		}
		return	false;
	}//file_get_contents()

	/**
	 * HTTP通信からコンテンツを取得する。
	 * 
	 * 対象が、とてもサイズの大きなファイルだったとしても、
	 * このメソッドはそれを検証しないことに注意してください。
	 * また、そのように巨大なファイルの場合でも、
	 * ディスクではなく、メモリに直接ロードします。
	 * 
	 * @param string $url ファイルのURL
	 * @param string $saveTo 取得したファイルの保存先パス(省略可)
	 * @return string|bool `$saveTo` が省略された場合、取得したコンテンツを返します。`$saveTo` が指定された場合、保存成功時に `true`、失敗時に `false` を返します。
	 */
	public function get_http_content( $url , $saveTo = null ){

		if( !ini_get('allow_url_fopen') ){
			#	PHP設定値 allow_url_fopen が無効な場合は、
			#	file() によるウェブアクセスができないため、エラーを記録。
			$this->px->error()->error_log( 'php.ini value "allow_url_fopen" is FALSE. So, disable to get Web contents ['.$path.'] on $dbh->get_http_content();' );
			return	false;
		}
		if( preg_match( '/^(?:http:\/\/|https:\/\/)/' , $url ) ){
			if( !is_null( $saveTo ) ){
				#	取得したウェブコンテンツを
				#	ディスクに保存する場合

				if( @is_file( $saveTo ) && !@is_writable( $saveTo ) ){
					#	保存先ファイルが既に存在するのに、書き込めなかったらfalse;
					return	false;

				}elseif( !@is_file( $saveTo ) && @is_dir( dirname( $saveTo ) ) && !@is_writable( dirname( $saveTo ) ) ){
					#	保存先ファイルが存在しなくて、
					#	親ディレクトリがあるのに、書き込めなかったらfalse;
					return	false;

				}

				if( !@is_dir( dirname( $saveTo ) ) ){
					#	親ディレクトリがなかったら、作ってみる。
					if( !$this->mkdir_all( dirname( $saveTo ) ) ){
						#	失敗したらfalse;
						return	false;
					}
				}

				#	重たいファイルを考慮して、
				#	1行ずつディスクに保存していく。
				$res = $this->fopen( $url , 'r' , false );
				while( $LINE = @fgets( $res ) ){
					if( !strlen( $LINE ) ){ break; }
					$this->save_file( $saveTo , $LINE );
				}
				$this->fclose( $url );
				return	true;
			}

			#	取得したウェブコンテンツのバイナリを
			#	メモリにロードする場合。
			return	file_get_contents( $url );
		}
		return	false;
	}//get_http_content()

	/**
	 * ファイルの更新日時を比較する。
	 * 
	 * @param string $path_a 比較対象A
	 * @param string $path_b 比較対象B
	 * @return bool|null 
	 * `$path_a` の方が新しかった場合に `true`、
	 * `$path_b` の方が新しかった場合に `false`、
	 * 同時だった場合に `null` を返します。
	 */
	public function is_newer_a_than_b( $path_a , $path_b ){
		if( strlen( $this->px->get_conf('system.filesystem_encoding') ) ){
			//PxFW 0.6.4 追加
			$path_a = @t::convert_encoding( $path_a , $this->px->get_conf('system.filesystem_encoding') );
			$path_b = @t::convert_encoding( $path_b , $this->px->get_conf('system.filesystem_encoding') );
		}

		$mtime_a = filemtime( $path_a );
		$mtime_b = filemtime( $path_b );
		if( $mtime_a > $mtime_b ){
			return	true;
		}elseif( $mtime_a < $mtime_b ){
			return	false;
		}
		return	null;
	}//is_newer_a_than_b()

	/**
	 * ファイル名/ディレクトリ名を変更する。
	 *
	 * @param string $original 現在のファイルまたはディレクトリ名
	 * @param string $newname 変更後のファイルまたはディレクトリ名
	 * @return bool 成功時 `true`、失敗時 `false` を返します。
	 */
	public function rename( $original , $newname ){
		if( strlen( $this->px->get_conf('system.filesystem_encoding') ) ){
			$original = @t::convert_encoding( $original , $this->px->get_conf('system.filesystem_encoding') );
			$newname = @t::convert_encoding( $newname , $this->px->get_conf('system.filesystem_encoding') );
		}

		if( !@file_exists( $original ) ){ return false; }
		if( !$this->is_writable( $original ) ){ return false; }
		return	@rename( $original , $newname );
	}//rename()

	/**
	 * ファイル名/ディレクトリ名の変更を完全に実行する。
	 *
	 * 移動先の親ディレクトリが存在しない場合にも、親ディレクトリを作成して移動するよう試みます。
	 *
	 * @param string $original 現在のファイルまたはディレクトリ名
	 * @param string $newname 変更後のファイルまたはディレクトリ名
	 * @return bool 成功時 `true`、失敗時 `false` を返します。
	 */
	public function rename_complete( $original , $newname ){
		if( strlen( $this->px->get_conf('system.filesystem_encoding') ) ){
			$original = @t::convert_encoding( $original , $this->px->get_conf('system.filesystem_encoding') );
			$newname = @t::convert_encoding( $newname , $this->px->get_conf('system.filesystem_encoding') );
		}

		if( !@file_exists( $original ) ){ return false; }
		if( !$this->is_writable( $original ) ){ return false; }
		$dirname = dirname( $newname );
		if( !@is_dir( $dirname ) ){
			if( !$this->mkdir_all( $dirname ) ){
				return false;
			}
		}
		return @rename( $original , $newname );
	}//rename_complete()

	/**
	 * 絶対パスを得る。
	 * 
	 * パス情報を受け取り、スラッシュから始まるサーバー内部絶対パスに変換して返します。
	 * 
	 * このメソッドは、`realpath()` と違い、存在しないアイテムも絶対パスに変換します。
	 * ただし、ルート直下のディレクトリまでは一致している必要があり、そうでない場合は、`false` を返します。
	 * 
	 * @param string $path 対象のパス
	 * @param string $itemname 再帰的に処理する場合に使用(初回コール時は使用しません)
	 * @return string 絶対パス
	 */
	public function get_realpath( $path , $itemname = null ){
		$path = preg_replace( '/\\\\/si' , '/' , $path );
		$path = preg_replace( '/^\/+/si' , '/' , $path );//先頭のスラッシュを1つにする。
		$itemname = preg_replace( '/\\\\/si' , '/' , $itemname );
		$itemname = preg_replace( '/^\/'.'*'.'/' , '/' , $itemname );//先頭のスラッシュを1つにする。

		if( $itemname == '/' ){ $itemname = ''; }//スラッシュだけが残ったら、ゼロバイトの文字にする。
		if( t::realpath( $path ) == '/' ){
			$rtn = $path.$itemname;
			$rtn = preg_replace( '/\/+/si' , '/' , $rtn );//先頭のスラッシュを1つにする。
			return	$rtn;
		}

		if( strlen( $this->px->get_conf('system.filesystem_encoding') ) ){
			$path = @t::convert_encoding( $path , $this->px->get_conf('system.filesystem_encoding') );
		}

		if( @file_exists( $path ) && strlen(t::realpath( $path )) ){
			return	t::realpath( $path ).$itemname;
		}

		if( basename( $path ) == '.' ){
			#	カレントディレクトリを含むパスへの対応
			return	$this->get_realpath( dirname( $path ) , $itemname );
		}
		if( basename( $path ) == '..' ){
			$count = 0;
			while( basename( $path ) == '..' && strlen( dirname( $path ) ) && dirname( $path ) != '/' ){
				$count ++;
				$path = dirname( $path );
			}
			for( $i = 0; $i < $count; $i++ ){
				$path = dirname( $path );
			}
			#	ペアレントディレクトリを含むパスへの対応
			return	$this->get_realpath( $path , $itemname );
		}
		return	$this->get_realpath( dirname( $path ) , basename( $path ).$itemname );
	}

	/**
	 * パス情報を得る。
	 * 
	 * @param string $path 対象のパス
	 * @return array パス情報
	 */
	public function pathinfo( $path ){
		if( strlen( $this->px->get_conf('system.filesystem_encoding') ) ){
			$path = @t::convert_encoding( $path , $this->px->get_conf('system.filesystem_encoding') );
		}
		$pathinfo = pathinfo( $path );
		$pathinfo['filename'] = $this->trim_extension( $pathinfo['basename'] );
		return	$pathinfo;
	}

	/**
	 * パス情報から、ファイル名を取得する。
	 * 
	 * @param string $path 対象のパス
	 * @return string 抜き出されたファイル名
	 */
	public function get_basename( $path ){
		return	pathinfo( $path , PATHINFO_BASENAME );
	}

	/**
	 * パス情報から、拡張子を除いたファイル名を取得する。
	 * 
	 * @param string $path 対象のパス
	 * @return string 拡張子が除かれたパス
	 */
	public function trim_extension( $path ){
		$pathinfo = pathinfo( $path );
		$RTN = preg_replace( '/\.'.preg_quote( $pathinfo['extension'] , '/' ).'$/' , '' , $path );
		return	$RTN;
	}

	/**
	 * ファイル名を含むパス情報から、ファイルが格納されているディレクトリ名を取得する。
	 * 
	 * @param string $path 対象のパス
	 * @return string 親ディレクトリのパス
	 */
	public function get_dirpath( $path ){
		return	pathinfo( $path , PATHINFO_DIRNAME );
	}

	/**
	 * パス情報から、拡張子を取得する。
	 * 
	 * @param string $path 対象のパス
	 * @return string 拡張子
	 */
	public function get_extension( $path ){
		return	pathinfo( $path , PATHINFO_EXTENSION );
	}


	/**
	 * CSVファイルを読み込む。
	 * 
	 * @param string $path 対象のCSVファイルのパス
	 * @param array $options オプション
	 * @return array|bool 読み込みに成功した場合、行列を格納した配列、失敗した場合には `false` を返します。
	 */
	public function read_csv( $path , $options = array() ){
		#	$options['charset'] は、保存されているCSVファイルの文字エンコードです。
		#	省略時は SJIS-win から、内部エンコーディングに変換します。

		if( strlen( $this->px->get_conf('system.filesystem_encoding') ) ){
			$path = @t::convert_encoding( $path , $this->px->get_conf('system.filesystem_encoding') );
		}

		$path = t::realpath( $path );
		if( !@is_file( $path ) ){
			#	ファイルがなければfalseを返す
			return	false;
		}

		if( !strlen( @$options['delimiter'] ) )    { $options['delimiter'] = ','; }
		if( !strlen( @$options['enclosure'] ) )    { $options['enclosure'] = '"'; }
		if( !strlen( @$options['size'] ) )         { $options['size'] = 10000; }
		if( !strlen( @$options['charset'] ) )      { $options['charset'] = 'SJIS-win'; }

		$RTN = array();
		if( !$this->fopen($path,'r') ){ return false; }
		$filelink = $this->get_file_resource($path);
		if( !is_resource( $filelink ) || !is_null( @$this->file[$path]['contents'] ) ){
			return $this->file[$path]['contents'];
		}
		while( $SMMEMO = fgetcsv( $filelink , intval( $options['size'] ) , $options['delimiter'] , $options['enclosure'] ) ){
			$SMMEMO = t::convert_encoding( $SMMEMO , mb_internal_encoding() , $options['charset'].',UTF-8,SJIS-win,eucJP-win,SJIS,EUC-JP' );
			array_push( $RTN , $SMMEMO );
		}
		$this->fclose($path);
		return	$RTN;
	}//read_csv()

	/**
	 * UTF-8のCSVファイルを読み込む
	 * 
	 * @param string $path 対象のCSVファイルのパス
	 * @param array $options オプション
	 * @return array|bool 読み込みに成功した場合、行列を格納した配列、失敗した場合には `false` を返します。
	 */
	public function read_csv_utf8( $path , $options = array() ){
		#	読み込み時にUTF-8の解釈が優先される。
		if( !gettype($options) ){
			$options = array();
		}
		$options['charset'] = 'UTF-8';
		return $this->read_csv( $path , $options );
	}//read_csv_utf8()

	/**
	 * 配列をCSV形式に変換する
	 * 
	 * @param array $array 2次元配列
	 * @param array $options オプション
	 * @return string 生成されたCSV形式のテキスト
	 */
	public function mk_csv( $array , $options = array() ){
		#	$options['charset'] は、出力されるCSV形式の文字エンコードを指定します。
		#	省略時は Shift_JIS に変換して返します。
		if( !is_array( $array ) ){ $array = array(); }

		if( !strlen( $options['charset'] ) ){ $options['charset'] = 'SJIS-win'; }
		$RTN = '';
		foreach( $array as $Line ){
			if( is_null( $Line ) ){ continue; }
			if( !is_array( $Line ) ){ $Line = array(); }
			foreach( $Line as $cell ){
				$cell = @t::convert_encoding( $cell , $options['charset'] , mb_internal_encoding().',UTF-8,SJIS-win,eucJP-win,SJIS,EUC-JP' );
				if( preg_match( '/"/' , $cell ) ){
					$cell = preg_replace( '/"/' , '""' , $cell);
				}
				if( strlen( $cell ) ){
					$cell = '"'.$cell.'"';
				}
				$RTN .= $cell.',';
			}
			$RTN = preg_replace( '/,$/' , '' , $RTN );
			$RTN .= "\r\n";
		}
		return	$RTN;
	}//mk_csv()

	/**
	 * 配列をUTF8-エンコードのCSV形式に変換する。
	 * 
	 * @param array $array 2次元配列
	 * @param array $options オプション
	 * @return string 生成されたCSV形式のテキスト
	 */
	public function mk_csv_utf8( $array , $options = array() ){
		if( !is_array($options) ){
			$options = array();
		}
		$options['charset'] = 'UTF-8';
		return	$this->mk_csv( $array , $options );
	}//mk_csv_utf8()

	/**
	 * ファイルを複製する。
	 * 
	 * @param string $from コピー元ファイルのパス
	 * @param string $to コピー先のパス
	 * @param int $perm 保存するファイルに与えるパーミッション
	 * @return bool 成功時に `true`、失敗時に `false` を返します。
	 */
	public function copy( $from , $to , $perm = null ){
		if( strlen( $this->px->get_conf('system.filesystem_encoding') ) ){
			$from = @t::convert_encoding( $from , $this->px->get_conf('system.filesystem_encoding') );
			$to   = @t::convert_encoding( $to   , $this->px->get_conf('system.filesystem_encoding') );
		}

		if( !@is_file( $from ) ){
			return false;
		}
		if( !$this->is_readable( $from ) ){
			return false;
		}

		if( @is_file( $to ) ){
			//	まったく同じファイルだった場合は、複製しないでtrueを返す。
			if( md5_file( $from ) == md5_file( $to ) && filesize( $from ) == filesize( $to ) ){
				return true;
			}
		}
		if( !@copy( $from , $to ) ){
			return false;
		}
		$this->chmod( $to , $perm );
		return true;
	}//copy()

	/**
	 * ディレクトリを複製する(下層ディレクトリも全てコピー)
	 * 
	 * @param string $from コピー元ファイルのパス
	 * @param string $to コピー先のパス
	 * @param int $perm 保存するファイルに与えるパーミッション
	 * @return bool 成功時に `true`、失敗時に `false` を返します。
	 */
	public function copy_all( $from , $to , $perm = null ){
		if( strlen( $this->px->get_conf('system.filesystem_encoding') ) ){
			$from = @t::convert_encoding( $from , $this->px->get_conf('system.filesystem_encoding') );
			$to   = @t::convert_encoding( $to   , $this->px->get_conf('system.filesystem_encoding') );
		}

		$result = true;

		if( @is_file( $from ) ){
			if( $this->mkdir_all( dirname( $to ) ) ){
				if( !$this->copy( $from , $to , $perm ) ){
					$result = false;
				}
			}else{
				$result = false;
			}
		}elseif( @is_dir( $from ) ){
			if( !@is_dir( $to ) ){
				if( !$this->mkdir_all( $to ) ){
					$result = false;
				}
			}
			$itemlist = $this->ls( $from );
			foreach( $itemlist as $Line ){
				if( $Line == '.' || $Line == '..' ){ continue; }
				if( @is_dir( $from.'/'.$Line ) ){
					if( @is_file( $to.'/'.$Line ) ){
						continue;
					}elseif( !@is_dir( $to.'/'.$Line ) ){
						if( !$this->mkdir_all( $to.'/'.$Line ) ){
							$result = false;
						}
					}
					if( !$this->copy_all( $from.'/'.$Line , $to.'/'.$Line , $perm ) ){
						$result = false;
					}
					continue;
				}elseif( @is_file( $from.'/'.$Line ) ){
					if( !$this->copy_all( $from.'/'.$Line , $to.'/'.$Line , $perm ) ){
						$result = false;
					}
					continue;
				}
			}
		}

		return $result;
	}//copy_all()

	/**
	 * ファイルを開き、ファイルリソースをセットする。
	 * 
	 * @param string $filepath ファイルのパス
	 * @param string $mode モード
	 * @param bool $flock ファイルをロックするフラグ
	 * @return resource|bool 成功したらファイルリソースを、失敗したら `false` を返します。
	 */
	public function &fopen( $filepath , $mode = 'r' , $flock = true ){
		$filepath_fsenc = $filepath;
		if( strlen( $this->px->get_conf('system.filesystem_encoding') ) ){
			//PxFW 0.6.4 追加
			$filepath_fsenc = @t::convert_encoding( $filepath_fsenc , $this->px->get_conf('system.filesystem_encoding') );
		}

		$filepath = $this->get_realpath( $filepath );

		#	すでに開かれていたら
		if( is_resource( @$this->file[$filepath]['res'] ) ){
			if( $this->file[$filepath]['mode'] != $mode ){
				#	$modeが前回のアクセスと違っていたら、
				#	前回の接続を一旦closeして、開きなおす。
				$this->fclose( $filepath );
			}else{
				#	前回と$modeが一緒であれば、既に開いているので、
				#	ここで終了。
				return	$this->file[$filepath]['res'];
			}
		}

		#	対象がディレクトリだったら開けません。
		if( @is_dir( $filepath_fsenc ) ){
			return	false;
		}

		#	ファイルが存在するかどうか確認
		if( @is_file( $filepath_fsenc ) ){
			$filepath = t::realpath( $filepath );
			#	対象のパーミッションをチェック
			switch( strtolower($mode) ){
				case 'r':
					if( !$this->is_readable( $filepath ) ){ return false; }
					break;
				case 'w':
				case 'a':
				case 'x':
					if( !$this->is_writable( $filepath ) ){ return false; }
					break;
				case 'r+':
				case 'w+':
				case 'a+':
				case 'x+':
					if( !$this->is_readable( $filepath ) ){ return false; }
					if( !$this->is_writable( $filepath ) ){ return false; }
					break;
			}
		}

		if( is_array( @$this->file[$filepath] ) ){ $this->fclose( $filepath ); }

		for( $i = 0; $i < 5; $i++ ){
			$res = @fopen( $filepath_fsenc , $mode );
			if( $res ){ break; }		#	openに成功したらループを抜ける
			sleep(1);
		}
		if( !is_resource( $res ) ){ return false; }	#	5回挑戦して読み込みが成功しなかった場合、falseを返す
		if( $flock ){ flock( $res , LOCK_EX ); }
		if( @is_file( $filepath_fsenc ) ){
			$filepath = t::realpath( $filepath );
		}
		$this->file[$filepath]['filepath'] = $filepath;
		$this->file[$filepath]['res'] = $res;
		$this->file[$filepath]['mode'] = $mode;
		$this->file[$filepath]['flock'] = $flock;
		return	$res;
	}//fopen()

	/**
	 * ファイルのリソースを取得する。
	 * 
	 * @param string $filepath ファイルのパス
	 * @return resource ファイルリソース
	 */
	public function &get_file_resource( $filepath ){
		$filepath = $this->get_realpath($filepath);
		return	$this->file[$filepath]['res'];
	}//get_file_resource()

	/**
	 * パーミッションを変更する。
	 * 
	 * @param string $filepath 対象のパス
	 * @param int $perm 保存するファイルに与えるパーミッション
	 * @return bool 成功時に `true`、失敗時に `false` を返します。
	 */
	public function chmod( $filepath , $perm = null ){
		if( strlen( $this->px->get_conf('system.filesystem_encoding') ) ){
			$filepath = @t::convert_encoding( $filepath , $this->px->get_conf('system.filesystem_encoding') );
		}

		if( is_null( $perm ) ){
			if( @is_dir( $filepath ) ){
				$perm = $this->default_permission['dir'];
			}else{
				$perm = $this->default_permission['file'];
			}
		}
		if( is_null( $perm ) ){
			$perm = 0775;	//	コンフィグに設定モレがあった場合
		}
		return	@chmod( $filepath , $perm );
	}//chmod()

	/**
	 * パーミッション情報を調べ、3桁の数字で返す。
	 * 
	 * @param string $path 対象のパス
	 * @return int|bool 成功時に 3桁の数字、失敗時に `false` を返します。
	 */
	public function get_permission( $path ){
		if( strlen( $this->px->get_conf('system.filesystem_encoding') ) ){
			//PxFW 0.6.4 追加
			$path = @t::convert_encoding( $path , $this->px->get_conf('system.filesystem_encoding') );
		}
		$path = @realpath( $path );
		if( !@file_exists( $path ) ){ return false; }
		$perm = rtrim( sprintf( "%o\n" , fileperms( $path ) ) );
		$start = strlen( $perm ) - 3;
		return	substr( $perm , $start , 3 );
	}//get_permission()


	/**
	 * ディレクトリにあるファイル名のリストを配列で返す。
	 * 
	 * @param string $path 対象ディレクトリのパス
	 * @return array|bool 成功時にファイルまたはディレクトリ名の一覧を格納した配列、失敗時に `false` を返します。
	 */
	public function ls($path){
		if( strlen( $this->px->get_conf('system.filesystem_encoding') ) ){
			//PxFW 0.6.4 追加
			$path = @t::convert_encoding( $path , $this->px->get_conf('system.filesystem_encoding') );
		}
		$path = @realpath($path);
		if( $path === false ){ return false; }
		if( !@file_exists( $path ) ){ return false; }
		if( !@is_dir( $path ) ){ return false; }

		$RTN = array();
		$dr = @opendir($path);
		while( ( $ent = readdir( $dr ) ) !== false ){
			#	CurrentDirとParentDirは含めない
			if( $ent == '.' || $ent == '..' ){ continue; }
			array_push( $RTN , $ent );
		}
		closedir($dr);
		if( strlen( $this->px->get_conf('system.filesystem_encoding') ) ){
			//PxFW 0.6.4 追加
			$RTN = @t::convert_encoding( $RTN , mb_internal_encoding() );
		}
		usort($RTN, "strnatcmp");
		return	$RTN;
	}//ls()

	/**
	 * ディレクトリを削除する。
	 * 
	 * このメソッドはディレクトリを削除します。
	 * 中身のない、空っぽのディレクトリ以外は削除できません。
	 * 
	 * @param string $path 対象ディレクトリのパス
	 * @return bool 成功時に `true`、失敗時に `false` を返します。
	 */
	public function rmdir( $path ){

		if( strlen( $this->px->get_conf('system.filesystem_encoding') ) ){
			$path = @t::convert_encoding( $path , $this->px->get_conf('system.filesystem_encoding') );
		}

		if( !$this->is_writable( $path ) ){
			return false;
		}
		$path = @realpath( $path );
		if( $path === false ){ return false; }
		if( @is_file( $path ) || @is_link( $path ) ){
			#   ファイルまたはシンボリックリンクの場合の処理
			#   ディレクトリ以外は削除できません。
			return false;

		}elseif( @is_dir( $path ) ){
			#   ディレクトリの処理
			#   rmdir() は再帰的削除を行いません。
			#   再帰的に削除したい場合は、代わりに rm() を使用します。
			$result = @rmdir( $path );
			return	$result;
		}

		return false;
	}//rmdir()

	/**
	 * ファイルやディレクトリを中身ごと完全に削除する。
	 * 
	 * このメソッドは、ファイルやシンボリックリンクも削除します。
	 * ディレクトリを削除する場合は、中身ごと完全に削除します。
	 * シンボリックリンクは、その先を追わず、シンボリックリンク本体のみを削除します。
	 * 
	 * @param string $path 対象のパス
	 * @return bool 成功時に `true`、失敗時に `false` を返します。
	 */
	public function rm( $path ){

		if( strlen( $this->px->get_conf('system.filesystem_encoding') ) ){
			$path = @t::convert_encoding( $path , $this->px->get_conf('system.filesystem_encoding') );
		}

		if( !$this->is_writable( $path ) ){
			return false;
		}
		$path = @realpath( $path );
		if( $path === false ){ return false; }
		if( @is_file( $path ) || @is_link( $path ) ){
			#	ファイルまたはシンボリックリンクの場合の処理
			$result = @unlink( $path );
			return	$result;

		}elseif( @is_dir( $path ) ){
			#	ディレクトリの処理
			$flist = $this->ls( $path );
			foreach ( $flist as $Line ){
				if( $Line == '.' || $Line == '..' ){ continue; }
				$this->rm( $path.'/'.$Line );
			}
			$result = @rmdir( $path );
			return	$result;

		}

		return false;
	}//rm()

	/**
	 * ディレクトリの内部を比較し、$comparisonに含まれない要素を$targetから削除する。
	 *
	 * @param string $target クリーニング対象のディレクトリパス
	 * @param string $comparison 比較するディレクトリのパス
	 * @return bool 成功時 `true`、失敗時 `false` を返します。
	 */
	public function compare_and_cleanup( $target , $comparison ){
		if( is_null( $comparison ) || is_null( $target ) ){ return false; }

		if( strlen( $this->px->get_conf('system.filesystem_encoding') ) ){
			$target = @t::convert_encoding( $target , $this->px->get_conf('system.filesystem_encoding') );
			$comparison = @t::convert_encoding( $comparison , $this->px->get_conf('system.filesystem_encoding') );
		}

		if( !@file_exists( $comparison ) && @file_exists( $target ) ){
			$this->rm( $target );
			return true;
		}

		if( @is_dir( $target ) ){
			$flist = $this->ls( $target );
		}else{
			return true;
		}

		foreach ( $flist as $Line ){
			if( $Line == '.' || $Line == '..' ){ continue; }
			$this->compare_and_cleanup( $target.'/'.$Line , $comparison.'/'.$Line );
		}

		return true;
	}//compare_and_cleanup()

	/**
	 * ディレクトリを同期する。
	 * 
	 * @param string $path_sync_from 同期元ディレクトリ
	 * @param string $path_sync_to 同期先ディレクトリ
	 * @return bool 常に `true` を返します。
	 */
	public function sync_dir( $path_sync_from , $path_sync_to ){
		$this->copy_all( $path_sync_from , $path_sync_to );
		$this->compare_and_cleanup( $path_sync_to , $path_sync_from );
		return true;
	}//sync_dir()

	/**
	 * 指定されたディレクトリ以下の、全ての空っぽのディレクトリを削除する。
	 * 
	 * @param string $path ディレクトリパス
	 * @param array $options オプション
	 * @return bool 成功時 `true`、失敗時 `false` を返します。
	 */
	public function remove_empty_dir( $path , $options = array() ){
		if( strlen( $this->px->get_conf('system.filesystem_encoding') ) ){
			$path = @t::convert_encoding( $path , $this->px->get_conf('system.filesystem_encoding') );
		}

		if( !$this->is_writable( $path ) ){ return false; }
		if( !@is_dir( $path ) ){ return false; }
		if( @is_file( $path ) || @is_link( $path ) ){ return false; }
		$path = @realpath( $path );
		if( $path === false ){ return false; }

		#--------------------------------------
		#	次の階層を処理するかどうかのスイッチ
		$switch_donext = false;
		if( is_null( $options['depth'] ) ){
			#	深さの指定がなければ掘る
			$switch_donext = true;
		}elseif( !is_int( $options['depth'] ) ){
			#	指定がnullでも数値でもなければ掘らない
			$switch_donext = false;
		}elseif( $options['depth'] <= 0 ){
			#	指定がゼロ以下なら、今回の処理をして終了
			$switch_donext = false;
		}elseif( $options['depth'] > 0 ){
			#	指定が正の数(ゼロは含まない)なら、掘る
			$options['depth'] --;
			$switch_donext = true;
		}else{
			return	false;
		}
		#	/ 次の階層を処理するかどうかのスイッチ
		#--------------------------------------

		$flist = $this->ls( $path );
		if( !count( $flist ) ){
			#	開いたディレクトリの中身が
			#	"." と ".." のみだった場合
			#	削除して終了
			$result = @rmdir( $path );
			return	$result;
		}
		$alive = false;
		foreach ( $flist as $Line ){
			if( $Line == '.' || $Line == '..' ){ continue; }
			if( @is_link( $path.'/'.$Line ) ){
				#	シンボリックリンクはシカトする。
			}elseif( @is_dir( $path.'/'.$Line ) ){
				if( $switch_donext ){
					#	さらに掘れと指令があれば、掘る。
					$this->remove_empty_dir( $path.'/'.$Line , $options );
				}
			}
			if( @file_exists( $path.'/'.$Line ) ){
				$alive = true;
			}
		}
		if( !$alive ){
			$result = @rmdir( $path );
			return	$result;
		}
		return	true;
	}//remove_empty_dir()


	/**
	 * 指定された2つのディレクトリの内容を比較し、まったく同じかどうか調べる。
	 *
	 * @param string $dir_a 比較対象ディレクトリA
	 * @param string $dir_b 比較対象ディレクトリB
	 * @param array $options オプション
	 * <dl>
	 *   <dt>bool $options['compare_filecontent']</dt>
	 * 	   <dd>ファイルの中身も比較するか？</dd>
	 *   <dt>bool $options['compare_emptydir']</dt>
	 * 	   <dd>空っぽのディレクトリの有無も評価に含めるか？</dd>
	 * </dl>
	 * @return bool 同じ場合に `true`、異なる場合に `false` を返します。
	 */
	public function compare_dir( $dir_a , $dir_b , $options = array() ){

		if( strlen( $this->px->get_conf('system.filesystem_encoding') ) ){
			//PxFW 0.6.4 追加
			$dir_a = @t::convert_encoding( $dir_a , $this->px->get_conf('system.filesystem_encoding') );
			$dir_b = @t::convert_encoding( $dir_b , $this->px->get_conf('system.filesystem_encoding') );
		}

		if( ( @is_file( $dir_a ) && !@is_file( $dir_b ) ) || ( !@is_file( $dir_a ) && @is_file( $dir_b ) ) ){
			return	false;
		}
		if( ( ( @is_dir( $dir_a ) && !@is_dir( $dir_b ) ) || ( !@is_dir( $dir_a ) && @is_dir( $dir_b ) ) ) && $options['compare_emptydir'] ){
			return	false;
		}

		if( @is_file( $dir_a ) && @is_file( $dir_b ) ){
			#--------------------------------------
			#	両方ファイルだったら
			if( $options['compare_filecontent'] ){
				#	ファイルの内容も比較する設定の場合、
				#	それぞれファイルを開いて同じかどうかを比較
				$filecontent_a = $this->file_get_contents( $dir_a );
				$filecontent_b = $this->file_get_contents( $dir_b );
				if( $filecontent_a !== $filecontent_b ){
					return false;
				}
			}
			return true;
		}

		if( @is_dir( $dir_a ) || @is_dir( $dir_b ) ){
			#--------------------------------------
			#	両方ディレクトリだったら
			$contlist_a = $this->ls( $dir_a );
			$contlist_b = $this->ls( $dir_b );

			if( $options['compare_emptydir'] && $contlist_a !== $contlist_b ){
				#	空っぽのディレクトリも厳密に評価する設定で、
				#	ディレクトリ内の要素配列の内容が異なれば、false。
				return false;
			}

			$done = array();
			foreach( $contlist_a as $Line ){
				#	Aをチェック
				if( $Line == '..' || $Line == '.' ){ continue; }
				if( !$this->compare_dir( $dir_a.'/'.$Line , $dir_b.'/'.$Line , $options ) ){
					return false;
				}
				$done[$Line] = true;
			}

			foreach( $contlist_b as $Line ){
				#	Aに含まれなかったBをチェック
				if( $done[$Line] ){ continue; }
				if( $Line == '..' || $Line == '.' ){ continue; }
				if( !$this->compare_dir( $dir_a.'/'.$Line , $dir_b.'/'.$Line , $options ) ){
					return false;
				}
				$done[$Line] = true;
			}

		}

		return true;
	}//compare_dir()


	#******************************************************************************************************************
	#	エラー処理

	/**
	 * オブジェクト内部エラーを記録する。
	 * 
	 * @param string $errortext エラー文言
	 * @param string $errorkey エラーキー
	 * @param string $file エラーが発生したファイルパス
	 * @param string $line エラーが発生した行番号
	 * @return bool 成功時に `true`、失敗時に `false` を返します。
	 */
	private function add_error( $errortext = null , $errorkey = null , $file = null , $line = null ){
		static $seq;	// シーケンス
		if( !strlen($errortext) ){
			$errortext = 'Unknown error';
		}
		if( !$seq ){ $seq = 0; }
		if( is_null( $errorkey ) ){ $errorkey = $seq; }
		if( is_null( $errortext ) ){ $errortext = 'Error'; }
		$this->errorlist[$errorkey] = $errortext;
		$seq ++;	// シーケンスを一つ進める

		#	エラーログを保存
		$this->px->error()->error_log( $errortext , $file , $line );

		return	true;
	}//add_error()

	/**
	 * オブジェクト内部エラーを取得する。
	 * 
	 * @return array 内部エラーリスト
	 */
	public function get_error_list(){
		return	$this->errorlist;
	}//get_error_list()


	#******************************************************************************************************************
	#	終了系の処理集

	/**
	 * 全てのファイルとデータベースを閉じる。
	 * 
	 * @return bool 成功時 `true`、失敗時 `false` を返します。
	 */
	public function close_all(){
		$res_f = $this->fclose_all();
		$res_d = $this->disconnect_all();

		if( !$res_f || !$res_d ){ return false; }

		return	true;
	}

	/**
	 * 開いている全てのファイルを閉じる。
	 * 
	 * @return bool 成功時 `true`、失敗時 `false` を返します。
	 */
	public function fclose_all(){
		foreach($this->file as $line){
			$this->fclose( $line['filepath'] );
		}
		return	true;
	}

	/**
	 * 開いているファイル(単体)を閉じる。
	 * 
	 * @param string $filepath 閉じるファイルパス
	 * @return bool 成功時 `true`、失敗時 `false` を返します。
	 */
	public function fclose( $filepath ){
		$filepath = $this->get_realpath( $filepath );
		if( !$this->is_file_open( $filepath ) ){
			#	ファイルを開いていない状態だったらスキップ
			return	false;
		}

		if( $this->file[$filepath]['flock'] ){
			flock( $this->file[$filepath]['res'] , LOCK_UN );
		}
		fclose( $this->file[$filepath]['res'] );
		unset( $this->file[$filepath] );
		return	true;
	}

	/**
	 * ファイルを開いている状態か確認する。
	 * 
	 * @param string $filepath 調査対象のファイルパス
	 * @return bool すでに開いている場合 `true`、開いていない場合に `false` を返します。
	 */
	public function is_file_open( $filepath ){
		$filepath = $this->get_realpath( $filepath );
		if( !@array_key_exists( $filepath , $this->file ) ){ return false; }
		if( !@is_array( $this->file[$filepath] ) ){ return false; }
		return true;
	}

	/**
	 * すべてのデータベースコネクションを切断する。
	 * 
	 * @return bool 成功時 `true`、失敗時 `false` を返します。
	 */
	public function disconnect_all(){
		return $this->disconnect();
	}
	/**
	 * データベースコネクションを切断する
	 * 
	 * @return bool 成功時 `true`、失敗時 `false` を返します。
	 */
	private function disconnect(){
		if( !$this->check_connection() ){return true;}
		if( $this->is_transaction() ){
			if( $this->auto_commit_flg ){
				#	オートコミットモード
				$this->commit();
			}else{
				#	オートコミットモードが無効な場合、ロールバック
				$this->rollback();
			}
		}

		if( class_exists('PDO') ){
			#--------------------------------------
			#	【 PDO 】
			unset( $this->connection );
			$this->connection = null;
		}//if( class_exists('PDO') )

		if( $this->get_db_conf('dbms') == 'mysql' ){
			#--------------------------------------
			#	【 MySQL 】
			if( mysql_close( $this->connection ) ){
				unset( $this->connection );
				return	true;
			}else{
				$this->add_error( 'Faild to disconnect DB.' , 'disconnect' , __FILE__ , __LINE__ );
				return	false;
			}

		}elseif( $this->get_db_conf('dbms') == 'postgresql' ){
			#--------------------------------------
			#	【 PostgreSQL 】
			if( pg_close( $this->connection ) ){
				unset( $this->connection );
				return	true;
			}else{
				$this->add_error( 'Faild to disconnect DB.' , 'disconnect' , __FILE__ , __LINE__ );
				return	false;
			}

		}elseif( $this->get_db_conf('dbms') == 'sqlite' ){
			#--------------------------------------
			#	【 SQLite 】
			sqlite_close( $this->connection );
			unset( $this->connection );
			return	true;

		}
		$this->add_error( '未対応のデータベースです。' , 'disconnect' , __FILE__ , __LINE__ );
		return	false;
	}


	#******************************************************************************************************************
	#	その他
	#	anch: allabout_others

	/**
	 * アプリケーションをロックする。
	 * 
	 * @param string $lockname ロック名。デフォルトは `'applock'`
	 * @param string $user_cd ロック処理をしたユーザーのID。ログインユーザーがいない場合は `null` を指定する。
	 * @param int $timeout_limit 他のユーザーがロックしている場合に、開放まで待つ待ち時間。省略時 `10` 秒。
	 * @param int $lockfile_expire 1回のロック状態が長く続く場合、その有効期限。省略時 `0` (=無期限)
	 * @return bool 成功時 `true`、失敗時に `false` を返します。
	 */
	public function lock( $lockname = 'applock' , $user_cd = null , $timeout_limit = 10 , $lockfile_expire = 0 ){
		if( !preg_match( '/^[a-zA-Z0-9_-]+$/ism' , $lockname ) ){ $lockname = 'applock'; }
		$lockfilepath = $this->px->get_conf('paths.px_dir').'_sys/applock/'.$lockname.'.txt';
		$lockfile_expire = intval( $lockfile_expire );

		$timeout_limit = intval( $timeout_limit );
		if( $timeout_limit <= 0 ){
			#	初期値：10秒
			$timeout_limit = 10;
		}

		if( !@is_dir( dirname( $lockfilepath ) ) ){
			$this->mkdir_all( dirname( $lockfilepath ) );
		}

		#	PHPのFileStatusCacheをクリア
		clearstatcache();

		$i = 0;
		while( ( @is_file( $lockfilepath ) && @filesize( $lockfilepath ) ) ){
			if( $lockfile_expire > 0 ){
				$file_bin = $this->file_get_contents( $lockfilepath );
				$file_bin_ary = explode( '::' , $file_bin );
				$file_time = $this->datetime2int( $file_bin_ary[1] );
				if( ( time() - $file_time ) > $lockfile_expire ){
					#	有効期限を過ぎていたら、ロックは成立する。(PxFW0.6.4 追加仕様)
					break;
				}
			}

			$i ++;
			if( $i >= $timeout_limit ){
				return false;
				break;
			}
			sleep(1);

			#	PHPのFileStatusCacheをクリア
			clearstatcache();
		}
		$RTN = $this->file_overwrite( $lockfilepath , $user_cd.'::'.date( 'Y-m-d H:i:s' , time() ) );
		return	$RTN;
	}//lock()

	/**
	 * アプリケーションロックを解除する。
	 * 
	 * `$dbh->lock()` でかけたロックを解除します。
	 *
	 * @param string $lockname ロック名。デフォルトは `'applock'`
	 * @return bool 成功時 `true`、失敗時に `false` を返します。
	 */
	public function unlock( $lockname = 'applock' ){
		if( !preg_match( '/^[a-zA-Z0-9_-]+$/ism' , $lockname ) ){ $lockname = 'applock'; }
		$lockfilepath = $this->px->get_conf('paths.px_dir').'_sys/applock/'.$lockname.'.txt';

		#	PHPのFileStatusCacheをクリア
		clearstatcache();

		$this->rm( $lockfilepath );
		$RTN = $this->file_overwrite( $lockfilepath , '' );
		return	$RTN;
	}//unlock()

	/**
	 * 実行したコマンドの標準出力を得て返す。
	 * 
	 * @param string $cmd コマンド
	 * @return string コマンドが発行した標準出力
	 */
	public function get_cmd_stdout( $cmd ){
		$res = @popen( $cmd , 'r' );
		$RTN = '';
		while( $LINE = @fgets( $res ) ){
			$RTN .= $LINE;
		}
		@pclose($res);
		return $RTN;
	}//get_cmd_stdout()


	/**
	 * サーバがUNIXパスか調べる。
	 * 
	 * @return bool UNIXパスなら `true`、それ以外なら `false` を返します。
	 */
	public function is_unix(){
		$rootpath = @realpath( '/' );
		if( $rootpath == '/' ){
			return	true;
		}
		return	false;
	}//is_unix()

	/**
	 * サーバがWindowsパスか調べる。
	 * 
	 * @return bool Windowsパスなら `true`、それ以外なら `false` を返します。
	 */
	public function is_windows(){
		$rootpath = @realpath( '/' );
		if( preg_match( '/^(?:[a-z]\:|'.preg_quote('\\\\','/').')/is' , $rootpath ) ){
			return	true;
		}
		return	false;
	}//is_windows()

}
?>