#
# Table structure for table 'tt_content'
#
CREATE TABLE tt_content (
	tx_testsitepacke_parent int(11) DEFAULT '0' NOT NULL,
	tx_testsitepacke_kids int(11) DEFAULT '0' NOT NULL,
	tablenames varchar(255) DEFAULT '' NOT NULL,
	KEY tx_testsitepacke_parent (tx_testsitepacke_parent)
);
