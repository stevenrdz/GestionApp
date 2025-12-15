using AuthApi.Controller;
using AuthApi.Data;
using AuthApi.Dtos;
using AuthApi.Models;
using AuthApi.Service;
using FluentAssertions;
using Microsoft.AspNetCore.Http;
using Microsoft.AspNetCore.Mvc;
using Microsoft.EntityFrameworkCore;
using System.IdentityModel.Tokens.Jwt;
using System.Security.Claims;
using Xunit;

namespace AuthApi.Tests
{
    public class AuthControllerTests
    {
        private AuthDbContext CreateInMemoryDb(string dbName)
        {
            var options = new DbContextOptionsBuilder<AuthDbContext>()
                .UseInMemoryDatabase(dbName)
                .Options;

            return new AuthDbContext(options);
        }

        private AuthController CreateController(AuthDbContext db)
        {
            const string secret = "0123456789ABCDEF0123456789ABCDEF";
            const string issuer = "auth-api";
            const string audience = "symfony-api";

            var jwtService = new JwtService(secret, issuer, audience);

            return new AuthController(db, jwtService);
        }

        [Fact]
        public async Task Register_ShouldCreateUser_WhenDataIsValid()
        {
            // Arrange
            var db = CreateInMemoryDb(nameof(Register_ShouldCreateUser_WhenDataIsValid));
            var controller = CreateController(db);

            var request = new RegisterRequest
            {
                UserName = "steven",
                Email = "steven@example.com",
                Password = "Password123!"
            };

            // Act
            var result = await controller.Register(request);

            // Assert
            var created = result as CreatedResult;
            created.Should().NotBeNull();
            created!.StatusCode.Should().Be(StatusCodes.Status201Created);

            var response = created.Value as RegisterResponse;
            response.Should().NotBeNull();
            response!.UserName.Should().Be("steven");
            response.Email.Should().Be("steven@example.com");

            db.Users.Count().Should().Be(1);
        }

        [Fact]
        public async Task Login_ShouldReturnTokens_WhenCredentialsAreValid()
        {
            // Arrange
            var db = CreateInMemoryDb(nameof(Login_ShouldReturnTokens_WhenCredentialsAreValid));

            var password = "Password123!";
            var user = new User
            {
                UserName = "steven",
                Email = "steven@example.com",
                PasswordHash = BCrypt.Net.BCrypt.HashPassword(password)
            };
            db.Users.Add(user);
            await db.SaveChangesAsync();

            var controller = CreateController(db);

            var request = new LoginRequest
            {
                UserNameOrEmail = "steven",
                Password = password
            };

            // Act
            var result = await controller.Login(request);

            // Assert
            var ok = result as OkObjectResult;
            ok.Should().NotBeNull();
            ok!.StatusCode.Should().Be(StatusCodes.Status200OK);

            var response = ok.Value as LoginResponse;
            response.Should().NotBeNull();
            response!.AccessToken.Should().NotBeNullOrWhiteSpace();
            response.RefreshToken.Should().NotBeNullOrWhiteSpace();
        }

        [Fact]
        public async Task Me_ShouldReturnUser_WhenTokenIsValid()
        {
            // Arrange
            var db = CreateInMemoryDb(nameof(Me_ShouldReturnUser_WhenTokenIsValid));

            var user = new User
            {
                UserName = "steven",
                Email = "steven@example.com",
                PasswordHash = BCrypt.Net.BCrypt.HashPassword("Password123!")
            };
            db.Users.Add(user);
            await db.SaveChangesAsync();

            var controller = CreateController(db);

            // Simular HttpContext.User con claim sub
            var claims = new[]
            {
                new Claim(JwtRegisteredClaimNames.Sub, user.Id.ToString())
            };
            var identity = new ClaimsIdentity(claims, "TestAuth");
            var principal = new ClaimsPrincipal(identity);

            controller.ControllerContext = new ControllerContext
            {
                HttpContext = new DefaultHttpContext
                {
                    User = principal
                }
            };

            // Act
            var result = await controller.Me();

            // Assert
            var ok = result as OkObjectResult;
            ok.Should().NotBeNull();
            ok!.StatusCode.Should().Be(StatusCodes.Status200OK);

            var response = ok.Value as MeResponse;
            response.Should().NotBeNull();
            response!.Id.Should().Be(user.Id);
            response.UserName.Should().Be(user.UserName);
        }

        [Fact]
        public async Task Refresh_ShouldReturnNewTokens_WhenRefreshTokenIsValid()
        {
            // Arrange
            var db = CreateInMemoryDb(nameof(Refresh_ShouldReturnNewTokens_WhenRefreshTokenIsValid));
            var controller = CreateController(db);

            var user = new User
            {
                UserName = "steven",
                Email = "steven@example.com",
                PasswordHash = BCrypt.Net.BCrypt.HashPassword("Password123!")
            };
            db.Users.Add(user);

            var refreshTokenValue = JwtService.GenerateRefreshToken(); // ← estático

            var rt = new RefreshToken
            {
                UserId = user.Id,
                Token = refreshTokenValue,
                ExpiresAt = DateTime.UtcNow.AddDays(1)
            };
            db.RefreshTokens.Add(rt);
            await db.SaveChangesAsync();

            var request = new RefreshTokenRequest
            {
                RefreshToken = refreshTokenValue
            };

            // Act
            var result = await controller.Refresh(request);

            // Assert
            var ok = result as OkObjectResult;
            ok.Should().NotBeNull();
            ok!.StatusCode.Should().Be(StatusCodes.Status200OK);

            var response = ok.Value as RefreshTokenResponse;
            response.Should().NotBeNull();
            response!.AccessToken.Should().NotBeNullOrWhiteSpace();
            response.RefreshToken.Should().NotBe(refreshTokenValue); // rotado
        }
    }
}
