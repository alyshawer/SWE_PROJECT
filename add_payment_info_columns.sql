-- Add payment method and account info columns to applications table
-- This allows freelancers to specify how they want to be paid when marking job as completed

ALTER TABLE applications 
ADD COLUMN payment_method VARCHAR(50) DEFAULT NULL,
ADD COLUMN payment_account_info VARCHAR(255) DEFAULT NULL;

