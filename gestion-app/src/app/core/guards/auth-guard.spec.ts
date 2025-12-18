import { TestBed } from '@angular/core/testing';
import { CanActivateFn, Router, UrlTree } from '@angular/router';
import { vi } from 'vitest';

import { authGuard } from './auth-guard';
import { AuthService } from '../../service/auth';

describe('authGuard', () => {
  const executeGuard: CanActivateFn = (...guardParameters) =>
    TestBed.runInInjectionContext(() => authGuard(...guardParameters));

  const authServiceMock = {
    isAuthenticated: vi.fn(),
  } as unknown as AuthService;

  const routerMock = {
    createUrlTree: vi.fn(),
  } as unknown as Router;

  beforeEach(() => {
    vi.clearAllMocks();

    TestBed.configureTestingModule({
      providers: [
        { provide: AuthService, useValue: authServiceMock },
        { provide: Router, useValue: routerMock },
      ],
    });
  });

  it('debería permitir el acceso cuando el usuario está autenticado', () => {
    authServiceMock.isAuthenticated = vi.fn(() => true);

    const result = executeGuard({} as any, {} as any);

    expect(result).toBe(true);
    expect(routerMock.createUrlTree).not.toHaveBeenCalled();
  });

  it('debería redirigir a /auth/login cuando el usuario NO está autenticado', () => {
    authServiceMock.isAuthenticated = vi.fn(() => false);

    const fakeTree = {} as UrlTree;
    (routerMock.createUrlTree as any) = vi.fn(() => fakeTree);

    const result = executeGuard({} as any, {} as any);

    expect(routerMock.createUrlTree).toHaveBeenCalledWith(['/auth/login']);
    expect(result).toBe(fakeTree);
  });
});
