#
# Table structure for table 'sys_file_storage'
#
CREATE TABLE sys_file_storage (
    soft_quota               bigint(20) unsigned DEFAULT '0' NOT NULL,
    hard_limit               bigint(20) unsigned DEFAULT '0' NOT NULL,
    current_usage            bigint(20) unsigned DEFAULT '0' NOT NULL,
    quota_warning_threshold  smallint(3) DEFAULT '0' NOT NULL,
    quota_warning_recipients text
);
