docker run --rm `
  --network gestionapp_default `
  -e SONAR_HOST_URL="http://sonarqube:9000" `
  -e SONAR_TOKEN="$env:SONAR_TOKEN" `
  -v "${PWD}\app:/usr/src" `
  sonarsource/sonar-scanner-cli
