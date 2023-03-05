## How to run the application
- Ensure you have Docker
- Ensure you have `php > 8.1`, `ext-url`, `unzip`
- Clone the repository
- run `cd verification-app`
- run `cp .env.example .env`
- run `composer upgrade`
- run `php artisan key:generate`
- run `./vendor/bin/sail up` (ensure Docker is running)
- run `./vendor/bin/sail php artisan migrate`
- run `./vendor/bin/sail npm i`
- run `./vendor/bin/sail npm run build`
- go to http://localhost/register and register a new account with an email & password
- you will be brought to the dashboard, or if your session has ended go to http://localhost/login with the email/password you registered previously.
- drag a valid JSON into the drop zone to verify

## API documentation
https://docs.google.com/document/d/1ulwuCODbldf5Am-qm1TyoA-401iyBfLQkDwsseDOFD8/edit?usp=sharing

## Tech design considerations
https://docs.google.com/document/d/1WhxCszGPvEWGVTa0mzFNSCj74nmw9yQZVNLbYB_3PTg/edit?usp=sharing
