<?php /* Smarty version Smarty-3.1.8, created on 2012-05-16 08:06:07
         compiled from "\Users\k-watanabe\Desktop\private\Dropbox\project\px\PxFW-1.x\htdocs\test\commandlist.html" */ ?>
<?php /*%%SmartyHeaderCode:54534fb343cf8b4883-97193018%%*/if(!defined('SMARTY_DIR')) exit('no direct access allowed');
$_valid = $_smarty_tpl->decodeProperties(array (
  'file_dependency' => 
  array (
    '44188fd446333a3cbf05e1924d22ad925a30fbe6' => 
    array (
      0 => '\\Users\\k-watanabe\\Desktop\\private\\Dropbox\\project\\px\\PxFW-1.x\\htdocs\\test\\commandlist.html',
      1 => 1337078914,
      2 => 'file',
    ),
  ),
  'nocache_hash' => '54534fb343cf8b4883-97193018',
  'function' => 
  array (
  ),
  'has_nocache_code' => false,
  'version' => 'Smarty-3.1.8',
  'unifunc' => 'content_4fb343cf8ed199_27291243',
),false); /*/%%SmartyHeaderCode%%*/?>
<?php if ($_valid && !is_callable('content_4fb343cf8ed199_27291243')) {function content_4fb343cf8ed199_27291243($_smarty_tpl) {?>
<p>ピクルスのコマンドは、URLの後ろに ?PX=foo でENTER!!</p>

<ul>
<li><a href="./?PX=config">config</a>
<p>メインコンフィグの設定内容を確認する。</p>
</li>
<li><a href="./?PX=clearcache">clearcache</a>
<p>PxFWのキャッシュを消去する。</p>
</li>
<li><a href="./?PX=initialize">initialize</a>
<p>データベースなどのを初期セットアップを実行する。プロジェクトのセットアップ時に1度だけ実行する。</p>
</li>
<li><a href="./?PX=sitemapdefinition">※未※sitemapdefinition</a>
<p>サイトマップ定義を確認する。</p>
</li>
<li><a href="./?PX=sitemap">sitemap</a>
<p>イトマップ全体を表示する。</p>
</li>
<li><a href="./?PX=pageinfo">pageinfo</a>
<p>ページの情報を表示する。</p>
</li>
<li><a href="./?PX=themeinfo">※未※themeinfo</a>
<p>テーマの一覧を表示する。</p>
</li>
<li><a href="./?PX=themes">※未※themes</a>
<p>PxFWのキャッシュを消去する。</p>
</li>
<li><a href="./?PX=phpinfo">phpinfo</a>
<p>phpinfoを表示する。</p>
</li>
<li><a href="./?PX=publish">publish</a>
<p>プロジェクトを静的なHTMLファイル群に出力する。<br />
メインコンフィグの publish.path_publish_dir に設定されたディレクトリに出力される。<br />
この機能の詳細は パブリッシュ を参照。</p>
</li>
<li><a href="./?PX=search">※未※search</a>
<p>プロジェクトのソースコードを全文検索する。</p>
</li>
<li><a href="./?PX=accesslog">※未※accesslog</a>
<p>アクセスログを閲覧する。</p>
</li>
<li><a href="./?PX=errorlog">※未※errorlog</a>
<p>エラーログを閲覧する。</p>
</li><?php }} ?>