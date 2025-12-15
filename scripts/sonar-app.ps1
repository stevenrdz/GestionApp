docker run --rm `
  --network cliente-pedidos_default `
  -e SONAR_TOKEN=$env:SONAR_TOKEN `
  -v "${PWD}\..\app:/usr/src" `
  -w /usr/src `
  sonarsource/sonar-scanner-cli `
  -D"sonar.projectKey=cliente-pedidos-app" `
  -D"sonar.sources=src" `
  -D"sonar.host.url=http://sonarqube:9000" `
  -D"sonar.token=$SONAR_TOKEN"
