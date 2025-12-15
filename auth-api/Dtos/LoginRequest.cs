using System.ComponentModel.DataAnnotations;

namespace AuthApi.Dtos
{
    public class LoginRequest
    {
        [Required(ErrorMessage = "UserNameOrEmail es obligatorio.")]
        public string UserNameOrEmail { get; set; } = string.Empty;

        [Required(ErrorMessage = "Password es obligatorio.")]
        public string Password { get; set; } = string.Empty;
    }
}
