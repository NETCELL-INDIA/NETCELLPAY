-- Netcell Pay: Android FCM token columns (Firebase project netcellpay-fe31a)
-- Run once on production DB if columns are missing.

ALTER TABLE `users` ADD COLUMN `android_fcm_token` TEXT NULL;
ALTER TABLE `users` ADD COLUMN `fcm_token` TEXT NULL;
ALTER TABLE `users` ADD COLUMN `device_token` TEXT NULL;

-- If a column already exists, skip that line (duplicate column error is OK).
