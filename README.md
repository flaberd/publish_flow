# publish_flow

Laravel 13 + Livewire + Tailwind CSS.

## Local development

```bash
composer install
npm install
cp .env.example .env
php artisan key:generate
php artisan migrate
composer dev   # serve + queue:listen + logs + vite, all together
```

## Production deployment

Docker / Docker Compose / GitHub Actions / GHCR / EC2 — see
[`docs/deployment.md`](docs/deployment.md) for the full setup: Dockerfile,
`compose.yaml`, Nginx config, CI/CD workflow, required secrets, EC2
provisioning, deploy flow, rollback, and the persistent-storage strategy for
MySQL and uploaded/generated video files.
