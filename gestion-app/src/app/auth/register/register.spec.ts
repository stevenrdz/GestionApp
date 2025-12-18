import { ComponentFixture, TestBed } from '@angular/core/testing';
import { of, throwError } from 'rxjs';
import { vi } from 'vitest';

import { Register } from './register';
import { AuthService } from '../../service/auth';
import { NotificationService } from '../../service/notification';
import { Router } from '@angular/router';
import { delay } from 'rxjs/operators';

describe('Register', () => {
  let component: Register;
  let fixture: ComponentFixture<Register>;

  const authService = {
    register: vi.fn(),
  } as unknown as AuthService;

  const router = {
    navigate: vi.fn(),
  } as unknown as Router;

  const notificationService = {
    success: vi.fn(),
    error: vi.fn(),
    info: vi.fn(),
    remove: vi.fn(),
  } as unknown as NotificationService;

  beforeEach(async () => {
    vi.clearAllMocks();
    vi.useRealTimers();

    // Silenciar consola si tu componente/servicios loguean (opcional)
    vi.spyOn(console, 'log').mockImplementation(() => {});
    vi.spyOn(console, 'warn').mockImplementation(() => {});
    vi.spyOn(console, 'error').mockImplementation(() => {});

    await TestBed.configureTestingModule({
      imports: [Register],
      providers: [
        { provide: AuthService, useValue: authService },
        { provide: Router, useValue: router },
        { provide: NotificationService, useValue: notificationService },
      ],
    }).compileComponents();

    fixture = TestBed.createComponent(Register);
    component = fixture.componentInstance;

    // Dispara bindings + ng lifecycle (aunque aquí no hay ngOnInit, es buena práctica)
    fixture.detectChanges();
    await fixture.whenStable();
  });

  afterEach(() => {
    vi.restoreAllMocks();
    vi.useRealTimers();
  });

  it('debería crearse el componente', () => {
    expect(component).toBeTruthy();
  });

  it('onSubmit debería llamar a register y en éxito notificar, setear mensaje y navegar luego de 1500ms', () => {
    // Emisión ASÍNCRONA para poder validar loading=true antes del next
    (authService.register as any).mockReturnValue(of({}).pipe(delay(0)));
  
    component.userName = 'testuser';
    component.email = 'test@test.com';
    component.password = '123456';
  
    vi.useFakeTimers();
  
    component.onSubmit();
  
    // Ahora sí existe el estado intermedio
    expect(component.loading).toBe(true);
    expect(component.error).toBeNull();
    expect(component.message).toBeNull();
  
    expect(authService.register).toHaveBeenCalledWith({
      userName: 'testuser',
      email: 'test@test.com',
      password: '123456',
    });
  
    // Dispara el delay(0) (flush del "next")
    vi.runOnlyPendingTimers();
  
    // Ya ejecutó next
    expect(component.loading).toBe(false);
    expect(component.message).toBe('Registro exitoso, ahora puedes iniciar sesión.');
    expect(notificationService.success).toHaveBeenCalledWith(
      'Usuario registrado correctamente. Ahora puedes iniciar sesión.'
    );
  
    // Aún no navega (espera 1500ms)
    expect(router.navigate).not.toHaveBeenCalled();
  
    // Ejecuta el setTimeout(1500)
    vi.advanceTimersByTime(1500);
  
    expect(router.navigate).toHaveBeenCalledWith(['/auth/login']);
  
    vi.useRealTimers();
  });

  it('onSubmit debería manejar error del servicio, setear error y notificar', () => {
    (authService.register as any).mockReturnValue(
      throwError(() => ({ error: { detail: 'Correo ya existe' } }))
    );

    component.userName = 'testuser';
    component.email = 'test@test.com';
    component.password = '123456';

    component.onSubmit();

    expect(component.loading).toBe(false);
    expect(component.message).toBeNull();
    expect(component.error).toBe('Correo ya existe');

    expect(notificationService.error).toHaveBeenCalledWith(
      'No se pudo registrar el usuario. Revisa los datos o intenta más tarde.'
    );

    expect(router.navigate).not.toHaveBeenCalled();
  });

  it('onSubmit debería usar mensaje por defecto si no viene detail', () => {
    (authService.register as any).mockReturnValue(
      throwError(() => ({}))
    );

    component.onSubmit();

    expect(component.loading).toBe(false);
    expect(component.error).toBe('Error al registrar');
    expect(notificationService.error).toHaveBeenCalled();
  });
});
