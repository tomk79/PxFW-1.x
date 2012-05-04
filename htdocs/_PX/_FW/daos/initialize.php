<?php
$this->load_pxclass('/bases/dao.php');

/**
 * 初期化処理のDAO
 **/
class px_daos_initialize extends px_bases_dao{

	/**
	 * ユーザー関連テーブルを作成する。
	 */
	public function create_user_tables(){
		#--------------------------------------
		#	user: ユーザマスタテーブル
		ob_start();?>
<?php if( $this->px->get_conf('dbs.dbms') == 'postgresql' ){ ?>
CREATE TABLE :D:table_name(
    id    VARCHAR NOT NULL,
    user_account    VARCHAR NOT NULL,
    user_pw    VARCHAR NOT NULL,
    user_name    VARCHAR,
    user_email    VARCHAR,
    tmp_pw    VARCHAR,
    tmp_email    VARCHAR,
    tmp_data    TEXT,
    login_date    TIMESTAMP DEFAULT 'NOW',
    create_date    TIMESTAMP DEFAULT 'NOW',
    update_date    TIMESTAMP DEFAULT 'NOW',
    delete_date    TIMESTAMP DEFAULT 'NOW',
    delete_flg    INT2 NOT NULL DEFAULT '0'
);
<?php }elseif( $this->px->get_conf('dbs.dbms') == 'sqlite' ){ ?>
CREATE TABLE :D:table_name(
    id    VARCHAR(64) NOT NULL,
    user_account    VARCHAR(64) NOT NULL,
    user_pw    VARCHAR(32) NOT NULL,
    user_name    VARCHAR(128),
    user_email    VARCHAR(128),
    tmp_pw    VARCHAR(32),
    tmp_email    VARCHAR(128),
    tmp_data    TEXT,
    login_date    DATETIME DEFAULT NULL,
    create_date    DATETIME DEFAULT NULL,
    update_date    DATETIME DEFAULT NULL,
    delete_date    DATETIME DEFAULT NULL,
    delete_flg    INT(1) NOT NULL DEFAULT '0'
);
<?php }else{ ?>
CREATE TABLE :D:table_name(
    id    VARCHAR(64) NOT NULL,
    user_account    VARCHAR(64) NOT NULL,
    user_pw    VARCHAR(32) NOT NULL,
    user_name    VARCHAR(128),
    user_email    VARCHAR(128),
    tmp_pw    VARCHAR(32),
    tmp_email    VARCHAR(128),
    tmp_data    TEXT,
    login_date    DATETIME DEFAULT NULL,
    create_date    DATETIME DEFAULT NULL,
    update_date    DATETIME DEFAULT NULL,
    delete_date    DATETIME DEFAULT NULL,
    delete_flg    INT(1) NOT NULL DEFAULT '0'
);
<?php } ?>
<?php
		$sql['user'] = array();
		array_push( $sql['user'] , @ob_get_clean() );
/*
		if( $this->px->get_conf('dbs.dbms') == 'postgresql' ){
			#	PostgreSQL
			array_push( $sql['user'] , 'ALTER TABLE :D:table_name ADD PRIMARY KEY ( user_cd );' );
			array_push( $sql['user'] , 'ALTER TABLE :D:table_name ADD UNIQUE ( user_id );' );
		}elseif( $this->px->get_conf('dbs.dbms') == 'mysql' ){
			#	MySQL
			array_push( $sql['user'] , 'ALTER TABLE :D:table_name ADD PRIMARY KEY ( user_cd );' );
			array_push( $sql['user'] , 'ALTER TABLE :D:table_name CHANGE user_cd user_cd INT(11) NOT NULL AUTO_INCREMENT;' );
			array_push( $sql['user'] , 'CREATE UNIQUE INDEX user_id ON :D:table_name (user_id(64));' );
		}
*/

		//トランザクション：スタート
		$this->px->dbh()->start_transaction();

		foreach( $sql as $table_name=>$sql_row ){
			foreach( $sql_row as $sql_content ){
				$bind_data = array(
					'table_name'=>$this->px->get_conf('dbs.prefix').'_'.$table_name,
				);
				$sql_final = $this->px->dbh()->bind( $sql_content , $bind_data );
				if( !strlen( $sql_final ) ){ continue; }

				if( !$this->px->dbh()->send_query( $sql_final ) ){
					$this->px->error()->error_log('database query error ['.$sql_final.']');

					//トランザクション：ロールバック
					$this->px->dbh()->rollback();
					return false;
				}
			}
		}

		//トランザクション：コミット
		$this->px->dbh()->commit();

		return true;
	}

}
?>