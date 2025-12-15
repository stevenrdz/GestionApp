using System.IdentityModel.Tokens.Jwt;
using System.Linq;
using System.Security.Claims;
using AuthApi.Models;
using AuthApi.Service;
using FluentAssertions;
using Xunit;

namespace AuthApi.Tests
{
    public class JwtServiceTests
    {
        [Fact]
        public void GenerateAccessToken_ShouldContainExpectedClaims()
        {
            // Arrange
            const string secret = "0123456789ABCDEF0123456789ABCDEF";
            const string issuer = "auth-api";
            const string audience = "symfony-api";

            var jwtService = new JwtService(secret, issuer, audience);

            var user = new User
            {
                Id = Guid.NewGuid(),
                UserName = "steven",
                Email = "steven@example.com",
                Role = "USER"
            };

            // Act
            var token = jwtService.GenerateAccessToken(user);

            // Assert
            token.Should().NotBeNullOrWhiteSpace();

            var handler = new JwtSecurityTokenHandler();
            var jwt = handler.ReadJwtToken(token);

            jwt.Claims.First(c => c.Type == JwtRegisteredClaimNames.Sub).Value
                .Should().Be(user.Id.ToString());

            jwt.Claims.First(c => c.Type == ClaimTypes.Name).Value
                .Should().Be(user.UserName);

            jwt.Claims.First(c => c.Type == ClaimTypes.Role).Value
                .Should().Be(user.Role);
        }

        [Fact]
        public void GenerateRefreshToken_ShouldReturnNonEmptyRandomString()
        {
            // Act
            var token1 = JwtService.GenerateRefreshToken();
            var token2 = JwtService.GenerateRefreshToken();

            // Assert
            token1.Should().NotBeNullOrWhiteSpace();
            token2.Should().NotBeNullOrWhiteSpace();
            token1.Should().NotBe(token2);
        }
    }
}
