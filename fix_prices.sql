-- Update mobile_subscription_plans prices to match mobile_app_plans
UPDATE mobile_subscription_plans SET price_monthly = 20000, price_yearly = 220000 WHERE slug = 'cabinet-entreprise';
UPDATE mobile_subscription_plans SET price_monthly = 5000, price_yearly = 55000 WHERE slug = 'pro';
UPDATE mobile_subscription_plans SET price_monthly = 2000, price_yearly = 22000 WHERE slug = 'etudiant';
UPDATE mobile_subscription_plans SET price_monthly = 0, price_yearly = 0 WHERE slug = 'gratuit';

-- Verify the updates
SELECT id, name, slug, price_monthly, price_yearly FROM mobile_subscription_plans ORDER BY price_monthly ASC;
