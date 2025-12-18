import { TestBed } from '@angular/core/testing';
import {
  HttpClientTestingModule,
  HttpTestingController,
} from '@angular/common/http/testing';

import {
  ApiService,
  CreateClientRequest,
  CreateOrderRequest,
} from './api';

describe('ApiService', () => {
  let service: ApiService;
  let httpMock: HttpTestingController;

  beforeEach(() => {
    TestBed.configureTestingModule({
      imports: [HttpClientTestingModule],
    });

    service = TestBed.inject(ApiService);
    httpMock = TestBed.inject(HttpTestingController);
  });

  afterEach(() => {
    // Garantiza que no queden requests pendientes
    httpMock.verify();
  });

  it('debería crearse el servicio', () => {
    expect(service).toBeTruthy();
  });

  it('getDashboard debería hacer GET a /api/dashboard', () => {
    service.getDashboard().subscribe();

    const req = httpMock.expectOne('/api/dashboard');
    expect(req.request.method).toBe('GET');

    req.flush({
      totalOrders: 0,
      completedOrders: 0,
      pendingOrders: 0,
      activeClients: 0,
      dailyActivity: [],
      monthlyActivity: [],
    });
  });

  it('getClients debería hacer GET a /api/clients', () => {
    service.getClients().subscribe();

    const req = httpMock.expectOne('/api/clients');
    expect(req.request.method).toBe('GET');

    req.flush([]);
  });

  it('createClient debería hacer POST a /api/clients con payload', () => {
    const payload: CreateClientRequest = {
      firstName: 'Juan',
      lastName: 'Pérez',
      email: 'juan@test.com',
      document: '1234567890',
      phone: null,
      address: null,
    };

    service.createClient(payload).subscribe();

    const req = httpMock.expectOne('/api/clients');
    expect(req.request.method).toBe('POST');
    expect(req.request.body).toEqual(payload);

    req.flush({ id: 1, message: 'Cliente creado' });
  });

  it('getOrders debería hacer GET a /api/orders sin params cuando no se envían filtros', () => {
    service.getOrders().subscribe();

    const req = httpMock.expectOne((r) => r.url === '/api/orders');
    expect(req.request.method).toBe('GET');

    // Sin filtros, HttpParams existe pero no debería tener valores
    expect(req.request.params.keys().length).toBe(0);

    req.flush([]);
  });

  it('getOrders debería incluir status y clientId en query params', () => {
    service.getOrders({ status: 'PENDIENTE', clientId: 5 }).subscribe();

    const req = httpMock.expectOne((r) => r.url === '/api/orders');
    expect(req.request.method).toBe('GET');

    expect(req.request.params.get('status')).toBe('PENDIENTE');
    expect(req.request.params.get('clientId')).toBe('5');

    req.flush([]);
  });

  it('createOrder debería hacer POST a /api/orders con payload', () => {
    const payload: CreateOrderRequest = {
      clientId: 1,
      totalAmount: 99.5,
      description: 'Pedido de prueba',
    };

    service.createOrder(payload).subscribe();

    const req = httpMock.expectOne('/api/orders');
    expect(req.request.method).toBe('POST');
    expect(req.request.body).toEqual(payload);

    req.flush({ id: 10, message: 'Pedido creado' });
  });

  it('completeOrder debería hacer POST a /api/orders/{id}/complete con body vacío', () => {
    service.completeOrder(10).subscribe();

    const req = httpMock.expectOne('/api/orders/10/complete');
    expect(req.request.method).toBe('POST');
    expect(req.request.body).toEqual({});

    req.flush({ message: 'Pedido completado' });
  });

  it('cancelOrder debería hacer POST a /api/orders/{id}/cancel con body vacío', () => {
    service.cancelOrder(10).subscribe();

    const req = httpMock.expectOne('/api/orders/10/cancel');
    expect(req.request.method).toBe('POST');
    expect(req.request.body).toEqual({});

    req.flush({ message: 'Pedido cancelado' });
  });
});
