ALTER TABLE `#__ars_updatestreams` ADD COLUMN `jedid` bigint(20) NOT NULL AFTER `folder` ;
ALTER TABLE `#__ars_updatestreams` ADD INDEX `#__ars_updatestreams_jedid` (`jedid`);