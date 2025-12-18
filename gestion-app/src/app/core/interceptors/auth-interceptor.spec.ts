import { TestBed } from '@angular/core/testing';
import { HttpInterceptorFn, HttpRequest } from '@angular/common/http';
import { vi } from 'vitest';

import { authInterceptor } from './auth-interceptor';
import { AuthService } from '../../service/auth';

describe('authInterceptor', () => {
  const interceptor: HttpInterceptorFn = (req, next) =>
    TestBed.runInInjectionContext(() => authInterceptor(req, next));

  const authServiceMock = {
    getToken: vi.fn(),
  } as unknown as AuthService;

  beforeEach(() => {
    vi.clearAllMocks();

    TestBed.configureTestingModule({
      providers: [{ provide: AuthService, useValue: authServiceMock }],
    });
  });

  it('debería agregar el header Authorization cuando existe token', () => {
    (authServiceMock.getToken as any).mockReturnValue('abc123');

    const req = new HttpRequest('GET', '/api/clients');
    const next = vi.fn((request: HttpRequest<any>) => request);

    const result = interceptor(req, next as any) as any;

    // Se llamó a next
    expect(next).toHaveBeenCalledTimes(1);

    // Capturamos el request que llegó a next
    const reqEnviado = (next.mock.calls[0] as any)[0] as HttpRequest<any>;

    expect(reqEnviado.headers.get('Authorization')).toBe('Bearer abc123');

    // El interceptor retorna lo que retorne next
    expect(result).toBe(reqEnviado);
  });

  it('NO debería modificar el request cuando NO existe token', () => {
    (authServiceMock.getToken as any).mockReturnValue(null);

    const req = new HttpRequest('GET', '/api/clients');
    const next = vi.fn((request: HttpRequest<any>) => request);

    const result = interceptor(req, next as any) as any;

    expect(next).toHaveBeenCalledTimes(1);

    const reqEnviado = (next.mock.calls[0] as any)[0] as HttpRequest<any>;

    // Debe ser el mismo request (no se clonó)
    expect(reqEnviado).toBe(req);
    expect(reqEnviado.headers.has('Authorization')).toBe(false);

    expect(result).toBe(req);
  });
});
