import { Component } from '@angular/core';
import { CommonModule } from '@angular/common';
import { FormsModule } from '@angular/forms';
import { Router } from '@angular/router';
import { AuthService } from '../../service/auth';
import { NotificationService } from '../../service/notification';

@Component({
  selector: 'app-register',
  standalone: true,
  imports: [CommonModule, FormsModule],
  templateUrl: './register.html',
  styleUrl: './register.css',
})
export class Register {
  userName = '';
  email = '';
  password = '';
  loading = false;
  message: string | null = null;
  error: string | null = null;

  constructor(
    private authService: AuthService,
    private router: Router,
    private notificationService: NotificationService
  ) {}

  onSubmit(): void {
    this.error = null;
    this.message = null;
    this.loading = true;

    this.authService
      .register({
        userName: this.userName,
        email: this.email,
        password: this.password
      })
      .subscribe({
        next: () => {
          this.loading = false;
          this.message = 'Registro exitoso, ahora puedes iniciar sesión.';

          this.notificationService.success(
            'Usuario registrado correctamente. Ahora puedes iniciar sesión.'
          );

          setTimeout(() => this.router.navigate(['/auth/login']), 1500);
        },
        error: (err) => {
          this.loading = false;
          this.error = err?.error?.detail || 'Error al registrar';

          this.notificationService.error(
            'No se pudo registrar el usuario. Revisa los datos o intenta más tarde.'
          );
        }
      });
  }
}
