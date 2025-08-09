DROP TABLE IF EXISTS hackathon10_users;

-- ユーザー情報テーブル
CREATE TABLE hackathon10_users (
    id INT PRIMARY KEY,
    username TEXT NOT NULL,
    password TEXT NOT NULL
);