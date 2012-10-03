<?php

	$page_info = $px->site()->get_current_page_info();
	$page_info_c = $px->site()->get_page_info( 'test.dynamic.test_c' );
	$dynamic_path_info_c = $px->site()->get_dynamic_path_info( $page_info_c['path'] );
	test::var_dump( $page_info );
	test::var_dump( $dynamic_path_info_c );

	$param['a'] = $px->req()->get_path_param('test_a');
	$param['b'] = $px->req()->get_path_param('test_b');
	$param['c'] = $px->req()->get_path_param('test_c');

	test::var_dump( $param['a'] );
	test::var_dump( $param['b'] );
	test::var_dump( $param['c'] );


?>

<hr />

<?php

	if( !is_null( $param['b'] ) ){
		$array_c_val = array(
			'a',
			'b',
			'c',
		);
		foreach( $array_c_val as $c_val ){
			$tmp_page_info_c = $page_info_c;
			unset( $tmp_page_info_c['id'] );//ページIDは自動で振ってもらいたいので、あえて消す。
			$tmp_page_info_c['title'] = 'TEST '.$c_val;
			unset( $tmp_page_info_c['title_breadcrumb'] );  //タイトル系も自動で振りたいので、あえて消す。
			unset( $tmp_page_info_c['title_h1'] );          //タイトル系も自動で振りたいので、あえて消す。
			unset( $tmp_page_info_c['title_label'] );       //タイトル系も自動で振りたいので、あえて消す。
			$tmp_page_info_c['list_flg'] = 1;
			$tmp_page_info_c['path'] = $px->site()->bind_dynamic_path_param(
				$dynamic_path_info_c['path_original'] ,
				array('test_a'=>$param['a'],'test_b'=>$param['b'],'test_c'=>$c_val)
			);
			$px->site()->set_page_info( $tmp_page_info_c['path'] , $tmp_page_info_c );
		}
	}


	test::var_dump( $px->site()->get_children( 'test.dynamic.test_b' ) );

?>