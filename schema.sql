CREATE TABLE domains (
    id INT AUTO_INCREMENT PRIMARY KEY,
    url VARCHAR(255) NOT NULL,
    valid_to DATE NULL,
    days_left INT NULL,
    last_checked DATETIME NULL
);
