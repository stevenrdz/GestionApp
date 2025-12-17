# GestionApp

# Microservicio de Autenticación (.NET) + Stack Docker

Este proyecto forma parte de la prueba técnica y expone lo siguiente:

- Registro de usuarios
- Login (JWT + refresh tokens)
- CRUD Clientes y Pedidos
- Documentación con **Swagger**
- Integración con **SQL Server** en Docker
- Pruebas unitarias con **xUnit** y **EF Core InMemory** en .NET 10
- Pruebas unitarias con **PHPUniy** en Symfony 7.4

Opcionalmente, el `docker-compose.yml` también levanta otros servicios (por ejemplo, Symfony/PHP, Nginx y SonarQube) para completar el ecosistema.

---

## 1. Arquitectura general

### Microservicio `auth-api` (.NET)

- **Framework**: .NET 10 (ASP.NET Core)
- **Base de datos**: SQL Server (contenedor `db`)
- **ORM**: Entity Framework Core
- **Autenticación**: JWT (Bearer)
- **Hash de contraseñas**: BCrypt
- **Pruebas**: xUnit + EF Core InMemory

### Microservicio `app` (Symfony)

- **Framework**: Symfony 7.4 (PHP 8.4)
- **Base de datos**: SQL Server (contenedor `db`)
- **ORM**: Doctrine
- **Autenticación**: JWT (Bearer)
- **Pruebas**: PHPUnit

### Frontend `gestion-app` (Angular)

- **Framework**: Angular 21
- **Pruebas**: Karma + Jasmine

### Servicios en Docker (según `docker-compose.yml`)

- `authapi` → Microservicio de autenticación
- `db` → SQL Server 2022 (Developer)
- `php` / `web` → Backend Symfony + Nginx (si se requiere para otros microservicios)
- `sonarqube` / `sonar-db` → Análisis estático de calidad de código

---

## 2. Requisitos previos

- **Docker** y **Docker Compose** instalados
- **.NET SDK 8/10** (solo necesario si se quiere compilar localmente o ejecutar pruebas unitarias fuera de Docker)
- Acceso a consola (PowerShell, Bash, etc.)

---

## 3. Configuración de entorno

Las credenciales y secretos sensibles **no están hardcodeados en el código**, sino gestionados vía:

- `appsettings.json` (sin credenciales reales)
- Variables de entorno definidas en `docker-compose.yml`

Variables clave:

- `JwtSecret`  
  Clave simétrica para firmar los JWT.

- `ConnectionStrings__DefaultConnection`  
  Cadena de conexión a SQL Server. 

---

## 4. Variables de entorno

Copiar el archivo `.env.example` a `.env` y rellenar:

- MSSQL_SA_PASSWORD
- JWT_SECRET

Dentro del proyecto app, copiar el archivo `.env.example` a `.env` y rellenar:

- APP_ENV
- APP_SECRET
- APP_SHARE_DIR
- DEFAULT_URI
- DB_HOST
- DB_PORT
- DB_NAME
- DB_USER
- DB_PASSWORD
- DB_TRUST_SERVER_CERT
- APP_ENCRYPTION_KEY

la `APP_ENCRYPTION_KEY` es una clave simétrica de 256 bits

## 5. Ejecución de docker

- En consola bash ejecutar este comando: dos2unix docker/php/entrypoint.sh
- docker compose build
- docker compose up -d
- docker compose exec php bash

Al ingresar a la carpeta app, verificar que .env exista y tenga los campos del paso 4. caso contrario no se migrará la tabla.

- composer install
- php bin/console cache:clear
- exit
- cd gestion-app

Al ingresar a la carpeta gestion-app, asegurarar tener descargado nodeJs y npm

- npm install
- npm install -g @angular/cli
- ng serve --proxy-config proxy.conf.json

## 6. Enlaces

- http://localhost:8080/api/doc (Symfony)
- http://localhost:5000/swagger/index.html (.NET)
- http://localhost:4200/auth/login (Angular)
- http://localhost:9000/ (SonarQube)

## 7. Pruebas unitarias

### .NET 10

Remover ${TOKEN} y agregar la key del proyecto creado en sonarqube

Ejecutar el comando para generar coverage:

dotnet test auth-api-test/AuthApi.Tests.csproj /p:CollectCoverage=true /p:CoverletOutput=../coverage/auth-api/coverage.opencover.xml /p:CoverletOutputFormat=opencover

Generar key en sonarqube y ejecutar el siguiente comando:

docker run --rm `
  --network gestionapp_default `
  -e SONAR_TOKEN="${TOKEN}" `
  -v "${PWD}:/src" `
  mcr.microsoft.com/dotnet/sdk:10.0 `
  bash -lc 'cd /src && \
    dotnet tool install --global dotnet-sonarscanner --version 9.0.0 && \
    export PATH="/root/.dotnet/tools:$PATH" && \
    dotnet sonarscanner begin \
      /k:"auth-api" \
      /d:sonar.host.url="http://sonarqube:9000" \
      /d:sonar.token="$SONAR_TOKEN" \
      /d:sonar.scanner.scanAll=false \
      /d:sonar.cs.opencover.reportsPaths="coverage/auth-api/coverage.opencover.xml" \
      /d:sonar.exclusions="**/Migrations/**,**/auth-api-test/**,**/script/**,**/gestion-app/**,**/docker/**,**/app/**,**/*.yml,**/*.yaml,**/Dockerfile" \
      /d:sonar.coverage.exclusions="**/Program.cs" && \
    mkdir -p coverage/auth-api && \
    dotnet test auth-api-test/AuthApi.Tests.csproj \
      /p:CollectCoverage=true \
      /p:CoverletOutput=../coverage/auth-api/coverage.opencover.xml \
      /p:CoverletOutputFormat=opencover && \
    dotnet build auth-api/AuthApi.csproj && \
    dotnet sonarscanner end /d:sonar.token="$SONAR_TOKEN"'


### Symfony 7.4

Ejecutar el comando para generar coverage:

XDEBUG_MODE=coverage php bin/phpunit --coverage-clover var/coverage.xml

Generar key en sonarqube y ejecutar el siguiente comando:

docker run --rm `
  --network gestionapp_default `
  -e SONAR_HOST_URL="http://sonarqube:9000" `
  -e SONAR_TOKEN="${TOKEN}" `
  -v "${PWD}\app:/usr/src" `
  sonarsource/sonar-scanner-cli
