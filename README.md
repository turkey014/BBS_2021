間違えて先に、このrepositoryのwikiに同じことを書きました。  
以下、コピペ

# 手順書

## 0.前提条件
* PCがある。基本的な操作(クリック、コピー、タイピングなど)や用語(エディタ、コマンド、ファイルなど)がわかる。
* AWSのアカウントを持っていて使えるクレジットがある。

## 1.AWSでEC2インスタンスを起動する  
[チュートリアル: Amazon EC2 Linux インスタンスの開始方法](https://docs.aws.amazon.com/ja_jp/AWSEC2/latest/UserGuide/EC2_GetStarted.html)
### EC2インスタンスの設定
1. Amazon マシンイメージ  
  Amazon Linux2 AMI(HVM), SSD Volume Type を選択  
2. インスタンスタイプの選択  
  t2.micro  
3. インスタンスの詳細の設定  
  デフォルト(「自動割り当てIP」が「サブネット設定を使用(有効)」になっていることを確認する。)  
4. ストレージの追加  
  デフォルト(8GB)  
5. タグの追加  
  お好みで設定  
6. セキュリティグループの設定  
  「タイプ」が「SSH」の行の「ソース」を「任意の場所」に変更する。  
  「ルールの追加」を押して行を追加し、「タイプ」は「HTTP」で「ソース」は「任意の場所」に設定する。  
  
## 2.EC2インスタンスにログインする
1. お使いの環境に応じてターミナルまたはPowerShellを起動する。（Macならターミナル、WindowsならPowerShell)  
  [ターミナルの開き方](https://support.apple.com/ja-jp/guide/terminal/apd5265185d-f365-44cb-8b09-71a064a42125/mac)
  [PowerShellのインストール](https://www.microsoft.com/ja-jp/p/powershell/9mz1snwt0n5d?activetab=pivot:overviewtab)
  [PowerShellの開き方](https://docs.microsoft.com/ja-jp/powershell/scripting/learn/ps101/01-getting-started?view=powershell-7.1)
2. sshコマンドを叩く  
```
ssh ec2-user@作成したEC2インスタンスの公開IPアドレス -i ダウンロードしたキーのパス 
```
  権限エラー(permission denied ...)が出た場合は、DLしたキーのパスを変更するコマンドを試行してみる。
```
chmod 600 ダウンロードしたキーのパス
```

## 3.EC2インスタンスに必要なものをインストールする  
1. vim (テキストエディタ)  
  コマンドを叩いてインストールする  
```
sudo yum install vim -y
```
* vimの簡単な使い方  
  vimの起動とファイルの開き方  
```
vim 開きたいファイル名
```
  ファイルの編集方法  
  iキーを押して挿入モード([INSERT])にすると編集できる。  
  カーソルキーでカーソルを移動し、ファイルを編集する。  
  エスケープキーを押して、挿入モードを終了する。  
  (挿入モードを終了しないとファイルの保存ができないので注意する)  
  ファイルを保存して終了する場合は、
```
:wq
```
  保存しないで終了する場合は、
```
:q
```
* vim の設定
  編集時、左側に行数を表示する。
  設定ファイルを開く。
```
vim ~/.vimrc
```
  行数を表示するための1行を追記する。
```
set number
```

2. screen (便利なツール)  
  コマンドを叩いてインストールする  
```
sudo yum install screen -y
```
* screenの簡単な使い方  
  起動する  
```
screen
```
  新しいウインドウを開く  
  Ctrl + a の直後に cキー  
  2つ以上ウインドウを開いているとき、  
  Ctrl + a の直後に nキーで次のウインドウへ移動  
  Ctrl + a の直後に pキーで前のウインドウへ移動  
  ウインドウを閉じる  
```
exit
```

3. docker (仮想的な環境を簡単に作れる便利なツール)  
  dockerのインストール～自動起動化コマンド  
```
sudo yum install -y docker
sudo systemctl start docker
sudo systemctl enable docker
sudo usermod -a -G docker ec2-user
```
  上の作業を完了した後は、反映させるために一度シェルを終了させる必要がある。  
  sshを一度ログアウトしてもう一度ログインする。    

4. docker-compose (dockerを簡単に扱うための便利なツール)  
  インストールコマンド  
```
sudo curl -L "https://github.com/docker/compose/releases/download/1.29.2/docker-compose-$(uname -s)-$(uname -m)" -o /usr/local/bin/docker-compose
sudo chmod +x /usr/local/bin/docker-compose
```
  インストールしてあるか確認する  
```
docker-compose -v
```
  docker-composeを使うために設定ファイルであるdocker-compose.yml を設定する。  
```
vim docker-compose.yml
```
  とりあえず今は起動確認のための中身を書く。詳しい設定は後ほど。  
```
version: "3"

services:
  web:
    image: nginx:latest
    ports:
      - 80:80
```
  docker-composeを起動する  
```
docker-compose up
```
  ウェブブラウザを立ち上げ、EC2インスタンスのIPアドレスに接続する。  
  Welcome to nginx！ の画面がでることを確認する。  
  docker-composeを停止する  
  docker-composeを起動したウインドウで Ctrl + c  

5. nginx (Webサーバー)
  設定ファイル用のディレクトリを作成する  
```
mkdir nginx
mkdir nginx/conf.d
```
  とりあえずの中身を書く。詳しい設定は後ほど。  
```
vim nginx/conf.d/default.conf
```
```
server {
    listen       0.0.0.0:80;
    server_name  _;
    charset      utf-8;

    root /var/www/public;
}
```
  Webページを配信するためのファイルを置くディレクトリを作成する  
```
mkdir public
mkdir public/image
```

## 4.各種設定ファイルの記述
1. Dockerfileの作成と設定  
  dockerコンテナ内の環境をカスタマイズしていくため，独自にイメージをビルドし，コンテナを作成する。    
  Dockerfileの作成（カレントディレクトリに作成）  
```
vim Dockerfile
```
  Dockerfileの中身  
```
FROM php:8.0-fpm-alpine AS php
RUN apk add -U --no-cache curl-dev
RUN docker-php-ext-install curl
RUN apk add autoconf g++ make
RUN pecl install apcu && docker-php-ext-enable apcu

RUN docker-php-ext-install exif

RUN docker-php-ext-install pdo_mysql

RUN install -o www-data -g www-data -d /var/www/public/image

RUN echo -e "post_max_size = 5M\nupload_max_filesize = 5M" >> ${PHP_INI_DIR}/php.ini
```
  Dockerfileを編集した後は、設定を反映させるためのコマンドを実行  
  (docker-compose up している場合は一度終了してから)  
```
docker-compose build
```

2. docker-compose.ymlの設定
```
vim docker-compose.yml
```
  中身
```
version: "3"

services:
  web:
    image: nginx:latest
    ports:
      - 80:80
    volumes:
      - ./nginx/conf.d/:/etc/nginx/conf.d/
      - ./public/:/var/www/public/
      - image:/var/www/public/image/
    depends_on:
      - php
  php:
    container_name: php
    build:
      context: .
      target: php
    volumes:
      - ./public/:/var/www/public/
      - image:/var/www/public/image/
  mysql:
    container_name: mysql
    image: mysql:5.7
    environment:
      MYSQL_DATABASE: BBS_2021
      MYSQL_ALLOW_EMPTY_PASSWORD: 1
      TZ: Asia/Tokyo
    volumes:
      - mysql:/var/lib/mysql
    command: >
      mysqld
      --character-set-server=utf8mb4
      --collation-server=utf8mb4_unicode_ci
      --max_allowed_packet=4MB

volumes:
  image:
    driver: local
  mysql:
    driver: local
```

3. nginxの設定
```
vim nginx/conf.d/default.conf
```
  中身  
```
server {
    listen       0.0.0.0:80;
    server_name  _;
    charset      utf-8;
    client_max_body_size 6M;

    root /var/www/public;

    location ~ \.php$ {
        fastcgi_pass  php:9000;
        fastcgi_index index.php;
        fastcgi_param SCRIPT_FILENAME  $document_root$fastcgi_script_name;
        include       fastcgi_params;
    }
}
```

## 5.データベースの設定
  dockerコンテナ内のmysqlサーバーにmysqlコマンドで接続する場合は，以下のコマンドを実行する  
```
docker exec -it mysql mysql BBS_2021
```
  中身  
```
CREATE TABLE `bbs_entries` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    `body` TEXT NOT NULL,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP
);
```
  画像を保存するために追加  
```
ALTER TABLE `bbs_entries` ADD COLUMN image_filename TEXT DEFAULT NULL;
```

## 6.ソースコード
  publicにbbs.php、editform.phpの2つのファイルを作成  
  bbs.php(メインのページ)を作成  
```
vim public/bbs.php
```
  中身  
  (URL) https://github.com/turkey014/BBS_2021/blob/main/public/bbs.php   
  editform.php(投稿した内容を編集するページ）を作成  
```
vim public/editform.php
```
  中身  
  (URL)


## 7.動作確認
  docker-compose upを行い、  
  EC2インスタンスのIPアドレス/bbs.phpにアクセスして動作確認を行う。  
