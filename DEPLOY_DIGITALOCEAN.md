# DigitalOcean Temporary Deployment

The app specification in `.do/app.yaml` deploys the two GitHub repositories as one application:

- Vue/Ionic static site at `/`
- Laravel API at `/api`
- PostgreSQL development database

DigitalOcean assigns an HTTPS URL ending in `.ondigitalocean.app`. A custom domain can be attached later without changing the application architecture.

## Before creating the app

1. Push the deployment files to the `main` branch of `dpaul0501/manush-e-backend`.
2. Push the current frontend to the `main` branch of `dpaul0501/manush-e-frontend`.
3. Generate a production application key locally with `php artisan key:generate --show`.
4. Never commit the generated key or provider API keys.

## Create the app

1. In DigitalOcean, select **Create > App Platform**.
2. Choose **GitHub** and authorize access to both Manush-E repositories.
3. Choose **Import from App Spec** and upload `.do/app.yaml`.
4. In the `api` component settings, replace the `APP_KEY` placeholder with the generated key and keep it encrypted.
5. Add any AI provider keys as encrypted run-time variables.
6. Confirm the estimated resources before creating the app.

The first backend start runs all database migrations. It does not seed demo content automatically.

## Seed the initial library once

After the first successful deployment, open the `api` component console and run:

```bash
php artisan db:seed --class=MeasurementLibrarySeeder --force
```

If the temporary deployment should include demo users and groups, run the local demo seeder separately only after reviewing its data.

## Verify

Open these paths on the generated hostname:

```text
/up
/api/assessment-library
/
```

`/up` should return HTTP 200. The assessment library should return HTTP 401 before login, confirming that the API is running and protected.

## Add a custom domain later

Attach `manushe.monerbondhu.com` to the same App Platform application. Change `CORS_ALLOWED_ORIGINS` only if the frontend and API are later split across different origins.
