# COACHTECH 模擬案件② 勤怠管理アプリ

## アプリケーション概要

本アプリケーションは、企業向けの **勤怠管理アプリ** です。

### 一般ユーザー

- 出勤 / 休憩開始 / 休憩終了 / 退勤の打刻
- 勤怠一覧の確認
- 勤怠詳細の確認
- 修正申請の作成
- 修正申請一覧の確認

### 管理者ユーザー

- 全ユーザーの勤怠一覧確認
- スタッフ別月次勤怠確認
- 修正申請の承認 / 却下

---

## 環境構築

本アプリケーションは Docker を使用して Laravel / MySQL 環境を構築します。
以下の手順に沿ってセットアップしてください。

### Docker ビルド

- git clone https://github.com/amihira03/coachtech-attendance.git
- cd coachtech-attendance
- cp src/.env.example src/.env
- docker compose up -d --build

### Laravel 環境構築

- docker compose exec php bash
- composer install
- php artisan key:generate
- php artisan migrate:fresh --seed

## 初期データについて

### ログイン情報

#### 一般ユーザー

- メールアドレス：user@example.com
- パスワード：password

#### 管理者ユーザー

- メールアドレス：admin@example.com
- パスワード：password

※ 上記ユーザーはシーディングによって作成されます。
※ その他、一般ユーザーが複数名ランダムで作成されます。

## 開発環境（URL）

### 一般ユーザー

- 会員登録：http://localhost/register
- ログイン：http://localhost/login
- 出勤登録：http://localhost/attendance
- 勤怠一覧：http://localhost/attendance/list
- 勤怠詳細：http://localhost/attendance/detail/{id}
- 申請一覧：http://localhost/stamp_correction_request/list

### 管理者

- ログイン：http://localhost/admin/login
- 勤怠一覧：http://localhost/admin/attendance/list
- 勤怠詳細：http://localhost/admin/attendance/{id}
- スタッフ一覧：http://localhost/admin/staff/list
- スタッフ別勤怠一覧：http://localhost/admin/attendance/staff/{id}
- 申請一覧：http://localhost/stamp_correction_request/list
- 修正申請承認：http://localhost/stamp_correction_request/approve/{id}

### その他

- phpMyAdmin：http://localhost:8080
- Mailhog：http://localhost:8025

## 使用技術（実行環境）

- PHP：8.1（php:8.1-fpm）
- Laravel：8.75
- MySQL：8.0.26（platform: linux/amd64）
- Nginx：1.21.1
- Docker：Docker Desktop
- 認証：Laravel Fortify
- バリデーション：FormRequest

---

## テスト

- docker compose exec php bash
- php artisan test

---

## ER 図

![ER図](./er-diagram.drawio.png)
