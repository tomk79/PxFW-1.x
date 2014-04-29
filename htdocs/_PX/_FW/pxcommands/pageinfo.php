<?php
$this->load_px_class('/bases/pxcommand.php');

/**
 * PX Command: pageinfoを表示する
 **/
class px_pxcommands_pageinfo extends px_bases_pxcommand{

	public function __construct( $command , $px ){
		parent::__construct( $command , $px );
		$this->execute();
	}//__construct()

	/**
	 * Execute PX Command "pageinfo".
	 */
	private function execute(){
		$command = $this->get_command();
		if( @$command[1] == 'qr' ){
			$this->flush_qr();
			exit;
		}

		$pageinfo = $this->px->site()->get_current_page_info();

		$src = '';
		$src .= '<div class="unit">'."\n";
		$src .= '	<p>次のパスに該当するページの情報を表示します。</p>'."\n";
		$src .= '	<ul>'."\n";
		$src .= '		<li style="word-break:break-all;">'.t::h( $_SERVER['PATH_INFO'] ).'</li>'."\n";
		$src .= '	</ul>'."\n";
		$src .= '</div><!-- /.unit -->'."\n";
		$src .= '<div class="unit">'."\n";
		$src .= '<table class="def" style="width:100%;">'."\n";
		$src .= '	<tr>'."\n";
		$src .= '		<th style="word-break:break-all;width:30%;">URLとQRコード</th>'."\n";
		$src .= '		<td style="word-break:break-all;width:70%;">';
		$src .= '<a href="'.t::h($this->mk_current_url()).'" target="_blank">'.t::text2html($this->mk_current_url()).'</a><br />';
		$src .= '<img src="'.t::h('?PX=pageinfo.qr').'" alt="QR Code" />';
		$src .= '</td>'."\n";
		$src .= '	</tr>'."\n";
		$src .= '</table>'."\n";
		$src .= '</div><!-- /.unit -->'."\n";
		$src .= '<div class="unit">'."\n";
		if( is_null($pageinfo) ){
			$src .= '	<p class="error">ページ情報が見つかりません。未定義です。</p>'."\n";
		}else{
			$src .= '<table class="def" style="width:100%;">'."\n";
			foreach( $pageinfo as $key=>$val ){
				$src .= '	<tr>'."\n";
				$src .= '		<th style="word-break:break-all;width:30%;">'.t::text2html($key).'</th>'."\n";
				$src .= '		<td style="word-break:break-all;width:70%;">'.t::text2html($val).'</td>'."\n";
				$src .= '	</tr>'."\n";
			}
			$src .= '</table>'."\n";
		}
		$src .= '</div><!-- /.unit -->'."\n";
		print $this->html_template( $src );
		exit;
	}

	/**
	 * このページのQRコードを出力する。
	 */
	private function flush_qr(){
		$pageinfo = $this->px->site()->get_current_page_info();

		$tmp_cd = realpath( '.' );
		chdir( $this->px->get_conf('paths.px_dir').'libs/qr_img/php/' );


		$tmpGET = $_GET;
		$_GET = array();
		$_GET['d'] = $this->mk_current_url();
		$_GET['t'] = 'P';
		$_GET['s'] = '4';
		@include('./qr_img.php');

		chdir( $tmp_cd );
		$_GET = $tmpGET;

		exit;
	}

	/**
	 * カレントページのフルURLを生成する
	 */
	private function mk_current_url(){
		return 'http'.($this->px->req()->is_ssl()?'s':'').'://'.$_SERVER['SERVER_NAME'].$this->px->href_self();
	}

}

?>