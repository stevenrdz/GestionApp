FROM mcr.microsoft.com/dotnet/aspnet:10.0 AS base

RUN apt-get update && apt-get install -y tzdata && \
    ln -fs /usr/share/zoneinfo/America/Guayaquil /etc/localtime && \
    dpkg-reconfigure -f noninteractive tzdata && \
    rm -rf /var/lib/apt/lists/*

ENV TZ=America/Guayaquil

WORKDIR /app
EXPOSE 5000

FROM mcr.microsoft.com/dotnet/sdk:10.0 AS build
WORKDIR /src
COPY . .
RUN dotnet restore
RUN dotnet publish -c Release -o /app/publish

FROM base AS final
WORKDIR /app
COPY --from=build /app/publish .
ENTRYPOINT ["dotnet", "AuthApi.dll"]
