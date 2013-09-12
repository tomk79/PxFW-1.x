<?php
$this->load_px_class('/bases/pxcommand.php');

/**
 * PX Command: rdbを表示する
 **/
class px_pxcommands_rdb extends px_bases_pxcommand{

	private $command = array();
	private $pageinfo = null;

	public function __construct( $command , $px ){
		parent::__construct( $command , $px );

		$this->command = $command;

		switch( $command[1] ){
			case 'exec_sql':
				$this->execute_exec_sql();
				break;
			case 'result':
				$this->execute_result();
				break;
			default:
				$this->home();
				break;
		}
	}//__construct()

	/**
	 * ホーム画面を表示する。
	 */
	private function home(){

		$src = '';
		$src .= '<p>データベース '.t::h($this->px->dbh()->get_db_conf('dbms')).' に対し、SQLを実行します。</p>'."\n";
		$src .= '<form action="?PX='.t::h( implode('.',$this->command) ).'.exec_sql" id="cont_sql_form" method="get" onsubmit="return !contSql.submitForm();">'."\n";
		$src .= '<p><textarea name="sql" style="width:100%; height:80px;">';
		$src .= t::h( $this->px->dbh()->file_get_contents( $this->px->req()->get_param('sql') ) );
		$src .= '</textarea></p>'."\n";
		$src .= '<p class="center"><button>SQLを実行する</button></p>'."\n";
		$src .= '</form>'."\n";
		$src .= '<div class="cont_results" style="margin-bottom:4em;">'."\n";
		$src .= '</div>'."\n";
		$src .= '<div class="unit">'."\n";
		$src .= '<ul>'."\n";
		$src .= '	<li><a href="javascript:;" onclick="contSql.execSql(\'\\\\d\');return false;">テーブルの一覧を取得する</a></li>'."\n";
		$src .= '</ul>'."\n";
		$src .= '</div>'."\n";

		ob_start();
?>
<script type="text/javascript">
var contSql = new (function(formElm){
	var formElm;

	/**
	 * SQLを実行する。
	 */
	this.execSql = function(sqlString){
		var textarea = $('textarea[name=sql]',formElm);
		textarea.val(sqlString);
		this.submitForm();
	}

	/**
	 * フォームを送信し、SQLを実行する。
	 * 実際には送信せず、AJAXで処理されています。
	 */
	this.submitForm = function(){
		var textarea = $('textarea[name=sql]',formElm);
		var sql = textarea[0].value;

		$('.cont_results').html('<p class="center">通信中</p>');
		$.ajax({
			url: <?php print t::data2jssrc('?PX='.$this->command[0].'.exec_sql.json'); ?> ,
			dataType: 'json' ,
			data:{
				sql: sql
			} ,
			success: function( data ){
				// if(console){console.debug(data);}
				var SRC = '';
				if( data.value === false ){
					SRC += '<p class="center">検索エラー。検索結果に false を受け取りました。</p>';
				}else{
					SRC += '<p class="center">'+(data.value.length)+'件の検索結果。</p>';
					SRC += '<table class="def">';
					SRC += '<tr>';
					for(var key2 in data.define){
						SRC += '<th style="word-break:break-all;">';
						SRC += data.define[key2];
						SRC += '</th>';
					}
					if(data.sql=='\\d'){
						SRC += '<th></th>';
					}
					SRC += '</tr>';
					if(data.value.length){
						for(var key1 in data.value){
							SRC += '<tr>';
							for(var key2 in data.value[key1]){
								SRC += '<td>';
								SRC += data.value[key1][key2];
								SRC += '</td>';
							}
							if(data.sql=='\\d'){
								SRC += '<td style="word-break:break-all;">';
								SRC += '<a href="javascript:;" onclick="contSql.execSql(\'SELECT count(*) AS count FROM '+data.value[key1]['table_name']+';\');return false;">count(*)</a>|';
								SRC += '<a href="javascript:;" onclick="contSql.execSql(\'SELECT * FROM '+data.value[key1]['table_name']+' LIMIT 0,20;\');return false;">SELECT</a>';
								SRC += '</td>';
							}
							SRC += '</tr>';
						}
					}
					SRC += '</table>';
				}
				$('.cont_results').html(SRC);
			} ,
			error: function(){
				$('.cont_results').html('<p class="center">エラーが発生しました。</p>');
			}
		});

		return true;
	}
})(document.getElementById('cont_sql_form'));
</script>
<?php
		$src .= ob_get_clean();

		print $this->html_template($src);
		exit;
	}

	/**
	 * SQLを実行する。
	 */
	private function execute_exec_sql(){
		$sql = $this->px->req()->get_param('sql');
		$sql = trim($sql);
		$value = array();

		switch( $sql ){
			case '\\d':
				$tmp_value = $this->px->dbh()->get_tablelist();
				$value = array();
				foreach( $tmp_value as $row ){
					array_push( $value , array('table_name'=>$row) );
				}
				unset($tmp_value);
				break;
			default:
				$res = $this->px->dbh()->send_query( $sql );
				$value = $this->px->dbh()->get_results();
				$this->px->dbh()->commit();
				$affected_rows = $this->px->dbh()->get_affected_rows();
				$last_insert_id = $this->px->dbh()->get_last_insert_id();
				break;
		}

		$define = array();
		if( is_array($value[0]) ){
			$define = array_keys($value[0]);
			    //↑メモ：テーブル名が分かる場合は、$dbh->get_table_definition() から取得したい。
		}

		switch( $this->command[2] ){
			case 'json':
				$data = array();
				$data['sql'] = $sql;
				$data['value'] = $value;
				$data['define'] = $define;
				$data['affected_rows'] = $affected_rows;
				$data['last_insert_id'] = $last_insert_id;
				$data['message'] = $message;
				$json = t::data2jssrc( $data );
				print $json;
				exit;
				break;
		}
		$this->px->redirect( '?PX='.$this->command[0].'.result' );
		exit;
	}

	/**
	 * 上書き完了画面を表示する。
	 */
	private function execute_result(){

		$src = '';
		$src .= '<p>データベースに対して、SQLを実行しました。</p>'."\n";

		$src .= '<form action="?PX='.t::h( $this->command[0] ).'" method="post">'."\n";
		$src .= '<p class="center"><button>戻る</button></p>'."\n";
		$src .= '</form>'."\n";
		print $this->html_template($src);
		exit;
	}

}

?>