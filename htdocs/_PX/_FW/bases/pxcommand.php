<?php
class px_bases_pxcommand{
	protected $px;
	protected $pxcommand_name = null;

	/**
	 * コンストラクタ
	 */
	public function __construct( $pxcommand_name , &$px ){
		$this->px = &$px;
		$this->pxcommand_name = $pxcommand_name;
	}//__construct()

	/**
	 * コマンド名を取得する
	 */
	public function get_command(){
		return $this->pxcommand_name;
	}

	/**
	 * コンテンツをHTMLテンプレートに包んで返す
	 */
	protected function html_template( $content ){
		@header( 'Content-type: text/html; charset="UTF-8"' );
		$src = '';
		$src .= '<!doctype html>'."\n";
		$src .= '<html>'."\n";
		$src .= '<head>'."\n";
		$src .= '<title>'.htmlspecialchars( $this->pxcommand_name[0] ).' | Pickles Framework</title>'."\n";
		$src .= '<style type="text/css">'."\n";

		$src .= ''."\n";
		$src .= '/**'."\n";
		$src .= ' * ユニット'."\n";
		$src .= ' * モジュールの単位となる。前後にデフォルトマージン付加'."\n";
		$src .= ' */'."\n";
		$src .= 'div.unit{'."\n";
		$src .= '	margin-top:1em;'."\n";
		$src .= '	margin-bottom:1.5em;'."\n";
		$src .= '	clear:both;'."\n";
		$src .= '}'."\n";
		$src .= ''."\n";
		$src .= '/**'."\n";
		$src .= ' * 画像置換'."\n";
		$src .= ' * .haribotekit の移植'."\n";
		$src .= ' */'."\n";
		$src .= '.imagereplace{'."\n";
		$src .= '	display:block;'."\n";
		$src .= '	position:relative; top:auto; left:auto;'."\n";
		$src .= '	padding:0px 0px 0px 0px !important;'."\n";
		$src .= '	border:0px solid transparent !important;'."\n";
		$src .= '	overflow:hidden;'."\n";
		$src .= '	zoom:100%;'."\n";
		$src .= '}'."\n";
		$src .= '.imagereplace .imagereplace-panel{'."\n";
		$src .= '	display:block;'."\n";
		$src .= '	position:absolute; top:0px; left:0px;'."\n";
		$src .= '	width:100%; height:100%;'."\n";
		$src .= '	background-repeat:no-repeat;'."\n";
		$src .= '	background-color:transparent;'."\n";
		$src .= '	zoom:100%;'."\n";
		$src .= '}'."\n";
		$src .= ''."\n";
		$src .= ''."\n";
		$src .= '/**'."\n";
		$src .= ' * 文字サイズ'."\n";
		$src .= ' */'."\n";
		$src .= '.xxlarge{'."\n";
		$src .= '	font-size:xx-large;'."\n";
		$src .= '}'."\n";
		$src .= '.xlarge{'."\n";
		$src .= '	font-size:x-large;'."\n";
		$src .= '}'."\n";
		$src .= '.large{'."\n";
		$src .= '	font-size:large;'."\n";
		$src .= '}'."\n";
		$src .= '.medium{'."\n";
		$src .= '	font-size:medium;'."\n";
		$src .= '}'."\n";
		$src .= '.small{'."\n";
		$src .= '	font-size:small;'."\n";
		$src .= '}'."\n";
		$src .= '.xsmall{'."\n";
		$src .= '	font-size:x-small;'."\n";
		$src .= '}'."\n";
		$src .= '.xxsmall{'."\n";
		$src .= '	font-size:xx-small;'."\n";
		$src .= '}'."\n";
		$src .= ''."\n";
		$src .= '/**'."\n";
		$src .= ' * 文字組み'."\n";
		$src .= ' */'."\n";
		$src .= '.center{'."\n";
		$src .= '	text-align:center;'."\n";
		$src .= '}'."\n";
		$src .= '.left{'."\n";
		$src .= '	text-align:left;'."\n";
		$src .= '}'."\n";
		$src .= '.right{'."\n";
		$src .= '	text-align:right;'."\n";
		$src .= '}'."\n";
		$src .= ''."\n";
		$src .= '/**'."\n";
		$src .= ' * まわりこみ制御'."\n";
		$src .= ' */'."\n";
		$src .= '.fl{'."\n";
		$src .= '	float:left;'."\n";
		$src .= '}'."\n";
		$src .= '.fr{'."\n";
		$src .= '	float:right;'."\n";
		$src .= '}'."\n";
		$src .= '.fc{'."\n";
		$src .= '	float:none;'."\n";
		$src .= '	clear:both;'."\n";
		$src .= '}'."\n";
		$src .= ''."\n";
		$src .= '/**'."\n";
		$src .= ' * .clearfix'."\n";
		$src .= ' */'."\n";
		$src .= '.clearfix{'."\n";
		$src .= '	display:block;'."\n";
		$src .= '	float:none;'."\n";
		$src .= '	clear:both;'."\n";
		$src .= '}'."\n";
		$src .= '.clearfix:after{'."\n";
		$src .= '	content: " ";'."\n";
		$src .= '	display:block;'."\n";
		$src .= '	visibility:hidden;'."\n";
		$src .= '	height:0.1px;'."\n";
		$src .= '	font-size:0.1em;'."\n";
		$src .= '	line-height:0px;'."\n";
		$src .= '	clear:both;'."\n";
		$src .= '}'."\n";
		$src .= '.clearfix{'."\n";
		$src .= '	/* clearfix(for IE6,7) (IE8以降不要) */'."\n";
		$src .= '	zoom:1;'."\n";
		$src .= '}'."\n";
		$src .= ''."\n";
		$src .= '/**'."\n";
		$src .= ' * カレントページ'."\n";
		$src .= ' */'."\n";
		$src .= '.current{'."\n";
		$src .= '	font-weight:bold;'."\n";
		$src .= '}'."\n";
		$src .= ''."\n";
		$src .= '/**'."\n";
		$src .= ' * デフォルトテーブルスタイル'."\n";
		$src .= ' */'."\n";
		$src .= 'table.def {'."\n";
		$src .= '	border:none;'."\n";
		$src .= '	border-collapse: collapse;'."\n";
		$src .= '	text-align: left;'."\n";
		$src .= '}'."\n";
		$src .= 'table.def th,'."\n";
		$src .= 'table.def td {'."\n";
		$src .= '	border: 1px solid #d6d6d6;'."\n";
		$src .= '	padding: 10px;'."\n";
		$src .= '}'."\n";
		$src .= 'table.def th {'."\n";
		$src .= '	background: #e7e7e7;'."\n";
		$src .= '}'."\n";


		$src .= '</style>'."\n";
		$src .= '</head>'."\n";
		$src .= '<body>'."\n";
		$src .= '<h1>'.htmlspecialchars( $this->pxcommand_name[0] ).' | Pickles Framework</h1>'."\n";
		$src .= '<div id="content" class="contents">'."\n";
		$src .= $content;
		$src .= '</div>'."\n";
		$src .= '</body>'."\n";
		$src .= '</html>';
		return $src;
	}//html_template()

}

?>