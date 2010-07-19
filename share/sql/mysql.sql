CREATE TABLE channels (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(255) NOT NULL, description VARCHAR(255) NOT NULL, indicator VARCHAR(255) NOT NULL, resolution INT NOT NULL, cost NUMERIC(5, 2) NOT NULL, uuid VARCHAR(36) NOT NULL, PRIMARY KEY(id)) ENGINE = InnoDB;
CREATE TABLE data (timestamp BIGINT NOT NULL, channel_id INT DEFAULT NULL, value NUMERIC(10, 5) NOT NULL, PRIMARY KEY(timestamp)) ENGINE = InnoDB;
CREATE TABLE groups (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(255) NOT NULL, description VARCHAR(255) NOT NULL, uuid VARCHAR(36) NOT NULL, PRIMARY KEY(id)) ENGINE = InnoDB;
CREATE TABLE groups_channel (group_id INT NOT NULL, channel_id INT NOT NULL, PRIMARY KEY(group_id, channel_id)) ENGINE = InnoDB;
CREATE TABLE groups_groups (parent_id INT NOT NULL, child_id INT NOT NULL, PRIMARY KEY(parent_id, child_id)) ENGINE = InnoDB;
CREATE TABLE users (id INT AUTO_INCREMENT NOT NULL, email VARCHAR(255) NOT NULL, password VARCHAR(255) NOT NULL, uuid VARCHAR(36) NOT NULL, PRIMARY KEY(id)) ENGINE = InnoDB;
CREATE TABLE groups_users (user_id INT NOT NULL, group_id INT NOT NULL, PRIMARY KEY(user_id, group_id)) ENGINE = InnoDB;
ALTER TABLE data ADD FOREIGN KEY (channel_id) REFERENCES channels(id);
ALTER TABLE groups_channel ADD FOREIGN KEY (group_id) REFERENCES groups(id);
ALTER TABLE groups_channel ADD FOREIGN KEY (channel_id) REFERENCES channels(id);
ALTER TABLE groups_groups ADD FOREIGN KEY (parent_id) REFERENCES groups(id);
ALTER TABLE groups_groups ADD FOREIGN KEY (child_id) REFERENCES groups(id);
ALTER TABLE groups_users ADD FOREIGN KEY (user_id) REFERENCES users(id);
ALTER TABLE groups_users ADD FOREIGN KEY (group_id) REFERENCES groups(id)
