<?php
/**
 * class px_pxcommands_publish
 * 
 * @author Tomoya Koyanagi <tomk79@gmail.com>
 */
$this->load_px_class('/bases/pxcommand.php');

/**
 * PX Command: publishを実行する
 * 
 * @author Tomoya Koyanagi <tomk79@gmail.com>
 */
class px_pxcommands_publish extends px_bases_pxcommand{

	/**
	 * ドキュメントルートディレクトリのパス
	 */
	private $path_docroot_dir;
	/**
	 * パブリッシュ先ディレクトリ
	 */
	private $path_publish_dir;
	/**
	 * 時書き出しディレクトリ(固定)
	 */
	private $path_tmppublish_dir;
	/**
	 * ロックファイル
	 */
	private $path_lockfile;
	/**
	 * パブリッシュ対象外のパス
	 */
	private $paths_ignore = array();

	/**
	 * パブリッシュ対象の一覧
	 */
	private $queue_items = array();
	/**
	 * パブリッシュ完了した対象の一覧
	 */
	private $done_items = array();
	/**
	 * パブリッシュ対象パス
	 */
	private $path_target = null;
	/**
	 * パブリッシュ対象パス(パラメータで指示された分)
	 */
	private $param_path_target = null;
	/**
	 * その他の内部エラー
	 */
	private $internal_errors = array();
	/**
	 * 拡張子とパブリッシュタイプのマッピング配列
	 * 
	 *  'http'|'include_text'|'copy'|'nopublish'
	 * 
	 * ※この設定は、mainconf.ini の publish_extensions の項目で上書きできるようになりました。
	 * 　ここに実装されている値はデフォルトです。変更する場合は、mainconf.ini を編集してください。
	 */
	private $publish_type_extension_map = array(
		'html' =>'http' ,
		'css'  =>'http' ,
		'js'   =>'http' ,
		'php'  =>'copy' ,
		'nopublish'=> 'nopublish' ,
		'inc'  =>'include_text' ,
	);
	/**
	 * パブリッシュツールのUSER_AGENT名
	 */
	private $crawler_user_agent = 'PicklesCrawler';
	/**
	 * publish API を持ったプラグインの一覧
	 */
	private $plugins_list = array();

	/**
	 * コンストラクタ
	 * 
	 * @param array $command PXコマンド名
	 * @param object $px $pxオブジェクト
	 */
	public function __construct( $command , $px ){
		parent::__construct( $command , $px );

		$this->path_target = $this->px->dbh()->get_realpath( $this->px->get_install_path() ).$_SERVER['PATH_INFO'];
		$this->path_target = preg_replace('/^\/+/s','/',$this->path_target);
		$this->path_target = preg_replace('/\/'.$this->px->get_directory_index_preg_pattern().'$/s','/',$this->path_target);
		$func_check_param_path = function($path){
			if( !preg_match('/^\//', $path) ){
				return false;
			}
			$path = preg_replace('/(?:\/|\\\\)/', '/', $path);
			if( preg_match('/(?:^|\/)\.{1,2}(?:$|\/)/', $path) ){
				return false;
			}
			return true;
		};
		$param_path_target = $this->px->req()->get_param('path_target');
		if( strlen( $param_path_target ) && $param_path_target != $this->path_target && $func_check_param_path( $param_path_target ) ){
			$this->path_target = $param_path_target;
			$this->param_path_target = $param_path_target;
		}

		$command = $this->get_command();
		$this->setup();

		switch( @$command[1] ){
			case 'run':
				$this->execute();
				break;
			default:
				$this->homepage();
				break;
		}
	}//__construct()

	/**
	 * 拡張子別パブリッシュタイプマップに登録する。
	 * 
	 * @param string $extension 拡張子
	 * @param string $publish_type パブリッシュの種類
	 * @return bool 成功時 `true`、失敗時 `false` を返します。
	 */
	public function set_publish_type_extension_map($extension, $publish_type){
		if(!strlen($extension)){
			return false;
		}
		$this->publish_type_extension_map[$extension] = $publish_type;
		return true;
	}

	/**
	 * ホームページを表示する。
	 * 
	 * HTMLを標準出力した後、`exit()` を発行してスクリプトを終了します。
	 * 
	 * @return void
	 */
	private function homepage(){
		$command = $this->get_command();
		$src = '';
		ob_start();?>
<script type="text/javascript">
function contEditPublishTargetPath(){
	$('.cont_publish_target_path_preview').hide();
	$('.cont_publish_target_path_editor').show();
}
function contEditPublishTargetPathApply(formElm){
	var path = $('input[name=path]', formElm).val();
	window.location.href = path + '?PX=publish&path_target='+encodeURIComponent(path);
}
</script>
<?php
		$src .= ob_get_clean();
		if(!is_dir($this->path_docroot_dir)){
			$src .= '<div class="unit">'."\n";
			$src .= '	<p class="error">ドキュメントルートディレクトリが存在しません。</p>'."\n";
			$src .= '	<ul><li style="word-break:break-all;">'.t::h( $this->path_docroot_dir ).'</li></ul>'."\n";
			$src .= '</div><!-- /.unit -->'."\n";
		}elseif(!is_dir($this->path_tmppublish_dir)){
			$src .= '<div class="unit">'."\n";
			$src .= '	<p class="error">パブリッシュ先一時ディレクトリが存在しません。</p>'."\n";
			$src .= '	<ul><li style="word-break:break-all;">'.t::h( $this->path_tmppublish_dir ).'</li></ul>'."\n";
			$src .= '</div><!-- /.unit -->'."\n";
		}elseif(!is_writable($this->path_tmppublish_dir)){
			$src .= '<div class="unit">'."\n";
			$src .= '	<p class="error">パブリッシュ先一時ディレクトリに書き込み許可がありません。</p>'."\n";
			$src .= '	<ul><li style="word-break:break-all;">'.t::h( $this->path_tmppublish_dir ).'</li></ul>'."\n";
			$src .= '</div><!-- /.unit -->'."\n";
		}elseif( strlen($this->px->get_conf('publish.path_publish_dir')) && !is_dir($this->px->get_conf('publish.path_publish_dir')) ){
			$src .= '<div class="unit">'."\n";
			$src .= '	<p class="error">パブリッシュ先ディレクトリが存在しません。</p>'."\n";
			$src .= '	<ul><li style="word-break:break-all;">'.t::h( $this->px->dbh()->get_realpath( $this->px->get_conf('publish.path_publish_dir') ).'/' ).'</li></ul>'."\n";
			$src .= '</div><!-- /.unit -->'."\n";
		}elseif( strlen($this->px->get_conf('publish.path_publish_dir')) && !is_writable($this->px->get_conf('publish.path_publish_dir')) ){
			$src .= '<div class="unit">'."\n";
			$src .= '	<p class="error">パブリッシュ先ディレクトリに書き込み許可がありません。</p>'."\n";
			$src .= '	<ul><li style="word-break:break-all;">'.t::h( $this->px->dbh()->get_realpath( $this->px->get_conf('publish.path_publish_dir') ).'/' ).'</li></ul>'."\n";
			$src .= '</div><!-- /.unit -->'."\n";
		}elseif( $this->is_locked() ){
			$src .= '<div class="unit">'."\n";
			$src .= '	<p>パブリッシュは<strong>ロックされています</strong>。</p>'."\n";
			$src .= '	<p>'."\n";
			$src .= '		現在、他のプロセスでパブリッシュが実行中である可能性があります。<br />'."\n";
			$src .= '		しばらく待ってから、リロードしてください。<br />'."\n";
			$src .= '	</p>'."\n";
			$src .= '	<p>'."\n";
			$src .= '		ロックファイルの内容を下記に示します。<br />'."\n";
			$src .= '	</p>'."\n";
			$src .= '	<blockquote><pre>'.t::h( $this->px->dbh()->file_get_contents( $this->path_lockfile ) ).'</pre></blockquote>'."\n";
			$src .= '	<p>'."\n";
			$src .= '		ロックファイルは下記の時刻に更新されました。<br />'."\n";
			$src .= '	</p>'."\n";
			$src .= '	<blockquote><pre>'.t::h( date( 'Y-m-d H:i:s', filemtime( $this->path_lockfile ) ) ).'</pre></blockquote>'."\n";
			$src .= '	<p>'."\n";
			$src .= '		ロックファイルは、次のパスに存在します。<br />'."\n";
			$src .= '	</p>'."\n";
			$src .= '	<blockquote><pre>'.t::h( realpath( $this->path_lockfile ) ).'</pre></blockquote>'."\n";
			$src .= '</div><!-- /.unit -->'."\n";
		}else{
			$src .= '<div class="unit">'."\n";
			$src .= '	<p>プロジェクト『'.t::h($this->px->get_conf('project.name')).'』をパブリッシュします。</p>'."\n";
			$src .= '<table class="def" style="width:100%;">'."\n";
			$src .= '	<tbody>'."\n";
			$src .= '		<tr>'."\n";
			$src .= '			<th style="width:30%;">パブリッシュ対象のパス</th>'."\n";
			$src .= '			<td style="width:70%;" class="cont_publish_target_path">'."\n";
			$src .= '				<div class="cont_publish_target_path_preview">'."\n";
			$src .= '					<div style="word-break:break-all;">'.t::h( $this->path_target ).'</div>'."\n";
			$src .= '					<div class="small"><a href="javascript:contEditPublishTargetPath();" class="icon">変更する</a></div>'."\n";
			$src .= '				</div>'."\n";
			$src .= '				<div class="cont_publish_target_path_editor" style="display:none;">'."\n";
			$src .= '					<form action="?" method="get" onsubmit="contEditPublishTargetPathApply(this); return false;" class="inline">'."\n";
			$src .= '						<input type="text" name="path" size="25" style="max-width:70%;" value="'.t::h( $this->path_target ).'" />'."\n";
			$src .= '						<input type="submit" style="width:20%;" value="変更する" />'."\n";
			$src .= '					</form>'."\n";
			$src .= '				</div>'."\n";
			$src .= '			</td>'."\n";
			$src .= '		</tr>'."\n";
			$src .= '		<tr>'."\n";
			$src .= '			<th style="width:30%;">適用するテーマ</th>'."\n";
			$src .= '			<td style="width:70%;">'.t::h($this->px->get_conf('system.default_theme_id')).'</td>'."\n";
			$src .= '		</tr>'."\n";
			$src .= '	</tbody>'."\n";
			$src .= '</table><!-- /table.def -->'."\n";
			$src .= ''."\n";

			$src .= '</div><!-- /.unit -->'."\n";
			$internal_errors = $this->get_internal_error_log();
			if( count($internal_errors) ){
				$src .= '<div class="unit form_error_box">'."\n";
				$src .= '	<p>次のエラーがありました。</p>'."\n";
				$src .= '	<ul>'."\n";
				foreach($internal_errors as $error_row){
					$src .= '		<li>'.t::h($error_row['message']).'</li>'."\n";
				}
				$src .= '	</ul>'."\n";
				$src .= '</div><!-- /.form_error_box -->'."\n";
				$src .= '<div class="unit">'."\n";
				$src .= '	<p>パブリッシュを実行する前に、設定を変更し、エラーを解消してください。</p>'."\n";
				$src .= '</div><!-- /.unit -->'."\n";

			}

			$src .= '<div class="unit">'."\n";
			$src .= '	<p>次のボタンをクリックしてパブリッシュを実行してください。</p>'."\n";
			$src .= '	<form action="?" method="get" target="_blank">'."\n";
			$src .= '	<p class="center"><button class="xlarge">パブリッシュを実行する</button></p>'."\n";
			$src .= '	<div><input type="hidden" name="PX" value="publish.run" />'.(strlen( $this->param_path_target )?'<input type="hidden" name="path_target" value="'.t::h( $this->path_target ).'" />':'').'</div>'."\n";
			$src .= '	</form>'."\n";
			$src .= '</div><!-- /.unit -->'."\n";

			$src .= '<div class="topic_box">'."\n";
			$src .= '	<p class="topic_box-heading">コマンドラインから実行する</p>'."\n";
			$src .= '   <p>パブリッシュは、次のコマンドから実行することもできます。</p>'."\n";
			$src .= '   <dl>'."\n";
			$src .= '		<dt>"curl" コマンドが使える場合</dt>'."\n";
			$auth_curl = '';
			$auth_wget = '';
			if( strlen( $this->px->get_conf('project.auth_name') ) || strlen( $this->px->get_conf('project.auth_password') ) ){
				$auth_curl = '--user '.t::data2text( $this->px->get_conf('project.auth_name').':'.$this->px->get_conf('project.auth_password') ).' ';
				$auth_wget = '--http-user='.t::data2text( $this->px->get_conf('project.auth_name') ).' --http-passwd='.t::data2text( $this->px->get_conf('project.auth_password') ).' ';
			}
			$src .= '       	<dd>$ curl '.t::h($auth_curl).''.t::h( t::data2text('http://'.$_SERVER['HTTP_HOST'].$this->path_target.'?PX=publish.run'.(strlen( $this->param_path_target )?'&path_target='.urlencode( $this->path_target ):'') ) ).'</dd>'."\n";
			$src .= '		<dt>"wget" コマンドが使える場合</dt>'."\n";
			$src .= '       	<dd>$ wget '.t::h($auth_wget).''.t::h( t::data2text('http://'.$_SERVER['HTTP_HOST'].$this->path_target.'?PX=publish.run'.(strlen( $this->param_path_target )?'&path_target='.urlencode( $this->path_target ):'') ) ).'</dd>'."\n";
			$src .= '   </dl>'."\n";
			$src .= '</div><!-- /.topic_box -->'."\n";
			$src .= ''."\n";

		}
		print $this->html_template($src);
		exit;
	}

	/**
	 * Execute PX Command "publish".
	 * 
	 * パブリッシュを実行します。
	 * 
	 * ログテキストを標準出力した後、`exit()` を発行してスクリプトを終了します。
	 * 
	 * @access private
	 * @return void
	 */
	private function execute(){
		$command = $this->get_command();
		while( @ob_end_clean() );
		@header('Content-type: text/plain');
		error_reporting(0);
		print ''.$command[0].' | Pickles Framework (version:'.$this->px->get_version().')'."\n";
		print 'project "'.$this->px->get_conf('project.name').'" ('.$this->px->get_conf('project.id').')'."\n";
		print '------'."\n";
		print 'PX command "'.$command[0].'" executed.'."\n";
		if( $this->px->req()->is_cmd() ){
			$auth_curl = '';
			$auth_wget = '';
			if( strlen( $this->px->get_conf('project.auth_name') ) || strlen( $this->px->get_conf('project.auth_password') ) ){
				$auth_curl = '--user '.t::data2text( $this->px->get_conf('project.auth_name').':'.$this->px->get_conf('project.auth_password') ).' ';
				$auth_wget = '--http-user='.t::data2text( $this->px->get_conf('project.auth_name') ).' --http-passwd='.t::data2text( $this->px->get_conf('project.auth_password') ).' ';
			}
			print 'Sorry, CUI is not supported.'."\n";
			print 'Please try below...'."\n";
			print '    - If you can use "curl" command.'."\n";
			print '      (If your system is Mac OSX, maybe you can use this.)'."\n";
			print '        $ curl '.$auth_curl.'http://{$yourdomain}'.$this->path_target.'?PX=publish.run'."\n";
			print '    - If you can use "wget" command.'."\n";
			print '        $ wget '.$auth_wget.'http://{$yourdomain}'.$this->path_target.'?PX=publish.run'."\n";
			print ''."\n";
			print ''."\n";
			print 'exit.'."\n";
			exit;
		}
		print 'ProcessID='.getmypid()."\n";
		print date('Y-m-d H:i:s')."\n";
		print '------'."\n";
		print 'path_docroot_dir => '.$this->path_docroot_dir."\n";
		print 'path_tmppublish_dir => '.$this->path_tmppublish_dir."\n";
		print 'path_lockfile => '.$this->path_lockfile."\n";
		print 'path_publish_dir => '.$this->path_publish_dir."\n";
		print 'path_target => '.$this->path_target.'*'."\n";
		print 'paths_ignore => '."\n";
		foreach( $this->paths_ignore as $row ){
			print '  - '.(is_string($row)?$row:($row===false?'[invalid_path]':'[unknown_type]'))."\n";
		}
		unset($row);

		if(!is_dir($this->path_docroot_dir)){
			print '------'."\n";
			print '[ERROR] path_docroot_dir is NOT exists.'."\n";
			print 'exit.'."\n";
			exit;
		}
		if(!is_dir($this->path_tmppublish_dir)){
			print '------'."\n";
			print '[ERROR] path_tmppublish_dir is NOT exists.'."\n";
			print 'exit.'."\n";
			exit;
		}
		if(!is_writable($this->path_tmppublish_dir)){
			print '------'."\n";
			print '[ERROR] path_tmppublish_dir is NOT writable.'."\n";
			print 'exit.'."\n";
			exit;
		}
		if( strlen($this->px->get_conf('publish.path_publish_dir')) && !is_dir($this->px->get_conf('publish.path_publish_dir')) ){
			print '------'."\n";
			print '[ERROR] path_publish_dir is NOT exists.'."\n";
			print 'exit.'."\n";
			exit;
		}
		if( strlen($this->px->get_conf('publish.path_publish_dir')) && !is_writable($this->px->get_conf('publish.path_publish_dir')) ){
			print '------'."\n";
			print '[ERROR] path_publish_dir is NOT writable.'."\n";
			print 'exit.'."\n";
			exit;
		}

		flush();
		if( !$this->lock() ){//ロック
			print '------'."\n";
			print 'publish is now locked.'."\n";
			print '  (lockfile updated: '.@date('Y-m-d H:i:s', filemtime($this->path_lockfile)).')'."\n";
			print 'Try again later...'."\n";
			print 'exit.'."\n";
			exit;
		}

		flush();
		print '------'."\n";
		print '* cleaning publish dir.'."\n";
		flush();
		$this->clear_publish_dir();
		print 'done.'."\n";
		print ''."\n";
		flush();

		print '------'."\n";
		print '* start scaning directory.'."\n";
		set_time_limit(60*60*3);
		$this->scan_dirs( '/' );
		set_time_limit(300);
		flush();
		print '------'."\n";
		print '* add pages by sitemap.'."\n";
		foreach( $this->px->site()->get_sitemap() as $page_info ){
			if( !preg_match( '/^\//s', $page_info['path'] ) ){
				continue;
			}
			if( $this->px->site()->get_path_type( $page_info['path'] ) == 'dynamic' ){
				$dynamic_path_info = $this->px->site()->get_dynamic_path_info( $page_info['path'] );
				$page_info['path'] = $this->px->site()->bind_dynamic_path_param( $page_info['path'] , array() );
			}
			if($this->is_ignore_path($this->px->dbh()->get_realpath($_SERVER['DOCUMENT_ROOT'].'/'.$this->px->get_install_path().$page_info['path']))){
				continue;
			}
			$this->add_queue( $this->px->dbh()->get_realpath($this->px->get_install_path().$page_info['path']) );
		}
		flush();

		// プラグインによる加工処理
		//   パブリッシュの前処理 before_execute() を実施
		foreach( $this->plugins_list as $tmp_key=>$tmp_plugin_name ){
			$tmp_class_name = $this->px->load_px_plugin_class($tmp_plugin_name.'/register/publish.php');
			$plugin_object = new $tmp_class_name($this->px, $this);
			$plugin_object->before_execute($this->px->dbh()->get_realpath($this->path_tmppublish_dir.'/htdocs/').'/');
		}

		print '------'."\n";
		print '* start publishing.'."\n";
		while( 1 ){
			if( !count($this->queue_items) ){
				break;
			}
			$path = array_pop( $this->queue_items );
			flush();
			set_time_limit(60*60*3);
			$this->publish_file( $path );
			set_time_limit(30);
			flush();
			$this->touch_lockfile();
		}
		print 'done.'."\n";
		print ''."\n";

		// プラグインによる加工処理
		//   パブリッシュの後処理 after_execute() を実施
		foreach( $this->plugins_list as $tmp_key=>$tmp_plugin_name ){
			$tmp_class_name = $this->px->load_px_plugin_class($tmp_plugin_name.'/register/publish.php');
			$plugin_object = new $tmp_class_name($this->px, $this);
			$plugin_object->after_execute($this->px->dbh()->get_realpath($this->path_tmppublish_dir.'/htdocs/').'/');
		}

		if( strlen( $this->path_publish_dir ) && is_dir( $this->path_publish_dir ) ){
			set_time_limit(60*60*24*4);
			print '------'."\n";
			print 'copying files to publish.path_publish_dir...'."\n";
			$copy_from = $this->px->dbh()->get_realpath( $this->path_tmppublish_dir.'htdocs/'.'.'.$this->path_target );
			$copy_to   = $this->px->dbh()->get_realpath( $this->path_publish_dir.'.'.$this->path_target );
			print 'copy from: '.$copy_from ."\n";
			print 'copy to:   '.$copy_to   ."\n";
			if(is_dir($copy_from)){
				$this->px->dbh()->mkdir_all( $copy_to );
			}elseif(is_file($copy_from)){
				$this->px->dbh()->mkdir_all( dirname($copy_to) );
			}
			$this->px->dbh()->sync_dir( $copy_from , $copy_to );
			print ''."\n";
			set_time_limit(30);

			// プラグインによる加工処理
			//   パブリッシュの後処理 after_copying() を実施
			foreach( $this->plugins_list as $tmp_key=>$tmp_plugin_name ){
				$tmp_class_name = $this->px->load_px_plugin_class($tmp_plugin_name.'/register/publish.php');
				$plugin_object = new $tmp_class_name($this->px, $this);
				$plugin_object->after_copying($this->px->dbh()->get_realpath( $this->path_publish_dir ).'/');
			}
		}

		$this->unlock();//ロック解除

		$internal_errors = $this->get_internal_error_log();
		if( count($internal_errors) ){
			print '------'."\n";
			print 'Internal error.'."\n";
			foreach($internal_errors as $error_row){
				print '    - '.$error_row['message']."\n";
				$this->publish_error_log(array(
					'error'=>$error_row['message'],
					'path'=>null
				));
			}
			print ''."\n";
		}

		print '------'."\n";
		print 'publish completed.'."\n";
		print date('Y-m-d H:i:s')."\n";
		print 'see => '.(strlen( $this->path_publish_dir ) && is_dir( $this->path_publish_dir ) ? $this->path_publish_dir : $this->path_tmppublish_dir)."\n";
		print "\n";
		$this->publish_log( array(
			'result'=>null,
			'message'=>'exit;',
			'path'=>null,
		) );
		print 'exit.'."\n";
		exit;
	}

	/**
	 * セットアップ
	 * 
	 * @return bool 常に `true` を返します。
	 */
	private function setup(){
		$this->path_docroot_dir = t::realpath('.').'/';
		if( strlen($this->px->get_conf('publish.path_publish_dir')) && @is_dir($this->px->get_conf('publish.path_publish_dir')) ){
			$this->path_publish_dir = t::realpath($this->px->get_conf('publish.path_publish_dir')).'/';
		}
		if( !is_dir($this->px->get_conf('paths.px_dir').'_sys/publish/') ){
			$this->px->dbh()->mkdir($this->px->get_conf('paths.px_dir').'_sys/publish/');
		}

		$this->path_tmppublish_dir = $this->px->dbh()->get_realpath($this->px->get_conf('paths.px_dir').'_sys/publish/').'/';
		$this->path_lockfile = $this->path_tmppublish_dir.'applock.txt';

		array_push( $this->paths_ignore , $this->px->dbh()->get_realpath($this->px->get_conf('paths.px_dir')) );
		array_push( $this->paths_ignore , $this->px->dbh()->get_realpath($this->path_docroot_dir.'/.htaccess') );
		array_push( $this->paths_ignore , $this->px->dbh()->get_realpath($this->path_docroot_dir.'/_px_execute.php') );
		array_push( $this->paths_ignore , '*/.DS_Store' );
		array_push( $this->paths_ignore , '*/Thumbs.db' );
		array_push( $this->paths_ignore , '*.nopublish/*' );
		array_push( $this->paths_ignore , '*.nopublish.*' );//←PxFW 1.0.4 追加
		array_push( $this->paths_ignore , '*/.svn/' );
		array_push( $this->paths_ignore , '*/.git/' );
		array_push( $this->paths_ignore , '*/.gitignore' );

		$conf_paths_ignore = preg_split('/\r\n|\r|\n|\,|\;/',$this->px->get_conf('publish.paths_ignore'));
		clearstatcache();
		foreach( $conf_paths_ignore as $row ){
			$row = trim( $row );
			if(!strlen($row)){ continue; }
			$row = preg_replace('/^\/+/','',$row);
			$row_realpath = $this->path_docroot_dir.$row;

			if( preg_match('/\*/',$row) ){
				// ワイルドカードが使われている場合、
				// 対象パスの存在確認を行わない。
			}else{
				$row_realpath = $this->px->dbh()->get_realpath($row_realpath);
			}

			array_push( $this->paths_ignore , $row_realpath );
		}

		// 拡張子別の処理方法の設定をコンフィグから読み込む
		$config_ary = $this->px->get_conf_all();
		foreach( $config_ary as $config_key=>$config_row ){
			if( preg_match('/^publish_extensions\.(.*)$/s', $config_key, $command_matches) ){
				$this->publish_type_extension_map[$command_matches[1]] = $config_row;
			}
		}

		// プラグインによる加工処理(publish API を利用するプラグイン一覧を精査)
		$path_plugin_dir = $this->px->get_conf('paths.px_dir').'plugins/';
		$plugins_list = $this->px->dbh()->ls( $path_plugin_dir );
		foreach( $plugins_list as $tmp_key=>$tmp_plugin_name ){
			if( !is_file( $path_plugin_dir.$tmp_plugin_name.'/register/publish.php' ) ){
				unset($plugins_list[$tmp_key]);
			}
		}
		$this->plugins_list = $plugins_list;

		return true;
	}//setup()

	/**
	 * パブリッシュディレクトリを空っぽにする。
	 * 
	 * @return bool 常に `true` を返します。
	 */
	private function clear_publish_dir(){
		$files = $this->px->dbh()->ls( $this->path_tmppublish_dir.'/htdocs' );
		if( is_array( $files ) ){
			foreach( $files as $filename ){
				$this->clear_publish_dir_rmdir_all( '/'.$filename );
			}
		}
		$this->px->dbh()->rm( $this->path_tmppublish_dir.'/publish_log.txt' );
		$this->px->dbh()->rm( $this->path_tmppublish_dir.'/publish_error_log.txt' );
		$this->px->dbh()->rm( $this->path_tmppublish_dir.'/readme.txt' );
		return true;
	}
	/**
	 * パブリッシュディレクトリ内のパスを中身ごと完全に削除する。
	 * 
	 * @param string $path_original パス
	 * @return bool 成功時 `true`、失敗時 `false` を返します。
	 */
	function clear_publish_dir_rmdir_all( $path_original ){
		$path = $this->path_tmppublish_dir.'/htdocs'.$path_original;
		$path = preg_replace('/^\/+/s','/',$path);
		if( strlen( $this->px->get_conf('system.filesystem_encoding') ) ){
			$path = @t::convert_encoding( $path , $this->px->get_conf('system.filesystem_encoding') );
		}

		if( !$this->px->dbh()->is_writable( $path ) ){
			return false;
		}
		$path = @realpath( $path );
		if( $path === false ){ return false; }
		if( @is_file( $path ) || @is_link( $path ) ){
			#	ファイルまたはシンボリックリンクの場合の処理
			if( !preg_match('/^'.preg_quote($this->path_tmppublish_dir.'htdocs'.$this->path_target,'/').'.*/s',$this->px->dbh()->get_realpath($path)) ){
				return	true;
			}else{
				$result = @unlink( $path );
				print ''.$path_original."\n";
				return	$result;
			}

		}elseif( @is_dir( $path ) ){
			#	ディレクトリの処理
			$flist = $this->px->dbh()->ls( $path );
			foreach ( $flist as $Line ){
				if( $Line == '.' || $Line == '..' ){ continue; }
				$this->clear_publish_dir_rmdir_all( $path_original.'/'.$Line );
			}

			if( !preg_match('/^'.preg_quote($this->path_tmppublish_dir.'htdocs'.$this->path_target,'/').'.*/s',$this->px->dbh()->get_realpath($path)) ){
				return	true;
			}else{
				$result = @rmdir( $path );
				print ''.$path_original."\n";
				return	$result;
			}
		}

		return	false;
	}

	/**
	 * パブリッシュキューを追加する。
	 * 
	 * @param string $path パス
	 * @return bool 成功時 `true`、失敗時 `false` を返します。
	 */
	public function add_queue( $path ){
		// プラグインがアクセスする可能性があるため、public とする。
		if( !preg_match( '/^\//' , $path ) ){
			return false;
		}
		if( !preg_match('/^'.preg_quote($this->path_target,'/').'.*/s',$path) ){
			return false;
		}

		$path = preg_replace('/(?:\?|\#).*$/s','',$path);
		$path = preg_replace('/\/$/s','/'.$this->px->get_directory_index_primary(), $path);

		if( $this->done_items[$path] ){ return true; }
		array_push( $this->queue_items , $path );
		$this->done_items[$path] = true;
		print '[add_queue] '.$path.' - remaining: '.count($this->queue_items).''."\n";
		return true;
	}

	/**
	 * パブリッシュログを出力する。
	 * 
	 * @param array $ary_logtexts ログに出力する諸情報を格納する連想配列
	 * @return bool 成功時 `true`、失敗時 `false` を返します。
	 */
	public function publish_log( $ary_logtexts ){
		// プラグインがアクセスする可能性があるため、public とする。
		$logtext = '';
		$logtext .= date('Y-m-d H:i:s');
		$logtext .= '	'.$ary_logtexts['message'];
		$logtext .= '	'.($ary_logtexts['result']===true?'success':($ary_logtexts['result']===false?'FAILED':$ary_logtexts['result']));
		$logtext .= '	'.$ary_logtexts['path'];

		$path = $this->path_tmppublish_dir.'publish_log.txt';
		$rtn = @error_log( $logtext."\r\n", 3, $path );
		$this->px->dbh()->chmod($path);
		return $rtn;
	}

	/**
	 * パブリッシュエラーログを出力する。
	 * 
	 * @param array $ary_logtexts ログに出力する諸情報を格納する連想配列
	 * @return bool 成功時 `true`、失敗時 `false` を返します。
	 */
	public function publish_error_log( $ary_logtexts ){
		// プラグインがアクセスする可能性があるため、public とする。
		$logtext = '';
		$logtext .= date('Y-m-d H:i:s').'	';
		$logtext .= $ary_logtexts['error'].'	';
		$logtext .= $ary_logtexts['path'];

		$path = $this->path_tmppublish_dir.'publish_error_log.txt';
		$rtn = @error_log( $logtext."\r\n", 3, $path );
		$this->px->dbh()->chmod($path);
		return $rtn;
	}

	/**
	 * ディレクトリを再帰的にスキャンする。
	 * 
	 * @param string $path パス
	 * @return bool 常に `true` を返します。
	 */
	private function scan_dirs( $path ){
		$path = preg_replace('/^\/+/s','/',$path);
		$realpath_target_dir  = $this->px->dbh()->get_realpath( $this->path_docroot_dir.'/'.$path );
		$realpath_tmppublish_dir = $this->px->dbh()->get_realpath( $this->path_tmppublish_dir.'/htdocs/'.$this->px->get_install_path().'/'.$path );

		$items = $this->px->dbh()->ls( $realpath_target_dir );
		foreach( $items as $filename ){
			$current_original_path = $realpath_target_dir.'/'.$filename;
			$filename = preg_replace( '/\.html(?:\.[a-zA-Z0-9]+)?$/si' , '.html' , $filename );
			$current_path = $realpath_target_dir.'/'.$filename;
			$current_publishto = $realpath_tmppublish_dir.'/'.$filename;

			if($this->is_ignore_path($current_original_path)){
				continue;
			}
			$extension = $this->px->dbh()->get_extension( $current_path );

			if( is_dir( $current_path ) ){
				//  対象がディレクトリだったら
				if( preg_match('/^'.preg_quote($this->path_target,'/').'.*/s',$this->px->dbh()->get_realpath($this->px->get_install_path().$path).'/') ){
					$this->px->dbh()->mkdir_all( $current_publishto );
				}
				$this->scan_dirs( $path.'/'.$filename );
			}elseif( is_file( $current_original_path ) ){
				//  対象がファイルだったら
				$tmp_extension = $this->px->dbh()->get_extension( $path.'/'.$filename );
				if( $this->publish_type_extension_map[$tmp_extension] == 'nopublish' ){
					// 'nopublish' 指定のファイルはスキャンしない
					continue;
				}
				$this->add_queue( $this->px->dbh()->get_realpath($this->px->get_install_path().$path.'/'.$filename) );
				unset($tmp_extension);
			}

		}

		return true;
	}//scan_dirs();

	/**
	 * ファイル単体をパブリッシュする。
	 * 
	 * @param string $path パス
	 * @return bool 成功時 `true`、失敗時 `false` を返します。
	 */
	private function publish_file( $path ){
		if( !preg_match( '/^\//' , $path ) ){
			print '[ERROR] '.$path."\n";
			return false;
		}

		$extension = $this->px->dbh()->get_extension( $path );
		$publish_type = $this->get_publish_type_by_extension($extension);
		print '['.$publish_type.'] '.$path;
		switch( $publish_type ){
			case 'http':
				$url = 'http'.($this->px->req()->is_ssl()?'s':'').'://'.$_SERVER['HTTP_HOST'].$this->px->dbh()->get_realpath($path);

				$httpaccess = $this->factory_httpaccess();
				$httpaccess->clear_request_header();//初期化
				$httpaccess->set_url( $url );//ダウンロードするURL
				$httpaccess->set_method( 'GET' );//メソッド
				$httpaccess->set_user_agent( $this->crawler_user_agent );//HTTP_USER_AGENT
				if( strlen( $this->px->get_conf('project.auth_name') ) ){
					//  基本認証、またはダイジェスト認証が設定されている場合
					if( strlen( $this->px->get_conf('project.auth_type') ) ){
						$httpaccess->set_auth_type( $this->px->get_conf('project.auth_type') );//認証タイプ
					}
					$httpaccess->set_auth_user( $this->px->get_conf('project.auth_name') );//認証ID
					$httpaccess->set_auth_pw( $this->px->get_conf('project.auth_password') );//認証パスワード
				}
				$this->px->dbh()->mkdir_all( dirname($this->path_tmppublish_dir.'/htdocs/'.$path) );
				$httpaccess->save_http_contents( $this->path_tmppublish_dir.'/htdocs/'.$path );//ダウンロードを実行する


				$result = $httpaccess->get_status_cd();
				print ' [HTTP Status: '.$result.']'.' - remaining: '.count($this->queue_items).''."\n";

				$relatedlink = $httpaccess->get_response(strtolower('X-PXFW-RELATEDLINK'));
				if( strlen($relatedlink) ){
					foreach( explode(',',$relatedlink) as $row ){
						$this->add_queue($row);
					}
				}

				$this->publish_log( array(
					'result'=>($result),
					'message'=>$publish_type,
					'path'=>$this->px->dbh()->get_realpath($path),
				) );
				if($result!=200){
					$this->publish_error_log( array(
						'error'=>'[ERROR] Publishing HTML was FAILED.(status='.$result.')',
						'path'=>$this->px->dbh()->get_realpath($path),
					) );
				}
				break;
			case 'include_text':
				$tmp_src = $this->px->dbh()->file_get_contents( $_SERVER['DOCUMENT_ROOT'].$path );
				if( strlen($this->px->get_conf('system.output_encoding')) ){
					$tmp_src = t::convert_encoding( $tmp_src , $this->px->get_conf('system.output_encoding'), 'utf-8' );
				}
				if(strlen($this->px->get_conf('system.output_eof_coding'))){
					//出力ソースの改行コード変換
					$eof_code = "\r\n";
					switch( strtolower( $this->px->get_conf('system.output_eof_coding') ) ){
						case 'cr':     $eof_code = "\r"; break;
						case 'lf':     $eof_code = "\n"; break;
						case 'crlf':
						default:       $eof_code = "\r\n"; break;
					}
					$tmp_src = preg_replace('/\r\n|\r|\n/si',$eof_code,$tmp_src);
				}
				$this->px->dbh()->mkdir_all( dirname($this->path_tmppublish_dir.'/htdocs/'.$path) );
				$result = $this->px->dbh()->file_overwrite( $this->path_tmppublish_dir.'/htdocs/'.$path , $tmp_src );
				print ''.' - remaining: '.count($this->queue_items).''."\n";

				$this->publish_log( array(
					'result'=>($result?true:false),
					'message'=>$publish_type.(strlen($this->px->get_conf('system.output_encoding'))?' (and convert encoding)':''),
					'path'=>$this->px->dbh()->get_realpath($path),
				) );
				if(!$result){
					$this->publish_error_log( array(
						'error'=>'[ERROR] Copying file was FAILED.',
						'path'=>$this->px->dbh()->get_realpath($path),
					) );
				}
				break;
			default:
				$this->px->dbh()->mkdir_all( dirname($this->path_tmppublish_dir.'/htdocs/'.$path) );
				$result = $this->px->dbh()->copy( $_SERVER['DOCUMENT_ROOT'].$path , $this->path_tmppublish_dir.'/htdocs/'.$path );
				print ''.' - remaining: '.count($this->queue_items).''."\n";

				$this->publish_log( array(
					'result'=>($result?true:false),
					'message'=>$publish_type,
					'path'=>$this->px->dbh()->get_realpath($path),
				) );
				if(!$result){
					$this->publish_error_log( array(
						'error'=>'[ERROR] Copying file was FAILED.',
						'path'=>$this->px->dbh()->get_realpath($path),
					) );
				}
				break;
		}

		// プラグインによる加工処理
		//   ファイル単位の加工処理 execute() を実施
		foreach( $this->plugins_list as $tmp_key=>$tmp_plugin_name ){
			$tmp_class_name = $this->px->load_px_plugin_class($tmp_plugin_name.'/register/publish.php');
			$plugin_object = new $tmp_class_name($this->px, $this);
			$plugin_object->execute($this->px->dbh()->get_realpath($this->path_tmppublish_dir.'/htdocs/'.$path), $extension, $publish_type);
		}

		return true;
	}

	/**
	 * 拡張子から、パブリッシュの種類を選択する。
	 * 
	 * @param string $extension 拡張子
	 * @return string パブリッシュの種類
	 */
	private function get_publish_type_by_extension( $extension ){
		if( strlen($this->publish_type_extension_map[strtolower($extension)]) ){
			return $this->publish_type_extension_map[strtolower($extension)];
		}
		return 'copy';// <- default "copy"
	}//get_publish_type_by_extension()

	/**
	 * HTTPAccessオブジェクトを生成して返す。
	 * 
	 * @return object HTTPAccessオブジェクト
	 */
	private function factory_httpaccess(){
		@require_once( $this->px->get_conf('paths.px_dir').'libs/PxHTTPAccess/PxHTTPAccess.php' );
		return new PxHTTPAccess();
	}

	/**
	 * 除外ファイルか調べる。
	 *
	 * @param string $path パス
	 * @return bool 除外ファイルの場合 `true`、それ以外の場合に `false` を返します。
	 */
	private function is_ignore_path( $path ){
		$path = $this->px->dbh()->get_realpath( $path );
		if(is_dir($path)){$path .= '/';}

		//if( !file_exists($path) ){ return true; }
		foreach( $this->paths_ignore as $row ){
			if(!is_string($row)){continue;}
			$preg_pattern = preg_quote($this->px->dbh()->get_realpath($row),'/');
			if( preg_match('/\*/',$preg_pattern) ){
				// ワイルドカードが使用されている場合
				$preg_pattern = preg_quote($row,'/');
				$preg_pattern = preg_replace('/'.preg_quote('\*','/').'/','(?:.*?)',$preg_pattern);//ワイルドカードをパターンに反映
				$preg_pattern = $preg_pattern.'$';//前方・後方一致
			}elseif(is_dir($row)){
				$preg_pattern = preg_quote($this->px->dbh()->get_realpath($row).'/','/');
			}elseif(is_file($row)){
				$preg_pattern = preg_quote($this->px->dbh()->get_realpath($row),'/');
			}
			if( preg_match( '/^'.$preg_pattern.'/s' , $path ) ){
				return true;
			}
		}
		return false;
	}//is_ignore_path();

	/**
	 * パブリッシュをロックする。
	 * 
	 * @return bool ロック成功時に `true`、失敗時に `false` を返します。
	 */
	private function lock(){
		$lockfilepath = $this->path_lockfile;
		$timeout_limit = 10;

		if( !@is_dir( dirname( $lockfilepath ) ) ){
			$this->px->dbh()->mkdir_all( dirname( $lockfilepath ) );
		}

		#	PHPのFileStatusCacheをクリア
		clearstatcache();

		$i = 0;
		while( ( @is_file( $lockfilepath ) && @filesize( $lockfilepath ) ) ){
			if( !$this->is_locked() ){
				break;
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
		$src = '';
		$src .= 'ProcessID='.getmypid()."\r\n";
		$src .= date( 'Y-m-d H:i:s' , time() )."\r\n";
		$RTN = $this->px->dbh()->file_overwrite( $lockfilepath , $src );
		return	$RTN;
	}//lock()

	/**
	 * パブリッシュがロックされているか確認する。
	 * 
	 * @return bool ロック中の場合に `true`、それ以外の場合に `false` を返します。
	 */
	private function is_locked(){
		$lockfilepath = $this->path_lockfile;
		$lockfile_expire = 60*30;//有効期限は30分

		if( is_file($lockfilepath) ){
			if( ( time() - filemtime($lockfilepath) ) > $lockfile_expire ){
				#	有効期限を過ぎていたら、ロックは成立する。
				return false;
			}
			return true;
		}
		return false;
	}//is_locked()

	/**
	 * パブリッシュロックを解除する。
	 * 
	 * @return bool ロック解除成功時に `true`、失敗時に `false` を返します。
	 */
	private function unlock(){
		$lockfilepath = $this->path_lockfile;

		#	PHPのFileStatusCacheをクリア
		clearstatcache();

		return unlink( $lockfilepath );
	}//unlock()

	/**
	 * パブリッシュロックファイルの更新日を更新する。
	 * 
	 * @return bool 成功時に `true`、失敗時に `false` を返します。
	 */
	private function touch_lockfile(){
		$lockfilepath = $this->path_lockfile;

		#	PHPのFileStatusCacheをクリア
		clearstatcache();
		if( !is_file( $lockfilepath ) ){
			return false;
		}

		return touch( $lockfilepath );
	}//touch_lockfile()

	/**
	 * その他の内部エラーを記録する。
	 * 
	 * @param string $message エラーメッセージ
	 * @return bool 常に `true` を返します。
	 */
	public function internal_error_log($message){
		// プラグインがアクセスする可能性があるため、public とする。
		array_push($this->internal_errors, array('message'=>$message));
		return true;
	}

	/**
	 * その他の内部エラーを取得する。
	 * 
	 * @return array 内部エラーを格納した配列
	 */
	private function get_internal_error_log(){
		return $this->internal_errors;
	}

}

?>