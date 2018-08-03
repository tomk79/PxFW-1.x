<?php

/**
 * プロジェクト管理機能
 * Copyright (C)Tomoya Koyanagi.
 */
class pxplugin_asazuke_admin{

	private $px;
	private $pcconf;
	private $cmd;

	private $local_sitemap = array();// ページ名等を定義する
	private $title = null; // 出力するページタイトル文字列

	/**
	 * コンストラクタ
	 */
	public function __construct( &$px, &$pcconf, $cmd ){
		$this->px = &$px;
		$this->pcconf = &$pcconf;
		$this->cmd = &$cmd;

		$this->set_sitemap();
	}

	/**
	 * config:設定値を取得
	 */
	public function get_conf( $key ){
		return	$this->pcconf->get_value( $key );
	}

	/**
	 * config:値を設定
	 */
	public function set_conf( $key , $val ){
		return	$this->pcconf->set_value( $key , $val );
	}

	/**
	 * ページタイトルを取得する
	 */
	public function get_page_title(){
		return $this->title;
	}

	/**
	 * 処理の開始
	 */
	public function start(){
		$cont_src = $this->start_controller();

		$title = @$this->local_sitemap[':'.implode('.', $this->cmd)]['title'];
		if( strlen( $title ) ){
			$this->title = $title;
		}

		$rtn = '';
		$rtn .= $cont_src."\n";
		return $rtn;
	}
	/**
	 * コントローラ
	 */
	private function start_controller(){
		if( @$this->cmd[0] == 'edit_proj' ){
			#	プロジェクト作成/編集
			return	$this->start_edit_proj();
		}elseif( @$this->cmd[0] == 'execute_program' ){
			#	プログラムを実行
			return	$this->start_execute_program();
		}elseif( @$this->cmd[0] == 'delete_program_content' ){
			#	プログラムが書き出したコンテンツを削除する
			return	$this->start_delete_program_content();
		}elseif( @$this->cmd[0] == 'configcheck' ){
			#	設定項目の確認
			return	$this->page_configcheck();
		}
		return	$this->page_start();
	}


	/**
	 * コンテンツ内へのリンク先を調整する。
	 */
	private function href( $linkto = null ){
		if(is_null($linkto)){
			return '?PX=plugins.asazuke.'.implode('.',$this->cmd);
		}
		if($linkto == ':'){
			return '?PX=plugins.asazuke';
		}
		$rtn = preg_replace('/^\:/','?PX=plugins.asazuke.',$linkto);

		$rtn = $this->px->theme()->href( $rtn );
		return $rtn;
	}

	/**
	 * コンテンツ内へのリンクを生成する。
	 */
	private function mk_link( $linkto , $options = array() ){
		if( !strlen($options['label']) ){
			if( $this->local_sitemap[$linkto] ){
				$options['label'] = @$this->local_sitemap[$linkto]['title'];
			}
		}
		$rtn = $this->href($linkto);

		$rtn = $this->px->theme()->mk_link( $rtn , $options );
		return $rtn;
	}

	/**
	 * フォームエレメントの遷移情報を生成する
	 */
	private function mk_form_defvalues($linkto = null){
		if(is_null($linkto)){
			return 'plugins.asazuke.'.implode('.',$this->cmd);
		}
		if($linkto == ':'){
			return 'plugins.asazuke';
		}
		$rtn = preg_replace('/^\:/','plugins.asazuke.',$linkto);
		$rtn = '<input type="hidden" name="PX" value="'.htmlspecialchars($rtn).'" />';
		return $rtn;
	}

	/**
	 * 見出しタグを生成する。
	 */
	private function mk_hx($label, $hx = '2'){
		return '<h'.$hx.'>'.t::h($label).'</h'.$hx.'>';
	}

	/**
	 * このコンテンツ内でのサイトマップを登録する
	 */
	private function set_sitemap(){

		$this->local_sitemap[ ':'                                                 ] = array( 'title'=>'ASAZUKE'                            );
		$this->local_sitemap[ ':configcheck'                                      ] = array( 'title'=>'設定の確認'                         );
		$this->local_sitemap[ ':edit_proj.'.@$this->cmd[1]                         ] = array( 'title'=>'プロジェクト編集'                   );
		$this->local_sitemap[ ':execute_program.'.@$this->cmd[1].'.'.@$this->cmd[2] ] = array( 'title'=>'プログラム実行'                     );
		$this->local_sitemap[ ':delete_program_content.'.@$this->cmd[1]            ] = array( 'title'=>'プログラムコンテンツの削除'         );

		return true;
	}


	/**
	 * スタートページ
	 */
	private function page_start(){

		$project_model = $this->pcconf->factory_model_project();
		$project_model->load_project();

		$path_project_home_dir = $project_model->get_project_home_dir();

		// $this->local_sitemap[ ':'.implode('.',$this->cmd) ] = array( 'title'=>'プロジェクト『'.htmlspecialchars( $project_model->get_project_name() ).'』の詳細情報' );

		$RTN = '';

		#======================================
		$RTN .= ''.$this->mk_hx( '基本情報' ).''."\n";
		$RTN .= '<table class="def" style="width:100%;">'."\n";
		$RTN .= '	<tr>'."\n";
		$RTN .= '		<th style="width:30%;"><div>ドキュメントルートのパス</div></th>'."\n";
		$RTN .= '		<td style="width:70%;"><div style="word-break:break-all;">'.htmlspecialchars( $project_model->get_path_docroot() ).'</div></td>'."\n";
		$RTN .= '	</tr>'."\n";
		$RTN .= '	<tr>'."\n";
		$RTN .= '		<th style="width:30%;"><div>スタートページのパス</div></th>'."\n";
		$RTN .= '		<td style="width:70%;"><div style="word-break:break-all;">'.htmlspecialchars( $project_model->get_path_startpage() ).'</div></td>'."\n";
		$RTN .= '	</tr>'."\n";
		$RTN .= '	<tr>'."\n";
		$RTN .= '		<th style="width:30%;"><div>許容するオリジナルファイルの最大サイズ</div></th>'."\n";
		$RTN .= '		<td style="width:70%;"><div style="word-break:break-all;">'.htmlspecialchars( $project_model->get_accept_html_file_max_size() ).'</div></td>'."\n";
		$RTN .= '	</tr>'."\n";
		$RTN .= '</table>'."\n";
		$RTN .= '<form action="'.htmlspecialchars( $this->href( ':edit_proj' ) ).'" method="post">'."\n";
		$RTN .= '	<p class="center"><input type="submit" value="基本情報を編集する" /></p>'."\n";
		$RTN .= '</form>'."\n";

		$RTN .= ''.$this->mk_hx( 'メインコンテンツエリアのセレクタ' ).''."\n";
		$list = $project_model->get_select_cont_main();
		$RTN .= '<table class="def" style="width:100%;">'."\n";
		$RTN .= '	<thead>'."\n";
		$RTN .= '	<tr>'."\n";
		$RTN .= '		<th><div>name</div></th>'."\n";
		$RTN .= '		<th><div>selector</div></th>'."\n";
		$RTN .= '		<th><div>index</div></th>'."\n";
		$RTN .= '	</tr>'."\n";
		$RTN .= '	</thead>'."\n";
		foreach( $list as $row ){
			$RTN .= '	<tr>'."\n";
			$RTN .= '		<th><div>'.t::h($row['name']).'</div></th>'."\n";
			$RTN .= '		<td><div>'.t::h($row['selector']).'</div></td>'."\n";
			$RTN .= '		<td><div>'.t::h($row['index']).'</div></td>'."\n";
			$RTN .= '	</tr>'."\n";
		}
		$RTN .= '</table>'."\n";
		$RTN .= '<ul>'."\n";
		$RTN .= '	<li style="word-break:break-all;">'.t::h( $path_project_home_dir.'/select_cont_main.csv' ).'</li>'."\n";
		$RTN .= '</ul>'."\n";

		$RTN .= ''.$this->mk_hx( 'サブコンテンツエリアのセレクタ' ).''."\n";
		$list = $project_model->get_select_cont_subs();
		$RTN .= '<table class="def" style="width:100%;">'."\n";
		$RTN .= '	<thead>'."\n";
		$RTN .= '	<tr>'."\n";
		$RTN .= '		<th><div>name</div></th>'."\n";
		$RTN .= '		<th><div>selector</div></th>'."\n";
		$RTN .= '		<th><div>index</div></th>'."\n";
		$RTN .= '		<th><div>cabinet_name</div></th>'."\n";
		$RTN .= '	</tr>'."\n";
		$RTN .= '	</thead>'."\n";
		foreach( $list as $row ){
			$RTN .= '	<tr>'."\n";
			$RTN .= '		<th><div>'.t::h($row['name']).'</div></th>'."\n";
			$RTN .= '		<td><div>'.t::h($row['selector']).'</div></td>'."\n";
			$RTN .= '		<td><div>'.t::h($row['index']).'</div></td>'."\n";
			$RTN .= '		<td><div>'.t::h($row['cabinet_name']).'</div></td>'."\n";
			$RTN .= '	</tr>'."\n";
		}
		$RTN .= '</table>'."\n";
		$RTN .= '<ul>'."\n";
		$RTN .= '	<li style="word-break:break-all;">'.t::h( $path_project_home_dir.'/select_cont_subs.csv' ).'</li>'."\n";
		$RTN .= '</ul>'."\n";

		$RTN .= ''.$this->mk_hx( 'DOM変換ルール' ).''."\n";
		$list = $project_model->get_dom_convert();
		$RTN .= '<table class="def" style="width:100%;">'."\n";
		$RTN .= '	<thead>'."\n";
		$RTN .= '	<tr>'."\n";
		$RTN .= '		<th><div>name</div></th>'."\n";
		$RTN .= '		<th><div>selector</div></th>'."\n";
		$RTN .= '		<th><div>replace_to</div></th>'."\n";
		$RTN .= '	</tr>'."\n";
		$RTN .= '	</thead>'."\n";
		foreach( $list as $row ){
			$RTN .= '	<tr>'."\n";
			$RTN .= '		<th><div>'.t::h($row['name']).'</div></th>'."\n";
			$RTN .= '		<td><div>'.t::h($row['selector']).'</div></td>'."\n";
			$RTN .= '		<td><div>'.t::h($row['replace_to']).'</div></td>'."\n";
			$RTN .= '	</tr>'."\n";
		}
		$RTN .= '</table>'."\n";
		$RTN .= '<ul>'."\n";
		$RTN .= '	<li style="word-break:break-all;">'.t::h( $path_project_home_dir.'/dom_convert.csv' ).'</li>'."\n";
		$RTN .= '</ul>'."\n";

		$RTN .= ''.$this->mk_hx( 'パンくずエリアセレクタ' ).''."\n";
		$list = $project_model->get_select_breadcrumb();
		$RTN .= '<table class="def" style="width:100%;">'."\n";
		$RTN .= '	<thead>'."\n";
		$RTN .= '	<tr>'."\n";
		$RTN .= '		<th><div>name</div></th>'."\n";
		$RTN .= '		<th><div>selector</div></th>'."\n";
		$RTN .= '		<th><div>index</div></th>'."\n";
		$RTN .= '	</tr>'."\n";
		$RTN .= '	</thead>'."\n";
		foreach( $list as $row ){
			$RTN .= '	<tr>'."\n";
			$RTN .= '		<th><div>'.t::h($row['name']).'</div></th>'."\n";
			$RTN .= '		<td><div>'.t::h($row['selector']).'</div></td>'."\n";
			$RTN .= '		<td><div>'.t::h($row['index']).'</div></td>'."\n";
			$RTN .= '	</tr>'."\n";
		}
		$RTN .= '</table>'."\n";
		$RTN .= '<ul>'."\n";
		$RTN .= '	<li style="word-break:break-all;">'.t::h( $path_project_home_dir.'/select_breadcrumb.csv' ).'</li>'."\n";
		$RTN .= '</ul>'."\n";

		$RTN .= ''.$this->mk_hx( 'タイトルの置換ルール' ).''."\n";
		$list = $project_model->get_replace_title();
		$RTN .= '<table class="def" style="width:100%;">'."\n";
		$RTN .= '	<thead>'."\n";
		$RTN .= '	<tr>'."\n";
		$RTN .= '		<th><div>name</div></th>'."\n";
		$RTN .= '		<th><div>preg_pattern</div></th>'."\n";
		$RTN .= '		<th><div>replace_to</div></th>'."\n";
		$RTN .= '	</tr>'."\n";
		$RTN .= '	</thead>'."\n";
		foreach( $list as $row ){
			$RTN .= '	<tr>'."\n";
			$RTN .= '		<th><div>'.t::h($row['name']).'</div></th>'."\n";
			$RTN .= '		<td><div>'.t::h($row['preg_pattern']).'</div></td>'."\n";
			$RTN .= '		<td><div>'.t::h($row['replace_to']).'</div></td>'."\n";
			$RTN .= '	</tr>'."\n";
		}
		$RTN .= '</table>'."\n";
		$RTN .= '<ul>'."\n";
		$RTN .= '	<li style="word-break:break-all;">'.t::h( $path_project_home_dir.'/replace_title.csv' ).'</li>'."\n";
		$RTN .= '</ul>'."\n";

		$RTN .= ''.$this->mk_hx( '文字列置換ルール' ).''."\n";
		$list = $project_model->get_replace_strings();
		$RTN .= '<table class="def" style="width:100%;">'."\n";
		$RTN .= '	<thead>'."\n";
		$RTN .= '	<tr>'."\n";
		$RTN .= '		<th><div>name</div></th>'."\n";
		$RTN .= '		<th><div>preg_pattern</div></th>'."\n";
		$RTN .= '		<th><div>replace_to</div></th>'."\n";
		$RTN .= '	</tr>'."\n";
		$RTN .= '	</thead>'."\n";
		foreach( $list as $row ){
			$RTN .= '	<tr>'."\n";
			$RTN .= '		<th><div>'.t::h($row['name']).'</div></th>'."\n";
			$RTN .= '		<td><div>'.t::h($row['preg_pattern']).'</div></td>'."\n";
			$RTN .= '		<td><div>'.t::h($row['replace_to']).'</div></td>'."\n";
			$RTN .= '	</tr>'."\n";
		}
		$RTN .= '</table>'."\n";
		$RTN .= '<ul>'."\n";
		$RTN .= '	<li style="word-break:break-all;">'.t::h( $path_project_home_dir.'/replace_strings.csv' ).'</li>'."\n";
		$RTN .= '</ul>'."\n";

		$RTN .= ''.$this->mk_hx( '除外共通リソース設定' ).''."\n";
		$list = $project_model->get_ignore_common_resources();
		$RTN .= '<table class="def" style="width:100%;">'."\n";
		$RTN .= '	<thead>'."\n";
		$RTN .= '	<tr>'."\n";
		$RTN .= '		<th><div>name</div></th>'."\n";
		$RTN .= '		<th><div>path</div></th>'."\n";
		$RTN .= '	</tr>'."\n";
		$RTN .= '	</thead>'."\n";
		foreach( $list as $row ){
			$RTN .= '	<tr>'."\n";
			$RTN .= '		<th><div>'.t::h($row['name']).'</div></th>'."\n";
			$RTN .= '		<td><div>'.t::h($row['path']).'</div></td>'."\n";
			$RTN .= '	</tr>'."\n";
		}
		$RTN .= '</table>'."\n";
		$RTN .= '<ul>'."\n";
		$RTN .= '	<li style="word-break:break-all;">'.t::h( $path_project_home_dir.'/ignore_common_resources.csv' ).'</li>'."\n";
		$RTN .= '</ul>'."\n";

		#======================================
		$program_model = $project_model->factory_program();

		$RTN .= '<div class="unit cols">'."\n";
		$RTN .= '	<div class="cols-col cols-2of3"><div class="cols-pad">'."\n";

		$RTN .= ''.$this->mk_hx( '書き出しを実行する' ).''."\n";

		if( $this->px->dbh()->is_unix() ){
			#--------------------------------------
			#	UNIXの場合→コマンドラインでの実行方法を案内。
			$RTN .= '<p>'."\n";
			$RTN .= '	この操作は、次のコマンドラインからも実行することができます。<br />'."\n";
			$RTN .= '</p>'."\n";
			$RTN .= '<div class="unit">'."\n";
			$RTN .= '	<div class="code"><pre><code>'.htmlspecialchars( ''.escapeshellcmd( $this->pcconf->get_path_command('php') ).' '.escapeshellarg( realpath( './_px_execute.php' ) ).' '.escapeshellarg( 'PX=plugins.asazuke.run&output_encoding='.urlencode('UTF-8').'' ) ).'</code></pre></div>'."\n";
			$RTN .= '</div>'."\n";
			$RTN .= ''."\n";

			$RTN .= '<p>'."\n";
			$RTN .= '	このコマンドを、ウェブから起動するには、次の「書き出しを実行する」ボタンをクリックします。<br />'."\n";
			$RTN .= '</p>'."\n";
		}else{
			#--------------------------------------
			#	Windowsの場合→コマンドラインで実行できない・・・。
			$RTN .= '<p>'."\n";
			$RTN .= '	書き出しを実行するには、次の「書き出しを実行する」ボタンをクリックしてください。<br />'."\n";
			$RTN .= '</p>'."\n";
		}

		$RTN .= '<form action="'.htmlspecialchars( $this->href( ':run' ) ).'" method="post" target="_blank">'."\n";
		$RTN .= '	<p class="center"><input type="submit" value="書き出しを実行する" /></p>'."\n";
		$RTN .= '</form>'."\n";


		$RTN .= '	</div></div>'."\n";
		$RTN .= '	<div class="cols-col cols-1of3 cols-last"><div class="cols-pad">'."\n";

		$RTN .= $this->mk_hx('書き出したデータのダウンロード')."\n";
		$is_zip = array();
		if( class_exists( 'ZipArchive' ) ){
			$is_zip['zip'] = true;
		}
		if( strlen( $this->pcconf->get_path_command('tar') ) ){
			$is_zip['tgz'] = true;
		}
		if( count( $is_zip ) ){
			#	tarコマンドが使えたら(UNIXのみ)
			$RTN .= '<p>'."\n";
			$RTN .= '	書き出したデータを';
			$RTN .= implode( ', ' , array_keys( $is_zip ) );
			$RTN .= '形式でダウンロードすることができます。';
			$RTN .= '<br />'."\n";
			$RTN .= '</p>'."\n";
			$RTN .= '<ul class="none">'."\n";
			foreach( array_keys( $is_zip ) as $type ){
				$RTN .= '	<li>'.$this->mk_link( ':execute_program'.'&mode=download&ext='.strtolower($type) , array('label'=>strtoupper($type).'形式でダウンロード','active'=>false,'style'=>'inside') ).'</li>'."\n";
			}
			$RTN .= '</ul>'."\n";
		}else{
			#	圧縮解凍系機能が利用できなかったら
			$RTN .= '<p>'."\n";
			$RTN .= '	<span class="error">tarコマンドのパスがセットされていません</span>。<code>$conf->path_commands[\'tar\']</code>に、tarコマンドのパスを設定してください。<br />'."\n";
			$RTN .= '</p>'."\n";
		}

		$RTN .= $this->mk_hx('書き出したデータの削除')."\n";
		$RTN .= '<p>'."\n";
		$RTN .= '	書き出したデータを削除します。<br />'."\n";
		$RTN .= '</p>'."\n";
		$RTN .= '<ul class="none">'."\n";
		$RTN .= '	<li>'.$this->mk_link( ':delete_program_content' , array('label'=>'削除する','active'=>false,'style'=>'inside') ).'</li>'."\n";
		$RTN .= '</ul>'."\n";

		$RTN .= '	</div></div>'."\n";
		$RTN .= '</div>'."\n";

		return	$RTN;
	}



	// ------------------------------------------------------------------------------------------------------------------

	/**
	 * 新規プロジェクト作成/編集
	 */
	private function start_edit_proj(){
		$error = $this->check_edit_proj_check();
		if( $this->px->req()->get_param('mode') == 'thanks' ){
			return	$this->page_edit_proj_thanks();
		}elseif( $this->px->req()->get_param('mode') == 'confirm' && !count( $error ) ){
			return	$this->page_edit_proj_confirm();
		}elseif( $this->px->req()->get_param('mode') == 'execute' && !count( $error ) ){
			return	$this->execute_edit_proj_execute();
		}elseif( !strlen( $this->px->req()->get_param('mode') ) ){
			$error = array();

			$project_model = &$this->pcconf->factory_model_project();
			$project_model->load_project();
			$this->px->req()->set_param( 'path_stargpage' , $project_model->get_path_startpage() );
			$this->px->req()->set_param( 'path_docroot' , $project_model->get_path_docroot() );
			$this->px->req()->set_param( 'accept_html_file_max_size' , $project_model->get_accept_html_file_max_size() );
		}
		return	$this->page_edit_proj_input( $error );
	}
	/**
	 * 新規プロジェクト作成/編集：入力
	 */
	private function page_edit_proj_input( $error ){
		$RTN = ''."\n";

		$path_pxc_home_dir = $this->px->dbh()->get_realpath( $this->px->get_conf('paths.px_dir') ).'/_sys/ramdata/plugins/PicklesCrawler/proj/';

		$RTN .= '<p>'."\n";
		$RTN .= '	プロジェクトの情報を入力して、「確認する」ボタンをクリックしてください。<span class="must">必須</span>印の項目は必ず入力してください。<br />'."\n";
		$RTN .= '</p>'."\n";
		if( is_array( $error ) && count( $error ) ){
			$RTN .= '<p class="error">'."\n";
			$RTN .= '	入力エラーを検出しました。画面の指示に従って修正してください。<br />'."\n";
			$RTN .= '</p>'."\n";
		}
		$RTN .= '<form action="'.htmlspecialchars( $this->href() ).'" method="post">'."\n";
		$RTN .= '<table style="width:100%;" class="form_elements">'."\n";
		$RTN .= '	<tr>'."\n";
		$RTN .= '		<th style="width:30%;"><div>ドキュメントルートのパス <span class="must">必須</span></div></th>'."\n";
		$RTN .= '		<td style="width:70%;">'."\n";
		$RTN .= '			<div><input type="text" name="path_docroot" value="'.htmlspecialchars( $this->px->req()->get_param('path_docroot') ).'" style="width:80%;" /></div>'."\n";
		if( strlen( @$error['path_docroot'] ) ){
			$RTN .= '			<div class="error">'.$error['path_docroot'].'</div>'."\n";
		}
		if( is_dir($path_pxc_home_dir) ){
			$pxc_projList = $this->px->dbh()->ls($path_pxc_home_dir);
			// test::var_dump($pxc_projList);
			$RTN .= '		<p class="small">PicklesCrawler のプロジェクトから選択</p>'."\n";
			$RTN .= '		<ul class="small">'."\n";
			foreach( $pxc_projList as $pxc_project_id ){
				$pxc_prgList = $this->px->dbh()->ls($path_pxc_home_dir.'/'.$pxc_project_id.'/prg/');
				// test::var_dump($pxc_prgList);
				foreach( $pxc_prgList as $pxc_prg_id ){
					$tmp_path = $path_pxc_home_dir.$pxc_project_id.'/prg/'.$pxc_prg_id.'/dl/';
					foreach( $this->px->dbh()->ls($tmp_path) as $basename ){
						if( $basename == '__LOGS__' ){ continue; }
						if( is_dir( $tmp_path.$basename.'/' ) ){
							$tmp_path .= $basename.'/';
							foreach( $this->px->dbh()->ls($tmp_path) as $basename ){
								if( is_dir( $tmp_path.$basename.'/' ) ){
									$tmp_path .= $basename.'/';
									break;
								}
							}
							break;
						}
					}
					$RTN .= '			<li><a href="javascript:$(\'input[name=path_docroot]\').val('.t::h( t::data2jssrc( $tmp_path ) ).');">'.t::h( $pxc_project_id ).' / '.t::h( $pxc_prg_id ).'</a></li>'."\n";
				}
			}
			$RTN .= '		</ul>'."\n";
		}
		$RTN .= '		</td>'."\n";
		$RTN .= '	</tr>'."\n";
		$RTN .= '	<tr>'."\n";
		$RTN .= '		<th style="width:30%;"><div>スタートページのパス <span class="must">必須</span></div></th>'."\n";
		$RTN .= '		<td style="width:70%;">'."\n";
		$RTN .= '			<div><input type="text" name="path_stargpage" value="'.htmlspecialchars( $this->px->req()->get_param('path_stargpage') ).'" style="width:80%;" /></div>'."\n";
		if( strlen( @$error['path_stargpage'] ) ){
			$RTN .= '			<div class="error">'.$error['path_stargpage'].'</div>'."\n";
		}
		$RTN .= '		</td>'."\n";
		$RTN .= '	</tr>'."\n";
		$RTN .= '	<tr>'."\n";
		$RTN .= '		<th style="width:30%;"><div>許容するオリジナルファイルの最大サイズ</div></th>'."\n";
		$RTN .= '		<td style="width:70%;">'."\n";
		$RTN .= '			<div><input type="text" name="accept_html_file_max_size" value="'.htmlspecialchars( $this->px->req()->get_param('accept_html_file_max_size') ).'" style="width:80%;" /></div>'."\n";
		if( strlen( @$error['accept_html_file_max_size'] ) ){
			$RTN .= '			<div class="error">'.$error['accept_html_file_max_size'].'</div>'."\n";
		}
		$RTN .= '		</td>'."\n";
		$RTN .= '	</tr>'."\n";
		$RTN .= '</table>'."\n";
		$RTN .= '	<div class="center"><input type="submit" value="確認する" /></div>'."\n";
		$RTN .= '	<input type="hidden" name="mode" value="confirm" />'."\n";
		$RTN .= '</form>'."\n";
		return	$RTN;
	}
	/**
	 * 新規プロジェクト作成/編集：確認
	 */
	private function page_edit_proj_confirm(){
		$RTN = ''."\n";
		$HIDDEN = ''."\n";

		$RTN .= '<p>'."\n";
		$RTN .= '	入力した内容を確認してください。<br />'."\n";
		$RTN .= '</p>'."\n";

		$RTN .= '<table style="width:100%;" class="form_elements">'."\n";
		$RTN .= '	<tr>'."\n";
		$RTN .= '		<th style="width:30%;"><div>ドキュメントルートのパス</div></th>'."\n";
		$RTN .= '		<td style="width:70%;">'."\n";
		$RTN .= '			<div style="overflow:hidden; max-width:300px;">'.htmlspecialchars( $this->px->req()->get_param('path_docroot') ).'</div>'."\n";
		$HIDDEN .= '<input type="hidden" name="path_docroot" value="'.htmlspecialchars( $this->px->req()->get_param('path_docroot') ).'" />';
		$RTN .= '		</td>'."\n";
		$RTN .= '	</tr>'."\n";
		$RTN .= '	<tr>'."\n";
		$RTN .= '		<th style="width:30%;"><div>スタートページのパス</div></th>'."\n";
		$RTN .= '		<td style="width:70%;">'."\n";
		$RTN .= '			<div>'.htmlspecialchars( $this->px->req()->get_param('path_stargpage') ).'</div>'."\n";
		$HIDDEN .= '<input type="hidden" name="path_stargpage" value="'.htmlspecialchars( $this->px->req()->get_param('path_stargpage') ).'" />';
		$RTN .= '		</td>'."\n";
		$RTN .= '	</tr>'."\n";
		$RTN .= '	<tr>'."\n";
		$RTN .= '		<th style="width:30%;"><div>許容するオリジナルファイルの最大サイズ</div></th>'."\n";
		$RTN .= '		<td style="width:70%;">'."\n";
		$RTN .= '			<div>'.htmlspecialchars( $this->px->req()->get_param('accept_html_file_max_size') ).'</div>'."\n";
		$HIDDEN .= '<input type="hidden" name="accept_html_file_max_size" value="'.htmlspecialchars( $this->px->req()->get_param('accept_html_file_max_size') ).'" />';
		$RTN .= '		</td>'."\n";
		$RTN .= '	</tr>'."\n";
		$RTN .= '</table>'."\n";

		$RTN .= '<div class="unit">'."\n";
		$RTN .= '<div class="center">'."\n";
		$RTN .= '<form action="'.htmlspecialchars( $this->href() ).'" method="post">'."\n";
		$RTN .= '	<input type="hidden" name="mode" value="execute" />'."\n";
		$RTN .= $HIDDEN;
		$RTN .= '	'.''."\n";
		$RTN .= '	<input type="submit" value="保存する" />'."\n";
		$RTN .= '</form>'."\n";
		$RTN .= '<form action="'.htmlspecialchars( $this->href() ).'" method="post">'."\n";
		$RTN .= '	<input type="hidden" name="mode" value="input" />'."\n";
		$RTN .= $HIDDEN;
		$RTN .= '	'.''."\n";
		$RTN .= '	<input type="submit" value="訂正する" />'."\n";
		$RTN .= '</form>'."\n";
		$RTN .= '</div>'."\n";
		$RTN .= '</div>'."\n";
		$RTN .= '<hr />'."\n";
		$RTN .= '<div class="unit">'."\n";
		$RTN .= '<form action="'.htmlspecialchars( $this->href(':') ).'" method="post">'."\n";
		$RTN .= '	<div class="center"><input type="submit" value="キャンセル" /></div>'."\n";
		$RTN .= '</form>'."\n";
		$RTN .= '</div>'."\n";
		return	$RTN;
	}
	/**
	 * 新規プロジェクト作成/編集：チェック
	 */
	private function check_edit_proj_check(){
		$RTN = array();

		if( strlen($this->px->req()->get_param('path_docroot')) ){
			$tmp_val = $this->px->req()->get_param('path_docroot');
			$tmp_val = preg_replace('/\\\\/','/',$tmp_val);//バックスラッシュをスラッシュに置換
			$tmp_val = preg_replace('/^[a-zA-Z]\:\//s','/',$tmp_val);//ボリュームラベルを削除
			$this->px->req()->set_param('path_docroot', $tmp_val);
		}
		if( !strlen( $this->px->req()->get_param('path_docroot') ) ){
			$RTN['path_docroot'] = 'ドキュメントルートのパスは必須項目です。';
		}elseif( preg_match( '/\r\n|\r|\n/' , $this->px->req()->get_param('path_docroot') ) ){
			$RTN['path_docroot'] = 'ドキュメントルートのパスはに改行を含めることはできません。';
		}elseif( !$this->px->dbh()->is_dir( $this->px->req()->get_param('path_docroot') ) ){
			$RTN['path_docroot'] = 'ドキュメントルートのパスのディレクトリが存在しません。';
		}

		if( strlen($this->px->req()->get_param('path_stargpage')) ){
			$tmp_val = $this->px->req()->get_param('path_stargpage');
			$tmp_val = preg_replace('/\\\\/','/',$tmp_val);//バックスラッシュをスラッシュに置換
			$tmp_val = preg_replace('/^[a-zA-Z]\:\//s','/',$tmp_val);//ボリュームラベルを削除
			$tmp_val = preg_replace('/\/index\.html$/s','/',$tmp_val);//ファイル名 index.html を省略
			$this->px->req()->set_param('path_stargpage', $tmp_val);
		}
		if( !strlen( $this->px->req()->get_param('path_stargpage') ) ){
			$RTN['path_stargpage'] = 'スタートページのパスは必須項目です。';
		}elseif( preg_match( '/\r\n|\r|\n/' , $this->px->req()->get_param('path_stargpage') ) ){
			$RTN['path_stargpage'] = 'スタートページのパスはに改行を含めることはできません。';
		}elseif( !$this->px->dbh()->is_file( $this->px->req()->get_param('path_docroot').'/'.$this->px->req()->get_param('path_stargpage') ) && !$this->px->dbh()->is_file( $this->px->req()->get_param('path_docroot').'/'.$this->px->req()->get_param('path_stargpage').'/index.html' ) ){
			$RTN['path_stargpage'] = 'スタートページのパスのファイルが存在しません。';
		}

		if( !strlen( $this->px->req()->get_param('accept_html_file_max_size') ) ){
			// 省略可
		}elseif( !preg_match('/^[1-9][0-9]*$/s', $this->px->req()->get_param('accept_html_file_max_size')) ){
			$RTN['accept_html_file_max_size'] = '数値で指定してください。';
		}

		return	$RTN;
	}
	/**
	 * 新規プロジェクト作成/編集：実行
	 */
	private function execute_edit_proj_execute(){

		$project_model = &$this->pcconf->factory_model_project();

		#	既存プロジェクトの編集
		$project_model->load_project();

		$project_model->set_path_startpage( $this->px->req()->get_param('path_stargpage') );
		$project_model->set_path_docroot( $this->px->req()->get_param('path_docroot') );
		$project_model->set_accept_html_file_max_size( $this->px->req()->get_param('accept_html_file_max_size') );

		#	出来上がったプロジェクトを保存
		if( !$project_model->save_project() ){
			return	'<p class="error">プロジェクトの保存に失敗しました。</p>';
		}

		return $this->px->redirect( $this->href().'&mode=thanks' );
	}
	/**
	 * 新規プロジェクト作成/編集：完了
	 */
	private function page_edit_proj_thanks(){
		$RTN = ''."\n";
		$RTN .= '<p>プロジェクト編集処理を完了しました。</p>'."\n";
		$backTo = ':';
		$RTN .= '<form action="'.htmlspecialchars( $this->href( $backTo ) ).'" method="post">'."\n";
		$RTN .= '	<p><input type="submit" value="戻る" /></p>'."\n";
		$RTN .= '</form>'."\n";
		return	$RTN;
	}





	###################################################################################################################

	/**
	 * プログラムを実行
	 */
	private function start_execute_program(){
		if( $this->px->req()->get_param('mode') == 'download' ){
			#	ダウンロードする場合
			return	$this->download_program_content();
		}

		$project_model = &$this->pcconf->factory_model_project();
		$project_model->load_project();
		$program_model = &$project_model->factory_program();

		$exec_page_id = ':run';

		$RTN = '';
		$RTN .= '<form action="'.htmlspecialchars( $this->href( ':' ) ).'" method="post">'."\n";
		$RTN .= '	<p class="center"><input type="submit" value="戻る" /></p>'."\n";
		$RTN .= '	'.$this->mk_form_defvalues( ':detail.'.@$this->cmd[1] )."\n";
		$RTN .= '</form>'."\n";
		return	$RTN;
	}//start_execute_program()


	/**
	 * プログラムが書き出したコンテンツのダウンロード
	 */
	private function download_program_content(){
		$download_content_path = $this->pcconf->get_program_home_dir( @$this->cmd[1] , @$this->cmd[2] ).'/dl';
		$download_zipto_path = dirname($download_content_path).'/tmp_download_content';
		if( !is_dir( $download_content_path ) ){
			return	'<p class="error">ディレクトリが存在しません。</p>';
		}

		if( $this->px->req()->get_param('ext') == 'tgz' && strlen( $this->pcconf->get_path_command('tar') ) ){
			#	tarコマンドが使えたら(UNIXのみ)
			$className = $this->px->load_px_plugin_class( '/asazuke/resources/tgz.php' );
			if( !$className ){
				$this->px->error()->error_log( 'tgzライブラリのロードに失敗しました。' , __FILE__ , __LINE__ );
				return	'<p class="error">tgzライブラリのロードに失敗しました。</p>';
			}
			$obj_tgz = new $className( $this->px , $this->pcconf->get_path_command('tar') );

			if( !$obj_tgz->zip( $download_content_path , $download_zipto_path.'.tgz' ) ){
				return	'<p class="error">圧縮に失敗しました。</p>';
			}

			if( !is_file( $download_zipto_path.'.tgz' ) ){
				return	'<p class="error">圧縮されたアーカイブファイルは現在は存在しません。</p>';
			}

			$dl_filename = @$this->cmd[1].'_'.@$this->cmd[2].'.tgz';
			if( $this->pcconf->get_value('dl_datetime_in_filename') ){
				$CONTENT = $this->px->dbh()->file_get_contents( $download_content_path.'/__LOGS__/datetime.txt' );
				list( $start_datetime , $end_datetime ) = explode(' --- ',$CONTENT);
				if( !strlen( $end_datetime ) ){
					$end_datetime = date('Y-m-d H:i:s');
				}
				$dl_filename = 'PxFW_asazuke_'.date('Ymd_Hi',$this->px->dbh()->datetime2int($end_datetime)).'.tgz';
			}
			$download_zipto_path = $download_zipto_path.'.tgz';

		}elseif( $this->px->req()->get_param('ext') == 'zip' && class_exists( 'ZipArchive' ) ){
			#	ZIP関数が有効だったら
			$className = $this->px->load_px_plugin_class( '/asazuke/resources/zip.php' );
			if( !$className ){
				$this->px->error()->error_log( 'zipライブラリのロードに失敗しました。' , __FILE__ , __LINE__ );
				return	'<p class="error">zipライブラリのロードに失敗しました。</p>';
			}
			$obj_zip = new $className( $this->px );
			if( !$obj_zip->zip( $download_content_path , $download_zipto_path.'.zip' ) ){
				return	'<p class="error">圧縮に失敗しました。</p>';
			}

			if( !is_file( $download_zipto_path.'.zip' ) ){
				return	'<p class="error">圧縮されたアーカイブファイルは現在は存在しません。</p>';
			}

			$dl_filename = @$this->cmd[1].'_'.@$this->cmd[2].'.zip';
			if( $this->pcconf->get_value('dl_datetime_in_filename') ){
				$CONTENT = $this->px->dbh()->file_get_contents( $download_content_path.'/__LOGS__/datetime.txt' );
				list( $start_datetime , $end_datetime ) = explode(' --- ',$CONTENT);
				if( !strlen( $end_datetime ) ){
					$end_datetime = date('Y-m-d H:i:s');
				}
				$dl_filename = 'PxFW_asazuke_'.date('Ymd_Hi',$this->px->dbh()->datetime2int($end_datetime)).'.zip';
			}
			$download_zipto_path = $download_zipto_path.'.zip';

		}

		$result = $this->px->flush_file( $download_zipto_path , array( 'filename'=>$dl_filename , 'delete'=>true ) );
		if( $result === false ){
			return	'<p class="error">作成されたアーカイブのダウンロードに失敗しました。</p>';
		}
		return	$result;
	}//download_program_content()


	###################################################################################################################

	/**
	 * プログラムが書き出したコンテンツの削除
	 */
	private function start_delete_program_content(){
		if( $this->px->req()->get_param('mode') == 'thanks' ){
			return	$this->page_delete_program_content_thanks();
		}elseif( $this->px->req()->get_param('mode') == 'execute' ){
			return	$this->execute_delete_program_content_execute();
		}
		return	$this->page_delete_program_content_confirm();
	}
	/**
	 * プログラムが書き出したコンテンツの削除：確認
	 */
	private function page_delete_program_content_confirm(){
		$RTN = ''."\n";
		$HIDDEN = ''."\n";

		$RTN .= '<p>プログラムが書き出したコンテンツを削除します。</p>'."\n";
		$RTN .= '<p>よろしいですか？</p>'."\n";

		$RTN .= '<div class="unit center">'."\n";
		$RTN .= '<form action="'.htmlspecialchars( $this->href() ).'" method="post">'."\n";
		$RTN .= '	<input type="hidden" name="mode" value="execute" />'."\n";
		$RTN .= $HIDDEN;
		$RTN .= '	'.''."\n";
		$RTN .= '	<input type="submit" value="削除する" />'."\n";
		$RTN .= '</form>'."\n";
		$RTN .= '</div>'."\n";
		$RTN .= '<hr />'."\n";
		$RTN .= '<form action="'.htmlspecialchars( $this->href( ':' ) ).'" method="post">'."\n";
		$RTN .= '	<p class="center"><input type="submit" value="キャンセル" /></p>'."\n";
		$RTN .= '</form>'."\n";
		return	$RTN;
	}
	/**
	 * プログラムが書き出したコンテンツの削除：実行
	 */
	private function execute_delete_program_content_execute(){
		// if( !$this->user->save_t_lastaction() ){
		// 	#	2重書き込み防止
		// 	return	$this->px->redirect( $this->href().'&mode=thanks' );
		// }


		$project_model = &$this->pcconf->factory_model_project();
		$project_model->load_project();
		$program_model = &$project_model->factory_program();
		$result = $program_model->delete_program_content();

		if( !$result ){
			return	'<p class="error">プログラムコンテンツの削除に失敗しました。</p>';
		}

		return	$this->px->redirect( $this->href().'&mode=thanks' );
	}
	/**
	 * プログラムが書き出したコンテンツの削除：完了
	 */
	private function page_delete_program_content_thanks(){
		$RTN = ''."\n";
		$RTN .= '<p>プログラムコンテンツの削除処理を完了しました。</p>';
		$RTN .= '<form action="'.htmlspecialchars( $this->href( ':' ) ).'" method="post">'."\n";
		$RTN .= '	<input type="submit" value="戻る" />'."\n";
		$RTN .= '</form>'."\n";
		return	$RTN;
	}






	/**
	 * 設定項目の確認
	 */
	private function page_configcheck(){
		$RTN = ''."\n";
		$RTN .= '<div class="unit">'."\n";
		$RTN .= '<table class="def">'."\n";
		$RTN .= '	<tr>'."\n";
		$RTN .= '		<th style="width:30%;">作業ディレクトリ</th>'."\n";
		$path = $this->pcconf->get_home_dir();
		if( is_dir( $path ) ){
			$path = realpath( $path );
		}
		$RTN .= '		<td style="width:70%;">'.htmlspecialchars( $path ).'</td>'."\n";
		$RTN .= '	</tr>'."\n";
		$RTN .= '	<tr>'."\n";
		$RTN .= '		<th style="width:30%;">収集数の上限</th>'."\n";
		$RTN .= '		<td style="width:70%;">'.htmlspecialchars( $this->pcconf->get_value('crawl_max_url_number') ).'</td>'."\n";
		$RTN .= '	</tr>'."\n";
		$RTN .= '	<tr>'."\n";
		$RTN .= '		<th style="width:30%;">phpのパス</th>'."\n";
		$RTN .= '		<td style="width:70%;">'.htmlspecialchars( $this->pcconf->get_path_command('php') ).'</td>'."\n";
		$RTN .= '	</tr>'."\n";
		$RTN .= '	<tr>'."\n";
		$RTN .= '		<th style="width:30%;">tarのパス</th>'."\n";
		$RTN .= '		<td style="width:70%;">'.htmlspecialchars( $this->pcconf->get_path_command('tar') ).'</td>'."\n";
		$RTN .= '	</tr>'."\n";
		$RTN .= '</table>'."\n";
		$RTN .= '</div>'."\n";
		$RTN .= '<form action="'.htmlspecialchars( $this->href(':') ).'" method="post">'."\n";
		$RTN .= '	<p class="center"><input type="submit" value="戻る" /></p>'."\n";
		$RTN .= '</form>'."\n";
		return	$RTN;
	}

}

?>