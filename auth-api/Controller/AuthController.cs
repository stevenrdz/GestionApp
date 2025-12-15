using System.IdentityModel.Tokens.Jwt;
using AuthApi.Data;
using AuthApi.Dtos;
using AuthApi.Models;
using AuthApi.Service;
using Microsoft.AspNetCore.Authorization;
using Microsoft.AspNetCore.Mvc;
using Microsoft.EntityFrameworkCore;

namespace AuthApi.Controller
{
    [ApiController]
    [Route("api/auth")]
    public class AuthController : ControllerBase
    {
        private readonly AuthDbContext _db;
        private readonly JwtService _jwt;

        public AuthController(AuthDbContext db, JwtService jwt)
        {
            _db = db;
            _jwt = jwt;
        }

        /// <summary>Registro de usuario</summary>
        /// <remarks>
        /// Crea un nuevo usuario con nombre de usuario, correo y contraseña.
        /// </remarks>
        /// <response code="201">Usuario registrado correctamente.</response>
        /// <response code="400">Datos inválidos o usuario/correo ya registrados.</response>
        [HttpPost("register")]
        [ProducesResponseType(typeof(RegisterResponse), 201)]
        [ProducesResponseType(400)]
        public async Task<IActionResult> Register(RegisterRequest req)
        {
            if (!ModelState.IsValid)
            {
                return BadRequest(new
                {
                    mensaje = "Datos inválidos. Verifica la información enviada.",
                    errores = ModelState
                        .Where(m => m.Value?.Errors.Count > 0)
                        .ToDictionary(
                            kvp => kvp.Key,
                            kvp => kvp.Value!.Errors.Select(e => e.ErrorMessage).ToArray()
                        )
                });
            }

            var existe = await _db.Users.AnyAsync(u =>
                u.UserName == req.UserName || u.Email == req.Email
            );

            if (existe)
            {
                return BadRequest(new
                {
                    mensaje = "Usuario o correo electrónico ya registrados."
                });
            }

            var user = new User
            {
                UserName = req.UserName,
                Email = req.Email,
                PasswordHash = BCrypt.Net.BCrypt.HashPassword(req.Password)
            };

            _db.Users.Add(user);
            await _db.SaveChangesAsync();

            return Created(string.Empty, new RegisterResponse
            {
                Id = user.Id,
                UserName = user.UserName,
                Email = user.Email,
                Role = user.Role,
                CreatedAt = user.CreatedAt
            });
        }

        /// <summary>Inicio de sesión de usuario</summary>
        /// <remarks>
        /// Recibe usuario o correo y contraseña, valida las credenciales
        /// y devuelve un token JWT junto con un refresh token.
        /// </remarks>
        /// <response code="200">
        /// Inicio de sesión exitoso. Devuelve el token de acceso y el refresh token.
        /// </response>
        /// <response code="400">
        /// Datos inválidos. Faltan campos obligatorios o el formato es incorrecto.
        /// </response>
        /// <response code="401">
        /// Credenciales incorrectas. Usuario o contraseña no válidos.
        /// </response>
        [HttpPost("login")]
        [ProducesResponseType(typeof(LoginResponse), 200)]
        [ProducesResponseType(400)]
        [ProducesResponseType(401)]
        public async Task<IActionResult> Login(LoginRequest req)
        {
            if (!ModelState.IsValid)
            {
                return BadRequest(new
                {
                    mensaje = "Datos inválidos. Verifica la información enviada.",
                    errores = ModelState
                        .Where(m => m.Value?.Errors.Count > 0)
                        .ToDictionary(
                            kvp => kvp.Key,
                            kvp => kvp.Value!.Errors.Select(e => e.ErrorMessage).ToArray()
                        )
                });
            }

            var user = await _db.Users
                .FirstOrDefaultAsync(u =>
                    u.UserName == req.UserNameOrEmail ||
                    u.Email == req.UserNameOrEmail);

            if (user == null || !BCrypt.Net.BCrypt.Verify(req.Password, user.PasswordHash))
            {
                return Unauthorized(new
                {
                    mensaje = "Credenciales incorrectas. Usuario o contraseña no válidos."
                });
            }

            var accessToken = _jwt.GenerateAccessToken(user);
            var refreshTokenValue = JwtService.GenerateRefreshToken();

            _db.RefreshTokens.Add(new RefreshToken
            {
                UserId = user.Id,
                Token = refreshTokenValue,
                ExpiresAt = DateTime.UtcNow.AddDays(7)
            });

            await _db.SaveChangesAsync();

            return Ok(new LoginResponse
            {
                AccessToken = accessToken,
                ExpiresIn = 7200,
                RefreshToken = refreshTokenValue
            });
        }

        /// <summary>Obtiene la información del usuario autenticado</summary>
        /// <remarks>
        /// Devuelve los datos del usuario asociado al token JWT enviado en la cabecera Authorization.
        /// </remarks>
        /// <response code="200">Información del usuario autenticado.</response>
        /// <response code="401">No autorizado. Token inválido o ausente.</response>
        [Authorize]
        [HttpGet("me")]
        [ProducesResponseType(typeof(MeResponse), 200)]
        [ProducesResponseType(401)]
        public async Task<IActionResult> Me()
        {
            var sub = User.FindFirst(JwtRegisteredClaimNames.Sub)?.Value;
            if (!Guid.TryParse(sub, out var userId))
            {
                return Unauthorized(new
                {
                    mensaje = "No autorizado. El token no contiene un identificador de usuario válido."
                });
            }

            var user = await _db.Users.FindAsync(userId);
            if (user == null)
            {
                return Unauthorized(new
                {
                    mensaje = "No autorizado. El usuario no existe o fue eliminado."
                });
            }

            return Ok(new MeResponse
            {
                Id = user.Id,
                UserName = user.UserName,
                Email = user.Email,
                Role = user.Role,
                CreatedAt = user.CreatedAt
            });
        }

        /// <summary>Renueva el token de acceso usando un refresh token</summary>
        /// <remarks>
        /// Recibe un refresh token válido, lo invalida y genera un nuevo par de
        /// access token + refresh token.
        /// </remarks>
        /// <response code="200">
        /// Refresh token válido. Devuelve un nuevo token de acceso y un nuevo refresh token.
        /// </response>
        /// <response code="400">
        /// Datos inválidos. El refresh token no fue enviado o el formato es incorrecto.
        /// </response>
        /// <response code="401">
        /// Refresh token inválido, expirado o ya utilizado.
        /// </response>
        [HttpPost("refresh")]
        [ProducesResponseType(typeof(RefreshTokenResponse), 200)]
        [ProducesResponseType(400)]
        [ProducesResponseType(401)]
        public async Task<IActionResult> Refresh(RefreshTokenRequest req)
        {
            if (!ModelState.IsValid)
            {
                return BadRequest(new
                {
                    mensaje = "Datos inválidos. Verifica la información enviada.",
                    errores = ModelState
                        .Where(m => m.Value?.Errors.Count > 0)
                        .ToDictionary(
                            kvp => kvp.Key,
                            kvp => kvp.Value!.Errors.Select(e => e.ErrorMessage).ToArray()
                        )
                });
            }

            var stored = await _db.RefreshTokens
                .Include(r => r.User)
                .FirstOrDefaultAsync(r => r.Token == req.RefreshToken);

            if (stored == null || stored.RevokedAt != null || stored.ExpiresAt <= DateTime.UtcNow)
            {
                return Unauthorized(new
                {
                    mensaje = "Refresh token inválido, expirado o ya utilizado."
                });
            }

            stored.RevokedAt = DateTime.UtcNow;

            var newRefresh = JwtService.GenerateRefreshToken();
            var newAccess = _jwt.GenerateAccessToken(stored.User!);

            _db.RefreshTokens.Add(new RefreshToken
            {
                UserId = stored.UserId,
                Token = newRefresh,
                ExpiresAt = DateTime.UtcNow.AddDays(7)
            });

            await _db.SaveChangesAsync();

            return Ok(new RefreshTokenResponse
            {
                AccessToken = newAccess,
                ExpiresIn = 7200,
                RefreshToken = newRefresh
            });
        }
    }
}
