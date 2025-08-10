DROP TABLE IF EXISTS hackathon10_users;
DROP TABLE IF EXISTS hackathon10_logs;
DROP TABLE IF EXISTS hackathon10_animals;
DROP TABLE IF EXISTS hackathon10_animal_description;

CREATE TABLE hackathon10_users (
    id SERIAL PRIMARY KEY,
    username TEXT,
    password TEXT, -- password_hash()で暗号化して保存
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);


CREATE TABLE hackathon10_logs (
    log_id SERIAL PRIMARY KEY,
    user_id INTEGER NOT NULL REFERENCES hackathon10_users(id) ON DELETE CASCADE,
    animal_id INTEGER NOT NULL REFERENCES hackathon10_animals(animal_id) ON DELETE CASCADE,
    role TEXT NOT NULL,   -- "user" または "assistant"
    message TEXT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);


CREATE TABLE hackathon10_animals (
    animal_id SERIAL PRIMARY KEY,
    name TEXT, -- 動物名
    description TEXT -- 説明やキャラ設定
);

CREATE TABLE hackathon10_animal_description (
    animal_description_id SERIAL PRIMARY KEY,
    animal_id  INTEGER NOT NULL REFERENCES hackathon10_animals(animal_id) ON DELETE CASCADE,
    user_id INTEGER NOT NULL REFERENCES hackathon10_users(id) ON DELETE CASCADE,
    description TEXT NOT NULL, -- 説明やキャラ設定
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);