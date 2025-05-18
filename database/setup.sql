
CREATE TABLE motel (
    id INT(10) AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    description TEXT,
    price INT(11),
    area INT(11),
    count_view INT(11) DEFAULT 0,
    address VARCHAR(255),
    latlng VARCHAR(255),
    images VARCHAR(255),
    user_id INT(10),
    category_id INT(11),
    district_id INT(11),
    utilities VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    phone VARCHAR(255),
    approve INT(11) DEFAULT 0
);
