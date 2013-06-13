<?php
class cont_{
	private $px;

	/**
	 * コンストラクタ
	 */
	public function __construct( $px ){
		$this->px = $px;
	}

	/**
	 * $dao_userを生成する
	 */
	private function &factory_dao_user(){
		$class_name_dao_user = $this->px->load_px_class( '/daos/user.php' );
		if( !$class_name_dao_user ){
			return false;
		}
		$dao_user = new $class_name_dao_user( $this->px );
		return $dao_user;
	}

	/**
	 * コンテンツを実行
	 */
	public function execute(){
		return $this->page_withdraw();
	}

	/**
	 * ユーザー情報変更
	 */
	private function page_withdraw(){
		$validate = $this->page_withdraw_validate();
		$mode = $this->px->req()->get_param('mode');

		switch( $mode ){
			case 'execute':
				if(!$this->px->user()->is_login()){ return $this->px->page_login(); }
				if(!count($validate)){
					return $this->page_withdraw_execute();
				}
				break;
			case 'complete':
				return $this->page_withdraw_complete();
				break;
			default:
				if(!$this->px->user()->is_login()){ return $this->px->page_login(); }
				$validate = array();
				break;
		}
		return $this->page_withdraw_input($validate);
	}
	private function page_withdraw_input($validate){
		$dao_user = $this->factory_dao_user();

		$rtn = '';
		$rtn .= '<p>'."\n";
		$rtn .= '	ユーザー登録情報を削除し、サービスから退会します。<br />'."\n";
		$rtn .= '</p>'."\n";

		$rtn .= '<div class="unit">'."\n";
		$rtn .= '<table class="def" style="width:100%;">'."\n";
		$rtn .= '	<tr>'."\n";
		$rtn .= '		<th>アカウント名</th>'."\n";
		$rtn .= '		<td>'.t::h( $this->px->user()->get_login_user_account() ).'</td>'."\n";
		$rtn .= '	</tr>'."\n";
		$rtn .= '	<tr>'."\n";
		$rtn .= '		<th>お名前</th>'."\n";
		$rtn .= '		<td>'.t::h( $this->px->user()->get_login_user_name() ).'</td>'."\n";
		$rtn .= '	</tr>'."\n";
		$rtn .= '	<tr>'."\n";
		$rtn .= '		<th>メールアドレス</th>'."\n";
		$rtn .= '		<td>'.t::h( $this->px->user()->get_login_user_email() ).'</td>'."\n";
		$rtn .= '	</tr>'."\n";
		$rtn .= '</table>'."\n";
		$rtn .= '</div>'."\n";

		$rtn .= '<p>'."\n";
		$rtn .= '	一度退会すると、ユーザー情報を復帰することができません。<br />'."\n";
		$rtn .= '</p>'."\n";
		$rtn .= '<p>'."\n";
		$rtn .= '	本当に退会してもよろしいですか？<br />'."\n";
		$rtn .= '</p>'."\n";

		if( count($validate) ){
			$rtn .= '<div class="unit form_error_box">'."\n";
			$rtn .= '	<p>次のエラーがありました。</p>'."\n";
			$rtn .= '	<ul>'."\n";
			foreach( $validate as $key=>$val ){
				$rtn .= '		<li>'.t::h($val).'</li>'."\n";
			}
			$rtn .= '	</ul>'."\n";
			$rtn .= '</div><!-- /.form_error_box -->'."\n";

		}

		$rtn .= '<form action="'.t::h($this->px->theme()->href( $this->px->req()->get_request_file_path() )).'" method="post">'."\n";

		$rtn .= '<div class="unit">';
		$rtn .= '<ul>'."\n";
		$rtn .= '	<li><label><input type="checkbox" name="confirm" value="1"'.(strlen($this->px->req()->get_param('confirm'))?' checked="checked"':'').' /> はい、本当に退会します。</label></li>'."\n";
		$rtn .= '</ul>'."\n";
		$rtn .= '</div>'."\n";

		$rtn .= '<div>';
		$rtn .= '<input type="hidden" name="mode" value="execute" />';
		$rtn .= '</div>'."\n";
		$rtn .= '<div class="unit form_buttons">'."\n";
		$rtn .= '	<ul>'."\n";
		$rtn .= '		<li class="form_buttons-submit"><input type="submit" value="本当に退会する" /></li>'."\n";
		$rtn .= '	</ul>'."\n";
		$rtn .= '</div><!-- /.form_buttons -->'."\n";

		$rtn .= '</form>'."\n";

		return $rtn;
	}
	private function page_withdraw_validate(){
		$rtn = array();
		if( !strlen( $this->px->req()->get_param('confirm') ) ){
			$rtn['confirm'] = '確認のため、チェックボックスのチェックをオンにしてください。';
		}
		return $rtn;
	}
	private function page_withdraw_execute(){
		$dao_user = $this->factory_dao_user();
		if( !$dao_user ){
			return '<p>DAOの生成に失敗しました。</p>';
		}

		$result = $dao_user->delete_user( $this->px->user()->get_login_user_id() );
		if( !$result ){
			return '<p>ユーザー情報の削除に失敗しました。</p>';
		}

		return $this->px->redirect( '?mode=complete' );
	}
	private function page_withdraw_complete(){
		$rtn = '';
		$rtn .= '<p>'."\n";
		$rtn .= '	ユーザー情報を削除しました。<br />'."\n";
		$rtn .= '</p>'."\n";
		$rtn .= '<p class="center">[<a href="'.t::h( $this->px->theme()->href( '' ) ).'">戻る</a>]</p>'."\n";
		return $rtn;
	}

}


$obj = new cont_($px);
print $obj->execute();
?>