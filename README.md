# Pickles Framework

Pickles Framework(PxFW) は、静的で大きなウェブサイトを効率よく構築できる オープンソースのフレームワークです。

データベース不要、PHP5が動くサーバーに手軽に導入でき、プロトタイプ制作を中心に進めるような柔軟な制作スタイルを実現します。

より詳しい説明は下記のウェブサイトを参照してください。

- <a href="http://pickles.pxt.jp/">http://pickles.pxt.jp/</a>


## セットアップ手順 - Setup

1. リポジトリのタグページ( https://github.com/tomk79/PxFW-1.x/tags )より、ソース一式をダウンロードします。
2. 解凍します。
3. htdocsに格納されたファイル一式を、お使いのウェブサーバーのドキュメントルート配下の任意のディレクトリに設置します。
4. パーミッションを設定します。<br />次のディレクトリとそれ以下の全てのファイルに、Apacheが書き込みできるパーミッションを設定してください。(この手順はWindowsサーバーでは不要です)
    - ./_PX/_sys
    - ./_caches
5. ウェブブラウザからアクセスします。

スタートページが表示されれば成功です。



## 使い方 - Usage

1. `./_PX/sitemaps/sitemap.csv` を編集して、ページを登録します。
2. コンテンツのHTMLを編集します。
3. テーマ `./_PX/themes/default/default.html` を編集します。
4. パブリッシュします。

より詳しいチュートリアルドキュメントが、下記のページから入手できます。

- <a href="http://pickles.pxt.jp/tutorial/">http://pickles.pxt.jp/tutorial/</a>


### パブリッシュ手順 - Publish

1. URLに `?PX=publish.run` を付けてアクセスします。
2. `./_PX/_sys/publish/` に出力されます。

出力先のディレクトリは `./_PX/configs/mainconf.ini` の
`publish` ディレクティブ `path_publish_dir` で変更できます。


## システム要件 - System Requirement

- Linux系サーバ または Windowsサーバ
- Apache1.3以降
    - mod_rewrite が利用可能であること
    - .htaccess が利用可能であること
- PHP5.3以上
    - mb_string が有効に設定されていること
    - safe_mode が無効に設定されていること



### Windowsサーバでの制約

Windows系OSでは、次のような制約を受けます。

- ボリューム名からバックスラッシュ区切りのパス名は使用できません。
絶対パスは、スラッシュから始まり、ディレクトリをスラッシュで区切ったUNIXの規則に変換して取り扱われます。
- セットアップされたディスク以外のボリュームにアクセスすることはできません。また、`\\` から始まるネットワークディレクトリにもアクセスできません。
- コマンドラインから実行することはできません。
- この他、PHPやApacheが持つ制約の影響を受けます。


## PXコマンドに関する注意事項
PXコマンドは、パブリッシュ機能(`?PX=publish`) や テーマ選択機能(`?PX=themes`)の他にも、Pickles Framework を便利に使うためのさまざまな機能を提供します。PXコマンドはサーバー内部の情報にアクセスしたり、サーバー上のデータを書き換えるインターフェイスを提供するため、第3者にアクセスされると大変キケンです。

Pickles Framework をインターネット上のサーバーで動かす場合には、次のことに注意してください。

- ウェブ制作環境として利用する場合、利用基本認証やIP制限などの処理を施し、一般のユーザーがアクセスできない場所に設置してください。
- または、Pickles Framework 上に構築したウェブアプリケーションをサービスとして公開する場合、設定ファイル (`./_PX/configs/mainconf.ini`) の `[system] allow_pxcommands` の値を `0` に設定し、PXコマンド機能を無効にしてください。



## 更新履歴 - Change log

更新履歴は、下記のファイルに記述があります。

- `./_docs/changelog.md`


## ライセンス - License

<a href="http://ja.wikipedia.org/wiki/MIT_License" target="_blank">MIT License</a>


## 作者 - Author

- (C)Tomoya Koyanagi &lt;tomk79@gmail.com&gt;
- website: <a href="http://www.pxt.jp/" target="_blank">http://www.pxt.jp/</a>
- Twitter: @tomk79 <a href="http://twitter.com/tomk79/" target="_blank">http://twitter.com/tomk79/</a>


