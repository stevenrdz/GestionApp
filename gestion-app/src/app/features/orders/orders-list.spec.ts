import { ComponentFixture, TestBed } from '@angular/core/testing';
import { of, throwError } from 'rxjs';
import { vi } from 'vitest';

import { OrdersList } from './orders-list';
import { ApiService, Order, Client } from '../../service/api';
import { NotificationService } from '../../service/notification';

describe('OrdersList', () => {
  let fixture: ComponentFixture<OrdersList>;
  let component: OrdersList;

  const api = {
    getClients: vi.fn(),
    getOrders: vi.fn(),
    createOrder: vi.fn(),
    completeOrder: vi.fn(),
    cancelOrder: vi.fn(),
  } as unknown as ApiService;

  const notificaciones = {
    success: vi.fn(),
    error: vi.fn(),
    info: vi.fn(),
    remove: vi.fn(),
  } as unknown as NotificationService;

  const clientesMock: Client[] = [
    {
      id: 1,
      firstName: 'Juan',
      lastName: 'Pérez',
      email: 'juan@test.com',
      phone: null,
      nationalId: null,
      address: null,
      document: '1234567890',
      typeDocument: 'CI',
      status: 'ACTIVO',
      createdAt: '2025-01-01',
      updatedAt: null,
    },
  ];

  const pedidosMock: Order[] = [
    {
      id: 10,
      clientId: 1,
      totalAmount: 50,
      status: 'PENDIENTE',
      description: 'Pedido de prueba',
      createdAt: '2025-01-01',
      updatedAt: null,
    },
  ];

  beforeEach(async () => {
    vi.clearAllMocks();
    vi.useRealTimers();

    // Silenciar consola del componente (recomendado)
    vi.spyOn(console, 'log').mockImplementation(() => {});
    vi.spyOn(console, 'warn').mockImplementation(() => {});
    vi.spyOn(console, 'error').mockImplementation(() => {});

    (api.getClients as any).mockReturnValue(of(clientesMock));
    (api.getOrders as any).mockReturnValue(of(pedidosMock));
    (api.createOrder as any).mockReturnValue(of({ id: 99, message: 'Pedido creado correctamente.' }));
    (api.completeOrder as any).mockReturnValue(of({ message: 'Pedido completado.' }));
    (api.cancelOrder as any).mockReturnValue(of({ message: 'Pedido cancelado.' }));

    await TestBed.configureTestingModule({
      imports: [OrdersList],
      providers: [
        { provide: ApiService, useValue: api },
        { provide: NotificationService, useValue: notificaciones },
      ],
    }).compileComponents();

    fixture = TestBed.createComponent(OrdersList);
    component = fixture.componentInstance;

    // Dispara ngOnInit
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

  it('al iniciar debería cargar clientes y pedidos', () => {
    expect(api.getClients).toHaveBeenCalled();
    expect(api.getOrders).toHaveBeenCalled();
  });

  it('loadOrders debería aplicar filtros', () => {
    component.filterStatus = 'PENDIENTE';
    component.filterClientId = 1;

    component.loadOrders();

    expect(api.getOrders).toHaveBeenCalledWith({ status: 'PENDIENTE', clientId: 1 });
  });

  it('loadOrders debería establecer mensaje de error cuando falla la API', () => {
    (api.getOrders as any).mockReturnValue(
      throwError(() => ({ error: { message: 'Error del servidor' } }))
    );

    component.loadOrders();

    expect(component.error).toBe('No se pudo cargar la lista de pedidos');
  });

  it('createOrder debería validar datos obligatorios', () => {
    component.newOrder = { clientId: 0, totalAmount: 0, description: null };

    component.createOrder();

    expect(notificaciones.error).toHaveBeenCalledWith('Selecciona cliente y monto total.');
    expect(api.createOrder).not.toHaveBeenCalled();
  });

  it('createOrder debería crear pedido, notificar y recargar', () => {
    vi.useFakeTimers();
    const spyRecarga = vi.spyOn(component as any, 'loadOrders');

    component.showCreateForm = true;
    component.newOrder = { clientId: 1, totalAmount: 100, description: null };

    component.createOrder();
    expect(api.createOrder).toHaveBeenCalled();

    // Ejecuta el setTimeout(..., 0)
    vi.runOnlyPendingTimers();

    expect(notificaciones.success).toHaveBeenCalled();
    expect(component.showCreateForm).toBe(false);
    expect(spyRecarga).toHaveBeenCalled();
  });

  it('complete debería completar pedido y recargar', () => {
    vi.useFakeTimers();
    const spyRecarga = vi.spyOn(component as any, 'loadOrders');

    component.complete({ id: 10 } as Order);
    expect(api.completeOrder).toHaveBeenCalledWith(10);

    vi.runOnlyPendingTimers();

    expect(notificaciones.success).toHaveBeenCalled();
    expect(spyRecarga).toHaveBeenCalled();
  });

  it('cancel debería cancelar pedido y recargar', () => {
    vi.useFakeTimers();
    const spyRecarga = vi.spyOn(component as any, 'loadOrders');

    component.cancel({ id: 10 } as Order);
    expect(api.cancelOrder).toHaveBeenCalledWith(10);

    vi.runOnlyPendingTimers();

    expect(notificaciones.success).toHaveBeenCalled();
    expect(spyRecarga).toHaveBeenCalled();
  });

  it('trackByOrderId debería devolver el id del pedido', () => {
    expect(component.trackByOrderId(0, { id: 99 } as Order)).toBe(99);
  });
});
