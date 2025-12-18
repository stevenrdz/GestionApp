import { TestBed } from '@angular/core/testing';
import {
  HttpClientTestingModule,
  HttpTestingController,
} from '@angular/common/http/testing';

import { AuthService, LoginRequest, RegisterRequest } from './auth';
import { environment } from '../../environments/environments';

describe('AuthService', () => {
  let service: AuthService;
  let httpMock: HttpTestingController;

  const TOKEN_KEY = environment.accessTokenStorageKey;

  beforeEach(() => {
    // Limpia localStorage antes de cada test
    localStorage.clear();

    TestBed.configureTestingModule({
      imports: [HttpClientTestingModule],
    });

    service = TestBed.inject(AuthService);
    httpMock = TestBed.inject(HttpTestingController);
  });

  afterEach(() => {
    httpMock.verify();
    localStorage.clear();
  });

  it('debería crearse el servicio', () => {
    expect(service).toBeTruthy();
  });

  it('register debería hacer POST a /auth-api/api/auth/register con payload', () => {
    const payload: RegisterRequest = {
      userName: 'test',
      email: 'test@test.com',
      password: '123456',
    };

    service.register(payload).subscribe();

    const req = httpMock.expectOne('/auth-api/api/auth/register');
    expect(req.request.method).toBe('POST');
    expect(req.request.body).toEqual(payload);

    req.flush({ ok: true });
  });

  it('login debería guardar accessToken en localStorage y actualizar el token actual', () => {
    const payload: LoginRequest = {
      userNameOrEmail: 'test@test.com',
      password: '123456',
    };

    const emisiones: Array<string | null> = [];
    const sub = service.currentToken$.subscribe((t) => emisiones.push(t));

    service.login(payload).subscribe((resp) => {
      expect(resp.accessToken).toBe('jwt-123');
    });

    const req = httpMock.expectOne('/auth-api/api/auth/login');
    expect(req.request.method).toBe('POST');
    expect(req.request.body).toEqual(payload);

    req.flush({
      accessToken: 'jwt-123',
      tokenType: 'Bearer',
      expiresIn: 3600,
      refreshToken: 'refresh-xyz',
    });

    // tap() ya debió ejecutar
    expect(localStorage.getItem(TOKEN_KEY)).toBe('jwt-123');
    expect(service.getToken()).toBe('jwt-123');
    expect(service.isAuthenticated()).toBe(true);

    // Emisiones típicas: [null] (inicial) y luego 'jwt-123'
    expect(emisiones).toContain('jwt-123');

    sub.unsubscribe();
  });

  it('login NO debería guardar nada si accessToken viene null', () => {
    const payload: LoginRequest = {
      userNameOrEmail: 'test@test.com',
      password: '123456',
    };

    service.login(payload).subscribe((resp) => {
      expect(resp.accessToken).toBeNull();
    });

    const req = httpMock.expectOne('/auth-api/api/auth/login');
    req.flush({
      accessToken: null,
      tokenType: 'Bearer',
      expiresIn: 3600,
      refreshToken: 'refresh-xyz',
    });

    expect(localStorage.getItem(TOKEN_KEY)).toBeNull();
    expect(service.getToken()).toBeNull();
    expect(service.isAuthenticated()).toBe(false);
  });

  it('logout debería eliminar el token y dejar el estado no autenticado', () => {
    // Simula token existente
    localStorage.setItem(TOKEN_KEY, 'jwt-abc');

    // Crear nueva instancia para que lea token desde storage al construir
    // (alternativa: llamar login, pero esto es más rápido)
    TestBed.resetTestingModule();
    TestBed.configureTestingModule({
      imports: [HttpClientTestingModule],
    });
    service = TestBed.inject(AuthService);
    httpMock = TestBed.inject(HttpTestingController);

    expect(service.getToken()).toBe('jwt-abc');
    expect(service.isAuthenticated()).toBe(true);

    service.logout();

    expect(localStorage.getItem(TOKEN_KEY)).toBeNull();
    expect(service.getToken()).toBeNull();
    expect(service.isAuthenticated()).toBe(false);

    httpMock.verify();
  });
});
