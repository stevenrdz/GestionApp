docker run --rm `
  --network gestionapp_default `
  -e SONAR_TOKEN="$env:SONAR_TOKEN" `
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
