ALTER TABLE `#__jdidealgateway_logs` ADD `currency` CHAR(15)  NOT NULL  DEFAULT 'EUR'  COMMENT 'The currency the order is in'  AFTER `quantity`;