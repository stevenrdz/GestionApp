import { ComponentFixture, TestBed } from '@angular/core/testing';
import { By } from '@angular/platform-browser';
import { vi } from 'vitest';

import { Notifications } from './notifications';
import { NotificationService, Toast } from '../../service/notification';

describe('Notifications', () => {
  let component: Notifications;
  let fixture: ComponentFixture<Notifications>;

  const notificationServiceMock = {
    toasts: vi.fn(),
    remove: vi.fn(),
  } as unknown as NotificationService;

  beforeEach(async () => {
    vi.clearAllMocks();

    await TestBed.configureTestingModule({
      imports: [Notifications],
      providers: [{ provide: NotificationService, useValue: notificationServiceMock }],
    }).compileComponents();

    fixture = TestBed.createComponent(Notifications);
    component = fixture.componentInstance;
  });

  it('debería crearse el componente', () => {
    expect(component).toBeTruthy();
  });

  it('trackById debería devolver el id del toast', () => {
    const toast: Toast = { id: 7, type: 'success', message: 'ok' };
    expect(component.trackById(0, toast)).toBe(7);
  });

  it('close debería llamar a notificationService.remove con el id', () => {
    component.close(10);
    expect((notificationServiceMock as any).remove).toHaveBeenCalledWith(10);
  });

  it('NO debería renderizar nada si no hay toasts', async () => {
    (notificationServiceMock as any).toasts.mockReturnValue([]);

    fixture.detectChanges();
    await fixture.whenStable();

    const container = fixture.debugElement.query(By.css('.fixed.bottom-4.right-4'));
    expect(container).toBeNull();
  });

  it('debería renderizar los toasts y al hacer click en ✕ debería eliminar el toast', async () => {
    const data: Toast[] = [
      { id: 1, type: 'success', message: 'Éxito' },
      { id: 2, type: 'error', message: 'Error' },
    ];

    (notificationServiceMock as any).toasts.mockReturnValue(data);

    fixture.detectChanges();
    await fixture.whenStable();

    const cards = fixture.debugElement.queryAll(By.css('.w-72.rounded-lg'));
    expect(cards.length).toBe(2);

    const firstCloseBtn = cards[0].query(By.css('button'));
    firstCloseBtn.triggerEventHandler('click', null);

    expect((notificationServiceMock as any).remove).toHaveBeenCalledWith(1);
  });
});
