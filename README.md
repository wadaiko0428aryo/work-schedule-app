# Work Schedule App
- 勤怠管理アプリです。
- 従業員が会員登録すると、勤怠打刻操作が可能になり、自身の勤怠情報等を閲覧、修正申請が可能です。
- 勤怠管理者（店長等）はログインすると、各従業員の勤怠情報や登録情報の閲覧、修正申請の承認等を操作できます。

## アプリケーションURL
Http://localhost/attendance

## 機能一覧
###従業員画面
- 会員登録・ログイン・ログアウト
- メール認証（mailhog）
- 勤怠打刻機能
- 勤怠一覧表示
- 勤怠詳細表示
- 勤怠修正申請
- 申請中、承認済み一覧表示
###管理者画面
- ログイン・ログアウト
- 当日の勤怠一覧表示
- 従業員一覧表示
- 従業員別勤怠一覧表示
- 勤怠詳細画面表示
- 勤怠情報修正機能
- 申請中・承認済み一覧表示
- 承認機能
- CSV出力機能

## 使用技術
- Laravel Framework 8.x
- PHP7.4.9
- MySQL8.0.26
- JavaScript
- mailhog

## ER図
![image](https://github.com/user-attachments/assets/f53317ae-f621-46a1-8ffd-fe5b9449b853)


## 環境構築
### Dockerビルド

1. クローン作成
> git clone git@github.com:wadaiko0428aryo/frea-market.git

2. DockerDesktopアプリを立ち上げる

3. コンテナをビルドして起動
> docker-compose up -d --build

### Laravel環境構築
1. 実行中の PHP コンテナの中に入る
> docker-compose exec php bash

2.Composer を使用した依存関係のインストール
> composer install

3.「.env.example」ファイルをコピーして「.env」ファイルを作成
> cp .env.example .env

4..envに以下の環境変数を追加
> - DB_CONNECTION=mysql
> - DB_HOST=mysql
> - DB_PORT=3306
> - DB_DATABASE=laravel_db
> - DB_USERNAME=laravel_user
> - DB_PASSWORD=laravel_pass

5. .envにmailhogの設定を追加
> - MAIL_FROM_ADDRESS="test@example.com"
> - MAIL_FROM_NAME="勤怠アプリ"

＜ここから！！！＞

   
6. アプリケーションキーの作成
> php artisan key:generate

7. マイグレーションの実行
> php artisan migrate

8. シーディングの実行
> php artisan db::seed


※メールの設定は必要に応じて行ってください。



URL
開発環境：http://localhost/attendance
phpMyAdmin:：http://localhost:8080/
