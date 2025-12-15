docker run --rm `
  --network cliente-pedidos_default `
  -e SONAR_TOKEN=$SONAR_TOKEN `
  -v "${PWD}:/src" `
  -w /src/auth-api `
  mcr.microsoft.com/dotnet/sdk:10.0 `
  bash -lc '
    dotnet tool install --global dotnet-sonarscanner --version 9.0.0 &&
    export PATH="/root/.dotnet/tools:$PATH" &&
    dotnet sonarscanner begin \
      /k:"cliente-pedidos-auth-api" \
      /d:sonar.host.url="http://sonarqube:9000" \
      /d:sonar.login="$SONAR_TOKEN" \
      /d:sonar.scanner.scanAll=false \
      /d:sonar.cs.opencover.reportsPaths="../AuthApi.Tests/coverage.opencover.xml" \
      /d:sonar.exclusions="**/Migrations/**,**/docker/**,**/app/**,**/*.yml,**/*.yaml,**/Dockerfile" \
      /d:sonar.coverage.exclusions="**/Program.cs" &&
    dotnet test ../AuthApi.Tests/AuthApi.Tests.csproj \
      /p:CollectCoverage=true \
      /p:CoverletOutput=../AuthApi.Tests/coverage.opencover.xml \
      /p:CoverletOutputFormat=opencover &&
    dotnet build AuthApi.csproj &&
    dotnet sonarscanner end /d:sonar.login="$SONAR_TOKEN"
  '
