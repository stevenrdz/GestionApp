using System.ComponentModel.DataAnnotations;

namespace AuthApi.Dtos
{
    public class RegisterRequest
    {
        [Required(ErrorMessage = "UserName es obligatorio.")]
        [MinLength(3, ErrorMessage = "UserName debe tener al menos 3 caracteres.")]
        public string UserName { get; set; } = string.Empty;

        [Required(ErrorMessage = "Email es obligatorio.")]
        [EmailAddress(ErrorMessage = "Formato de email inválido.")]
        public string Email { get; set; } = string.Empty;

        [Required(ErrorMessage = "Password es obligatorio.")]
        [MinLength(8, ErrorMessage = "Password debe tener mínimo 8 caracteres.")]
        public string Password { get; set; } = string.Empty;
    }
}
