# Production Deployment — AWS ECR + EC2

This guide covers deploying **ecommerce-auth-service** to production using:

- **AWS ECR** — store Docker images (`auth` + `nginx`)
- **AWS RDS** — PostgreSQL database
- **AWS ElastiCache** (recommended) or Redis — cache, sessions, queues
- **EC2** — run containers via `deploy.sh`

## Architecture

```
Internet
   │
   ▼
EC2 :8080 ──► auth-nginx (ECR microservices/nginx)
                   │
                   ▼
              app (ECR microservices/auth, PHP-FPM :9000)
                   │
         ┌─────────┴─────────┐
         ▼                   ▼
    AWS RDS              ElastiCache
   (PostgreSQL)            (Redis)
```

| Container | ECR repository | Host port |
|-----------|----------------|-----------|
| PHP-FPM app | `microservices/auth` | none (internal) |
| Nginx | `microservices/nginx` | **8080** → 80 |

The app container **must** be named `app` — nginx proxies to `app:9000`.

---

## Prerequisites

### AWS resources

1. **ECR repositories** (create once):

```bash
aws ecr create-repository --repository-name microservices/auth   --region us-east-1
aws ecr create-repository --repository-name microservices/nginx  --region us-east-1
```

2. **RDS PostgreSQL** — database created, security group allows port **5432** from the EC2 instance.

3. **ElastiCache Redis** (recommended) — security group allows port **6379** from EC2.  
   Set `REDIS_HOST` in `/opt/.env` to the ElastiCache endpoint.

4. **EC2 instance** with:
   - Amazon Linux 2023 or Ubuntu
   - Docker installed
   - AWS CLI installed
   - IAM role with ECR pull permissions (`ecr:GetAuthorizationToken`, `ecr:BatchGetImage`)
   - Security group: inbound **8080** (or 80 behind ALB)

### Local machine (build & push)

- Docker
- AWS CLI configured (`aws configure`)
- IAM user/role with ECR push permissions

---

## Step 1 — Create production `.env` on EC2

On the **EC2 server**, create `/opt/.env` from the template:

```bash
sudo mkdir -p /opt
sudo cp .env.production.example /opt/.env
sudo nano /opt/.env
sudo chmod 600 /opt/.env
```

Fill in at minimum:

| Variable | Example |
|----------|---------|
| `APP_KEY` | `base64:...` from `php artisan key:generate --show` |
| `APP_URL` | `http://54.x.x.x:8080` or your domain |
| `DB_HOST` | RDS endpoint |
| `DB_DATABASE` | `ecommerce_auth` |
| `DB_USERNAME` | RDS user |
| `DB_PASSWORD` | RDS password |
| `REDIS_HOST` | ElastiCache endpoint |
| `PASSPORT_PRIVATE_KEY` | Single-line PEM with `\n` |
| `PASSPORT_PUBLIC_KEY` | Single-line PEM with `\n` |

**Important:**

- Do **not** put inline comments on value lines — Docker `--env-file` may include them in the value.
- Do **not** commit `/opt/.env` or real secrets to git.
- Generate `APP_KEY` once and keep it stable across redeploys.
- Generate Passport keys once; all app instances must share the same keys.

Copy template from repo:

```bash
# From your laptop — upload template to EC2
scp .env.production.example ec2-user@YOUR_EC2_IP:/tmp/
# On EC2
sudo cp /tmp/.env.production.example /opt/.env
```

---

## Step 2 — Build and push images to ECR

Run from your **development machine** (project root).

### 2a. Login to ECR

```bash
export AWS_REGION=us-east-1
export AWS_ACCOUNT_ID=975049961565
export ECR=${AWS_ACCOUNT_ID}.dkr.ecr.${AWS_REGION}.amazonaws.com

aws ecr get-login-password --region $AWS_REGION | \
  docker login --username AWS --password-stdin $ECR
```

### 2b. Build images

```bash
# PHP-FPM app
make build-production

# Nginx reverse proxy
make build-nginx
```

Or without Make:

```bash
docker build --target production -t ecommerce-auth-service:latest .
docker build -f docker/nginx/Dockerfile -t ecommerce-auth-service-nginx:latest .
```

### 2c. Tag and push

```bash
docker tag ecommerce-auth-service:latest       $ECR/microservices/auth:latest
docker tag ecommerce-auth-service-nginx:latest $ECR/microservices/nginx:latest

docker push $ECR/microservices/auth:latest
docker push $ECR/microservices/nginx:latest
```

Or use Make (after adding tags):

```bash
make push-ecr
```

### 2d. Verify in ECR

```bash
aws ecr describe-images --repository-name microservices/auth  --region us-east-1
aws ecr describe-images --repository-name microservices/nginx --region us-east-1
```

---

## Step 3 — Deploy on EC2

Copy `deploy.sh` to the server (once):

```bash
scp deploy.sh ec2-user@YOUR_EC2_IP:/home/ec2-user/
```

On **EC2**:

```bash
chmod +x deploy.sh
./deploy.sh
```

`deploy.sh` will:

1. Login to ECR and **pull** latest images
2. Create Docker network `auth-net`
3. Remove old `app` and `auth-nginx` containers
4. Start **app** (PHP-FPM) with `/opt/.env`
5. Start **nginx** on port **8080**

Optional overrides:

```bash
ENV_FILE=/opt/.env NGINX_PORT=8080 ./deploy.sh
```

---

## Step 4 — Verify

On EC2:

```bash
docker ps
docker logs -f app
docker logs -f auth-nginx
curl http://localhost:8080/health
```

Expected app log flow:

1. `PostgreSQL is ready`
2. `Running database migrations`
3. `Initialisation complete`
4. `Starting process`

From your browser:

```
http://YOUR_EC2_PUBLIC_IP:8080/health
```

---

## Redeploy (new code)

After pushing new images to ECR:

```bash
# On EC2 only — pulls latest and recreates containers
./deploy.sh
```

You do **not** need to rebuild on EC2. Images are pulled from ECR.

---

## Troubleshooting

### Stuck on `Waiting for PostgreSQL at postgres:5432`

- `/opt/.env` is missing or `DB_HOST` is wrong.
- RDS security group does not allow EC2 on port 5432.
- Inline comment on `DB_HOST` line broke the hostname.

```bash
docker exec app env | grep ^DB_
```

### Nginx 502 Bad Gateway

- App container not named `app`.
- App not on `auth-net` network.
- App still starting (check `docker logs app`).

### Redis connection errors

- Set `REDIS_HOST` to ElastiCache endpoint (not `redis`) when not running a Redis container.
- ElastiCache security group must allow EC2 on port 6379.

### `network auth-net not found`

- Run `./deploy.sh` — it creates the network automatically.
- Or manually: `docker network create auth-net`

### Old code after deploy

- `deploy.sh` pulls latest from ECR. Ensure you **pushed** new images before redeploying.
- Confirm digest: `docker inspect 975049961565.dkr.ecr.us-east-1.amazonaws.com/microservices/auth:latest`

---

## Security checklist

- [ ] `/opt/.env` mode `600`, owned by root or deploy user
- [ ] `APP_DEBUG=false` in production
- [ ] RDS and ElastiCache not publicly accessible
- [ ] EC2 security group limits port 8080 to trusted IPs or ALB only
- [ ] Passport keys and `APP_KEY` stored in AWS Secrets Manager (recommended for production)
- [ ] ECR image scanning enabled

---

## Related files

| File | Purpose |
|------|---------|
| `.env.production.example` | Template for `/opt/.env` on EC2 |
| `deploy.sh` | Pull from ECR and start containers on EC2 |
| `docker/nginx/Dockerfile` | Standalone nginx image build |
| `Dockerfile` (`production` target) | PHP-FPM app image build |
| `Makefile` | `build-production`, `build-nginx`, `push-ecr` |

For local Docker Compose development, see [DOCKER.md](../DOCKER.md).
