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
		return $this->page_add_user();
	}

	/**
	 * モード：ユーザー追加テスト
	 */
	private function page_add_user(){
		$validate = $this->page_add_user_validate();
		$mode = $this->px->req()->get_param('mode');

		switch( $mode ){
			case 'execute':
				if(!count($validate)){
					return $this->page_add_user_execute();
				}
				break;
			case 'confirm':
				if(!count($validate)){
					return $this->page_add_user_confirm();
				}
				break;
			case 'complete':
				return $this->page_add_user_complete();
				break;
			default:
				$validate = array();
				break;
		}
		return $this->page_add_user_input($validate);
	}
	private function page_add_user_input($validate){
		$dao_user = $this->factory_dao_user();

		$rtn = '';
		$rtn .= '<p>'."\n";
		$rtn .= '	ユーザーを追加します。<br />'."\n";
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

		$rtn .= '<table class="form_elements">'."\n";
		$rtn .= '	<thead>'."\n";
		$rtn .= '		<tr>'."\n";
		$rtn .= '			<th>入力項目名</th>'."\n";
		$rtn .= '			<th>入力フィールド</th>'."\n";
		$rtn .= '		</tr>'."\n";
		$rtn .= '	</thead>'."\n";
		$rtn .= '	<tbody>'."\n";
		$rtn .= '		<tr'.(strlen($validate['user_account'])?' class="form_elements-error"':'').'>'."\n";
		$rtn .= '			<th>アカウント名<span class="form_elements-must">必須</span></th>'."\n";
		$rtn .= '			<td>'."\n";
		$rtn .= '				<ul class="form_elements-notes">'."\n";
		$rtn .= '					<li>半角英数字で入力してください。</li>'."\n";
		$rtn .= '				</ul>'."\n";
		if( strlen($validate['user_account']) ){
			$rtn .= '				<ul class="form_elements-errors">'."\n";
			$rtn .= '					<li>'.t::h($validate['user_account']).'</li>'."\n";
			$rtn .= '				</ul>'."\n";
		}
		$rtn .= '				<input type="text" name="user_account" value="'.t::h( $this->px->req()->get_param('user_account') ).'" />'."\n";
		$rtn .= '			</td>'."\n";
		$rtn .= '		</tr>'."\n";
		$rtn .= '		<tr'.(strlen($validate['user_pw'])?' class="form_elements-error"':'').'>'."\n";
		$rtn .= '			<th>パスワード<span class="form_elements-must">必須</span></th>'."\n";
		$rtn .= '			<td>'."\n";
		if( strlen($validate['user_pw']) ){
			$rtn .= '				<ul class="form_elements-errors">'."\n";
			$rtn .= '					<li>'.t::h($validate['user_pw']).'</li>'."\n";
			$rtn .= '				</ul>'."\n";
		}
		$rtn .= '				<input type="password" name="user_pw" value="" />'."\n";
		$rtn .= '			</td>'."\n";
		$rtn .= '		</tr>'."\n";
		$rtn .= '		<tr'.(strlen($validate['user_name'])?' class="form_elements-error"':'').'>'."\n";
		$rtn .= '			<th>お名前<span class="form_elements-must">必須</span></th>'."\n";
		$rtn .= '			<td>'."\n";
		if( strlen($validate['user_name']) ){
			$rtn .= '				<ul class="form_elements-errors">'."\n";
			$rtn .= '					<li>'.t::h($validate['user_name']).'</li>'."\n";
			$rtn .= '				</ul>'."\n";
		}
		$rtn .= '				<input type="text" name="user_name" value="'.t::h( $this->px->req()->get_param('user_name') ).'" />'."\n";
		$rtn .= '			</td>'."\n";
		$rtn .= '		</tr>'."\n";
		$rtn .= '		<tr'.(strlen($validate['user_email'])?' class="form_elements-error"':'').'>'."\n";
		$rtn .= '			<th>メールアドレス<span class="form_elements-must">必須</span></th>'."\n";
		$rtn .= '			<td>'."\n";
		if( strlen($validate['user_email']) ){
			$rtn .= '				<ul class="form_elements-errors">'."\n";
			$rtn .= '					<li>'.t::h($validate['user_email']).'</li>'."\n";
			$rtn .= '				</ul>'."\n";
		}
		$rtn .= '				<input type="text" name="user_email" value="'.t::h( $this->px->req()->get_param('user_email') ).'" />'."\n";
		$rtn .= '			</td>'."\n";
		$rtn .= '		</tr>'."\n";
		$rtn .= '	</tbody>'."\n";
		$rtn .= '</table>'."\n";
		$rtn .= '<div>';
		$rtn .= '<input type="hidden" name="mode" value="confirm" />';
		$rtn .= '</div>'."\n";
		$rtn .= '<div class="unit form_buttons">'."\n";
		$rtn .= '	<ul>'."\n";
		$rtn .= '		<li class="form_buttons-submit"><input type="submit" value="入力内容を確認する" /></li>'."\n";
		$rtn .= '	</ul>'."\n";
		$rtn .= '</div><!-- /.form_buttons -->'."\n";

		$rtn .= '</form>'."\n";

		return $rtn;
	}
	private function page_add_user_confirm(){
		$dao_user = $this->factory_dao_user();

		$rtn = '';
		$hidden = '';
		$rtn .= '<p>'."\n";
		$rtn .= '	ユーザーを追加します。入力した内容をご確認ください。<br />'."\n";
		$rtn .= '</p>'."\n";

		$rtn .= '<table class="form_elements">'."\n";
		$rtn .= '	<thead>'."\n";
		$rtn .= '		<tr>'."\n";
		$rtn .= '			<th>入力項目名</th>'."\n";
		$rtn .= '			<th>入力フィールド</th>'."\n";
		$rtn .= '		</tr>'."\n";
		$rtn .= '	</thead>'."\n";
		$rtn .= '	<tbody>'."\n";
		$rtn .= '		<tr>'."\n";
		$rtn .= '			<th>アカウント名</th>'."\n";
		$rtn .= '			<td>'."\n";
		$rtn .= '				'.t::h( $this->px->req()->get_param('user_account') ).''."\n";
		$rtn .= '			</td>'."\n";
		$rtn .= '		</tr>'."\n";
		$hidden .= '<input type="hidden" name="user_account" value="'.t::h( $this->px->req()->get_param('user_account') ).'" />';
		$rtn .= '		<tr>'."\n";
		$rtn .= '			<th>パスワード</th>'."\n";
		$rtn .= '			<td>'."\n";
		$rtn .= '				********'."\n";
		$rtn .= '			</td>'."\n";
		$rtn .= '		</tr>'."\n";
		$hidden .= '<input type="hidden" name="user_pw" value="'.t::h( $this->px->req()->get_param('user_pw') ).'" />';
		$rtn .= '		<tr>'."\n";
		$rtn .= '			<th>お名前</th>'."\n";
		$rtn .= '			<td>'."\n";
		$rtn .= '				'.t::h( $this->px->req()->get_param('user_name') ).''."\n";
		$rtn .= '			</td>'."\n";
		$rtn .= '		</tr>'."\n";
		$hidden .= '<input type="hidden" name="user_name" value="'.t::h( $this->px->req()->get_param('user_name') ).'" />';
		$rtn .= '		<tr>'."\n";
		$rtn .= '			<th>メールアドレス</th>'."\n";
		$rtn .= '			<td>'."\n";
		$rtn .= '				'.t::h( $this->px->req()->get_param('user_email') ).''."\n";
		$rtn .= '			</td>'."\n";
		$rtn .= '		</tr>'."\n";
		$hidden .= '<input type="hidden" name="user_email" value="'.t::h( $this->px->req()->get_param('user_email') ).'" />';
		$rtn .= '	</tbody>'."\n";
		$rtn .= '</table>'."\n";
		$rtn .= '<form action="'.t::h($this->px->theme()->href( $this->px->req()->get_request_file_path() )).'" method="post">'."\n";
		$rtn .= '<div>';
		$rtn .= '<input type="hidden" name="mode" value="execute" />';
		$rtn .= $hidden;
		$rtn .= '</div>'."\n";
		$rtn .= '<div class="unit form_buttons">'."\n";
		$rtn .= '	<ul>'."\n";
		$rtn .= '		<li class="form_buttons-submit"><input type="submit" value="ユーザーを追加する" /></li>'."\n";
		$rtn .= '		<li class="form_buttons-revise"><input type="submit" value="修正する" onclick="$(\'#content input[name=mode]\')[0].value=\'input\';" /></li>'."\n";
		$rtn .= '	</ul>'."\n";
		$rtn .= '</div><!-- /.form_buttons -->'."\n";
		$rtn .= '</form>'."\n";

		$rtn .= '<form action="'.t::h($this->px->theme()->href( $this->px->site()->get_parent() )).'" method="post">'."\n";
		$rtn .= '<div class="unit form_buttons">'."\n";
		$rtn .= '	<ul>'."\n";
		$rtn .= '		<li class="form_buttons-cancel"><input type="submit" value="キャンセル" /></li>'."\n";
		$rtn .= '	</ul>'."\n";
		$rtn .= '</div><!-- /.form_buttons -->'."\n";
		$rtn .= '</form>'."\n";

		return $rtn;
	}
	private function page_add_user_validate(){
		$rtn = array();
		if( !strlen( $this->px->req()->get_param('user_account') ) ){
			$rtn['user_account'] = 'アカウント名を入力してください。';
		}elseif( strlen( $this->px->req()->get_param('user_account') )>255 ){
			$rtn['user_account'] = 'アカウント名が長すぎます。';
		}elseif( !preg_match( '/^[a-zA-Z0-9\-\_]+$/s', $this->px->req()->get_param('user_account') ) ){
			$rtn['user_account'] = 'アカウント名に使用できない文字が含まれています。半角英数字で入力してください。';
		}else{
			$dao_user = $this->factory_dao_user();
			$user_preset = $dao_user->get_user_info_by_account( $this->px->req()->get_param('user_account') );
			if($user_preset['user_account']==$this->px->req()->get_param('user_account')){
				$rtn['user_account'] = 'このアカウント名は既に使用されています。';
			}
		}

		if( !strlen( $this->px->req()->get_param('user_pw') ) ){
			$rtn['user_pw'] = 'パスワードを入力してください。';
		}

		if( !strlen( $this->px->req()->get_param('user_name') ) ){
			$rtn['user_name'] = 'お名前を入力してください。';
		}elseif( strlen( $this->px->req()->get_param('user_name') )>255 ){
			$rtn['user_name'] = 'お名前が長すぎます。';
		}

		if( !strlen( $this->px->req()->get_param('user_email') ) ){
			$rtn['user_email'] = 'メールアドレスを入力してください。';
		}
		return $rtn;
	}
	private function page_add_user_execute(){
		$new_user_info = array(
			'user_account'=>$this->px->req()->get_param('user_account'),
			'user_pw'=>$this->px->req()->get_param('user_pw'),
			'user_name'=>$this->px->req()->get_param('user_name'),
			'user_email'=>$this->px->req()->get_param('user_email'),
		);
		$dao_user = $this->factory_dao_user();
		if( !$dao_user ){
			return '<p>DAOの生成に失敗しました。</p>';
		}

		$result = $dao_user->create_user( $new_user_info );
		if( !$result ){
			return '<p>ユーザーの追加に失敗しました。</p>';
		}

		return $this->px->redirect( '?mode=complete' );
	}
	private function page_add_user_complete(){
		$rtn = '';
		$rtn .= '<p>'."\n";
		$rtn .= '	ユーザー追加を完了しました。<br />'."\n";
		$rtn .= '</p>'."\n";
		$rtn .= '<p class="center">[<a href="'.t::h( $this->px->theme()->href( $this->px->site()->get_parent() ) ).'">戻る</a>]</p>'."\n";
		return $rtn;
	}

}


$obj = new cont_($px);
print $obj->execute();
?>