import { TestBed } from '@angular/core/testing';
import { vi } from 'vitest';

import { NotificationService } from './notification';

describe('NotificationService', () => {
  let service: NotificationService;

  beforeEach(() => {
    TestBed.configureTestingModule({});
    service = TestBed.inject(NotificationService);
    vi.useRealTimers();
  });

  afterEach(() => {
    vi.useRealTimers();
  });

  it('debería crearse el servicio', () => {
    expect(service).toBeTruthy();
  });

  it('success debería agregar un toast de tipo success', () => {
    service.success('OK', 0); // sin auto-expire
    const toasts = service.toasts();

    expect(toasts.length).toBe(1);
    expect(toasts[0].type).toBe('success');
    expect(toasts[0].message).toBe('OK');
    expect(toasts[0].id).toBe(1);
  });

  it('error e info deberían agregar toasts con tipos correctos y ids incrementales', () => {
    service.error('Falló', 0);
    service.info('Aviso', 0);

    const toasts = service.toasts();
    expect(toasts.length).toBe(2);

    expect(toasts[0].type).toBe('error');
    expect(toasts[0].message).toBe('Falló');
    expect(toasts[0].id).toBe(1);

    expect(toasts[1].type).toBe('info');
    expect(toasts[1].message).toBe('Aviso');
    expect(toasts[1].id).toBe(2);
  });

  it('remove debería eliminar un toast por id', () => {
    service.success('A', 0);
    service.success('B', 0);

    expect(service.toasts().length).toBe(2);

    const idA = service.toasts()[0].id;
    service.remove(idA);

    const toasts = service.toasts();
    expect(toasts.length).toBe(1);
    expect(toasts[0].message).toBe('B');
  });

  it('debería auto-eliminar el toast después de durationMs', () => {
    vi.useFakeTimers();

    service.success('Auto', 4000);
    expect(service.toasts().length).toBe(1);

    // Aún existe antes del tiempo
    vi.advanceTimersByTime(3999);
    expect(service.toasts().length).toBe(1);

    // Al llegar al tiempo se elimina
    vi.advanceTimersByTime(1);
    expect(service.toasts().length).toBe(0);
  });

  it('NO debería auto-eliminar si durationMs es 0', () => {
    vi.useFakeTimers();

    service.success('Persistente', 0);
    vi.advanceTimersByTime(10000);

    expect(service.toasts().length).toBe(1);
    expect(service.toasts()[0].message).toBe('Persistente');
  });
});
