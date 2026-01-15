# FarmOS Allow Any Domain

This custom module removes email domain restrictions from FarmOS user registration.

## Installation

1. Copy the `farmos_allow_any_domain` folder to your FarmOS `modules/custom/` directory
2. Enable the module: `drush en farmos_allow_any_domain`
3. Clear cache: `drush cr`

## What it does

- Removes the `_user_validate_mail_domain` validation from the user registration form
- Allows users to register with any email domain (not just middleworldfarms.org)

## Security Note

This module removes domain restrictions, so use with caution in production environments. Consider enabling it only for demo/development instances.