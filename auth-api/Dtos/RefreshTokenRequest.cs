using System.ComponentModel.DataAnnotations;

namespace AuthApi.Dtos
{
    public class RefreshTokenRequest
    {
        [Required(ErrorMessage = "RefreshToken es obligatorio.")]
        public string RefreshToken { get; set; } = string.Empty;
    }
}
