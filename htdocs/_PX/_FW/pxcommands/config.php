<?php
$this->load_px_class('/bases/pxcommand.php');

/**
 * PX Command: configを表示する
 **/
class px_pxcommands_config extends px_bases_pxcommand{
	private $config_ary = array();

	public function __construct( $command , $px ){
		parent::__construct( $command , $px );
		$this->execute();
	}//__construct()

	/**
	 * Execute PX Command "config".
	 */
	private function execute(){
		$src = '';

		$src .= '<h2>mainconf</h2>'."\n";

		$src .= '<div class="unit">'."\n";
		$src .= '	<p>'."\n";
		$src .= '		コンフィグに設定された内容を表示します。<br />'."\n";
		$src .= '	</p>'."\n";
		$src .= '	<p>'."\n";
		$src .= '		コンフィグは、次のファイルを編集して変更することができます。<br />'."\n";
		$src .= '	</p>'."\n";
		$src .= '	<ul>'."\n";
		$src .= '		<li style="word-break:break-all;">'.t::h( realpath( $this->px->get_path_conf() ) ).'</li>'."\n";
		$src .= '	</ul>'."\n";
		$src .= '</div><!-- /.unit -->'."\n";

		$this->config_ary = $this->px->get_conf_all();
		$src .= '<div class="unit">'."\n";

		$src .= '<h3>project</h3>'."\n";
		$src .= '<table class="def" style="width:100%;">' . "\n";
		$src .= '<colgroup><col width="30%" /><col width="30%" /><col width="40%" /></colgroup>' . "\n";
		$src .= $this->mk_config_unit('project.id','プロジェクトID');
		$src .= $this->mk_config_unit('project.name','プロジェクト名');
		$src .= $this->mk_config_unit('project.path_top','トップページのパス');
		$src .= $this->mk_config_unit('project.auth_type','認証形式');
		$src .= $this->mk_config_unit('project.auth_name','認証ユーザーID');
		$src .= $this->mk_config_unit('project.auth_password','認証パスワード');
		$src .= '</table>' . "\n";

		$src .= '<h3>paths</h3>'."\n";
		$src .= '<table class="def" style="width:100%;">' . "\n";
		$src .= '<colgroup><col width="30%" /><col width="30%" /><col width="40%" /></colgroup>' . "\n";
		$src .= $this->mk_config_unit('paths.px_dir','Pickles Framework のディレクトリパス','realpath',true);
		$src .= $this->mk_config_unit('paths.access_log','アクセスログ出力先ファイルパス','realpath');
		$src .= $this->mk_config_unit('paths.error_log','エラーログ出力先ファイルパス','realpath');
		$src .= '</table>' . "\n";

		$src .= '<h3>colors</h3>'."\n";
		$src .= '<table class="def" style="width:100%;">' . "\n";
		$src .= '<colgroup><col width="30%" /><col width="30%" /><col width="40%" /></colgroup>' . "\n";
		$src .= $this->mk_config_unit('colors.main','メインカラー');
		$src .= '</table>' . "\n";


		$src .= '<h3>publish</h3>'."\n";
		$src .= '<table class="def" style="width:100%;">' . "\n";
		$src .= '<colgroup><col width="30%" /><col width="30%" /><col width="40%" /></colgroup>' . "\n";
		$src .= $this->mk_config_unit('publish.path_publish_dir','パブリッシュ先ディレクトリパス','realpath');
		$src .= $this->mk_config_unit('publish.paths_ignore','パブリッシュ対象外パスの一覧');
		$src .= '</table>' . "\n";

		$src .= '<h3>dbms</h3>'."\n";
		$src .= '<table class="def" style="width:100%;">' . "\n";
		$src .= '<colgroup><col width="30%" /><col width="30%" /><col width="40%" /></colgroup>' . "\n";
		$src .= $this->mk_config_unit('dbms.prefix','テーブル名の接頭辞');
		$src .= $this->mk_config_unit('dbms.dbms','DBMS名');
		$src .= $this->mk_config_unit('dbms.host','接続先ホスト名');
		$src .= $this->mk_config_unit('dbms.port','接続先ポート番号');
		$src .= $this->mk_config_unit('dbms.database_name','データベース名(SQLiteの場合は、データベースのパス)');
		$src .= $this->mk_config_unit('dbms.user','ユーザー名');
		$src .= $this->mk_config_unit('dbms.password','パスワード');
		$src .= $this->mk_config_unit('dbms.charset','文字セット');
		$src .= '</table>' . "\n";

		$src .= '<h3>commands</h3>'."\n";
		$commands = array();
		foreach($this->config_ary as $key=>$val){
			if( preg_match('/^commands\.(.*)$/s', $key, $command_matches) ){
				array_push($commands, array('key'=>$key,'command'=>$command_matches[1] ));
			}
		}
		if( !count($commands) ){
			$src .= '<p>コマンドの設定はありません。</p>' . "\n";
		}else{
			$src .= '<table class="def" style="width:100%;">' . "\n";
			$src .= '<colgroup><col width="30%" /><col width="30%" /><col width="40%" /></colgroup>' . "\n";
			foreach($commands as $command){
				$src .= $this->mk_config_unit($command['key'],$command['command'].'コマンドのパス','string');
			}
			$src .= '</table>' . "\n";
		}

		$src .= '<h3>system</h3>'."\n";
		$src .= '<table class="def" style="width:100%;">' . "\n";
		$src .= '<colgroup><col width="30%" /><col width="30%" /><col width="40%" /></colgroup>' . "\n";
		$src .= $this->mk_config_unit('system.allow_pxcommands','PX Commands の実行を許可するフラグ(1=許可, 0=不許可)','bool');
		$src .= $this->mk_config_unit('system.session_name','セッションID');
		$src .= $this->mk_config_unit('system.session_expire','セッションの有効期限(秒)');
		$src .= $this->mk_config_unit('system.default_theme_id','デフォルトのテーマID');
		$src .= $this->mk_config_unit('system.filesystem_encoding','ファイル名の文字エンコード');
		$src .= $this->mk_config_unit('system.output_encoding','出力エンコード');
		$src .= $this->mk_config_unit('system.output_eof_coding',' 出力改行コード("CR"|"LF"|"CRLF")');
		$src .= '</table>' . "\n";

		$src .= '</div><!-- /.unit -->'."\n";

		if( count($this->config_ary) ){
			$src .= '<div class="unit">'."\n";
			$src .= '<h3>その他の値</h3>'."\n";
			$src .= $this->mk_ary_table($this->config_ary);
			$src .= '</div><!-- /.unit -->'."\n";
		}

		$src .= '<div class="unit">'."\n";
		$src .= '<h2>プラグイン</h2>'."\n";
		$tmp_path_plugins_base_dir = $this->px->get_conf('paths.px_dir').'plugins/';
		$tmp_plugin_list = $this->px->dbh()->ls( $tmp_path_plugins_base_dir );
		foreach( $tmp_plugin_list as $tmp_plugin_key=>$tmp_plugin_name ){
			if(!is_dir($tmp_path_plugins_base_dir.$tmp_plugin_name)){
				unset( $tmp_plugin_list[$tmp_plugin_key] );
			}
		}
		if( !count($tmp_plugin_list) ){
			$src .= '<p>プラグインは組み込まれていません。</p>'."\n";
		}else{
			$src .= '<p>次のプラグインが組み込まれています。</p>'."\n";
			$src .= '	<table class="def">'."\n";
			$src .= '		<thead>'."\n";
			$src .= '			<tr>'."\n";
			$src .= '				<th>プラグイン名</th>'."\n";
			$src .= '				<th style="word-break:break-all;">version</th>'."\n";
			$src .= '				<th style="word-break:break-all;">object</th>'."\n";
			$src .= '				<th style="word-break:break-all;">initialize</th>'."\n";
			$src .= '				<th style="word-break:break-all;">pxcommand</th>'."\n";
			$src .= '				<th style="word-break:break-all;">outputfilter</th>'."\n";
			$src .= '			</tr>'."\n";
			$src .= '		</thead>'."\n";
			$src .= '		<tbody>'."\n";
			foreach( $tmp_plugin_list as $tmp_plugin_name ){
				$src .= '			<tr>'."\n";
				$src .= '				<th style="word-break:break-all;">'.t::h($tmp_plugin_name).'</th>'."\n";
				$plugin_version = '????';
				if( is_file( $tmp_path_plugins_base_dir.$tmp_plugin_name.'/register/info.php' ) ){
					$class_name_info = $this->px->load_px_plugin_class('/'.$tmp_plugin_name.'/register/info.php');
					if($class_name_info){
						$obj_info = new $class_name_info();
						if( is_callable( array( $obj_info, 'get_version' ) ) ){
							$plugin_version = $obj_info->get_version();
						}
					}
				}
				$src .= '				<td class="center" style="word-break:break-all;">'.t::h($plugin_version).'</td>'."\n";
				$src .= '				<td class="center">'.(is_file( $tmp_path_plugins_base_dir.$tmp_plugin_name.'/register/object.php' )?'○':'-').'</td>'."\n";
				$src .= '				<td class="center">'.(is_file( $tmp_path_plugins_base_dir.$tmp_plugin_name.'/register/initialize.php' )?'○':'-').'</td>'."\n";
				$src .= '				<td class="center">'.(is_file( $tmp_path_plugins_base_dir.$tmp_plugin_name.'/register/pxcommand.php' )?'○':'-').'</td>'."\n";
				$src .= '				<td class="center">'.(is_file( $tmp_path_plugins_base_dir.$tmp_plugin_name.'/register/outputfilter.php' )?'○':'-').'</td>'."\n";
				$src .= '			</tr>'."\n";
			}
			$src .= '		</tbody>'."\n";
			$src .= '	</table>'."\n";
		}
		unset($tmp_path_plugins_base_dir,$tmp_plugin_list,$tmp_plugin_name,$tmp_class_name);
		$src .= '</div><!-- /.unit -->'."\n";

		print $this->html_template($src);
		exit;
	}

	/**
	 * コンフィグ項目1件の出力
	 */
	private function mk_config_unit($key,$label,$type='string',$must = false){
		$src = '';
		$src .= '	<tr>'."\n";
		$src .= '		<th style="word-break:break-all;">'.t::h( $key ).'</th>'."\n";
		$src .= '		<th style="word-break:break-all;">'.t::h( $label ).'</th>'."\n";
		$src .= '		<td style="word-break:break-all;">';
		if(is_null($this->config_ary[$key])){
			$src .= '<span style="font-style:italic; color:#aaaaaa; background-color:#ffffff;">null</span>';
		}else{
			switch(strtolower($type)){
				case 'bool':
					$src .= ($this->config_ary[$key]?'<span style="font-style:italic; color:#0033dd; background-color:#ffffff;">true</span>':'<span style="font-style:italic; color:#0033dd; background-color:#ffffff;">false</span>');
					break;
				case 'realpath':
					$src .= $this->h( realpath( $this->config_ary[$key] ) ).'<br />(<q>'.$this->h( $this->config_ary[$key] ).'</q>)';
					break;
				case 'string':
				default:
					$src .= $this->h( $this->config_ary[$key] );
					break;
			}
		}
		$src .= '</td>'."\n";
		$src .= '	</tr>'."\n";
		unset($this->config_ary[$key]);
		return $src;
	}

	/**
	 * HTML特殊文字の変換
	 */
	private function h($txt){
		$txt = t::h($txt);
		$txt = preg_replace('/\r\n|\r|\n/','<br />',$txt);
		return $txt;
	}

	/**
	 * 配列をtableのhtmlソースに変換
	 */
	private function mk_ary_table( $ary ) {
		if(is_array($ary)) {
			if($this->is_hash($ary)) {
				$html = '';
				$html .= '<table class="def" style="width:100%;">' . "\n";
				$html .= '<colgroup><col width="40%" /><col width="60%" /></colgroup>' . "\n";
				foreach ($ary as $key => $val) {
					$html .= '<tr>' . "\n";
					$html .= '<th style="word-break:break-all;">' .t::h( $key ). '</th>' . "\n";
					$html .= '<td style="word-break:break-all;">' .$this->mk_ary_table($val). '</td>' . "\n";
					$html .= '</tr>' . "\n";
				}
				$html .= '</table>' . "\n";
			} elseif(!$this->is_hash($ary)) {
				$html = '';
				$html .= '<table class="def">' . "\n";
				$html .= '<colgroup><col width="30%" /><col width="70%" /></colgroup>' . "\n";
				foreach ($ary as $val) {
					$html .= '<tr>' . "\n";
					$html .= '<td style="word-break:break-all;">' .t::h( $val ). '</td>' . "\n";
					$html .= '</tr>' . "\n";
				}
				$html .= '</table>' . "\n";
			}

			} elseif(!is_array($ary)) {
				$html = t::h( $ary );
			}
		return $html;
	}//mk_ary_table()

	/**
	 * 連想配列(true)か添付配列(false)か調べる
	 */
	private function is_hash( $ary ) {
		$i = 0;
		foreach($ary as $key => $dummy) {
			if ( $key !== $i++ ) return true;
		}
		return false;
	}


}
?>