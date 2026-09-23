CREATE DATABASE IF NOT EXISTS sushant CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE sushant;
DROP TABLE IF EXISTS issues; DROP TABLE IF EXISTS books; DROP TABLE IF EXISTS users;
CREATE TABLE users (id INT AUTO_INCREMENT PRIMARY KEY,name VARCHAR(100) NOT NULL,email VARCHAR(100) NOT NULL UNIQUE,mobile VARCHAR(20),age INT,password VARCHAR(255) NOT NULL,role ENUM('superadmin','admin','user') NOT NULL DEFAULT 'user',created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,reset_otp VARCHAR(10),reset_otp_expires DATETIME);
CREATE TABLE books (id INT AUTO_INCREMENT PRIMARY KEY,title VARCHAR(150) NOT NULL,author VARCHAR(100) NOT NULL,category VARCHAR(80) NOT NULL,isbn VARCHAR(30) UNIQUE,quantity INT NOT NULL DEFAULT 1,available INT NOT NULL DEFAULT 1,created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP);
CREATE TABLE issues (id INT AUTO_INCREMENT PRIMARY KEY,book_id INT NOT NULL,user_id INT NOT NULL,issue_date DATE NOT NULL,due_date DATE NOT NULL,return_date DATE DEFAULT NULL,status ENUM('Issued','Returned','Overdue') DEFAULT 'Issued',fine DECIMAL(10,2) DEFAULT 0,created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,FOREIGN KEY(book_id) REFERENCES books(id) ON DELETE CASCADE,FOREIGN KEY(user_id) REFERENCES users(id) ON DELETE CASCADE);
INSERT INTO users(name,email,mobile,age,password,role) VALUES
('Super Admin','superadmin@gmail.com','9999999999',30,'$2y$12$DQNsSgTn6tEV7L2r2Hvd9.pbJGVv9H7wnVqy0g/SPAWgvd6j3Un4a','superadmin'),
('Admin','admin@gmail.com','9999999998',28,'$2y$12$.hNp2aonVY/4xlxuFaDFjO3ukwwOuoqsZnybmGB63XnVsHU8qZYlC','admin'),
('Rahul Sharma','rahul@gmail.com','9999999997',20,'$2y$12$WMcRUOpn3JA5GWPOYS9JaO1fQ3BvQvaxVHFFu/odG.sOUNjL5XG','user'),
('Priya Verma','priya@gmail.com','9999999996',21,'$2y$12$WMcRUOpn3JA5GWPOYS9JaO1fQ3BvQvaxVHFFu/odG.sOUNjL5XG','user');
INSERT INTO books(title,author,category,isbn,quantity,available) VALUES('The Alchemist','Paulo Coelho','Fiction','9780062315007',10,7),('Ikigai','Hector Garcia','Self Help','9780143130727',8,5),('Atomic Habits','James Clear','Self Help','9780735211292',9,4);
