CREATE TABLE data (id INT AUTO_INCREMENT NOT NULL, channel_id INT DEFAULT NULL, timestamp BIGINT NOT NULL, value DOUBLE PRECISION NOT NULL, INDEX data_channel_id_idx (channel_id), UNIQUE INDEX ts_uniq (channel_id, timestamp), PRIMARY KEY(id)) ENGINE = InnoDB 
