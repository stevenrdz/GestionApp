import { Injectable } from '@angular/core';
import { HttpClient, HttpParams } from '@angular/common/http';
import { Observable } from 'rxjs';

/** Dashboard */

export interface DailyActivityItem {
  date: string;
  total: number;
}

export interface MonthlyActivityItem {
  month: string;
  total: number;
}

export interface DashboardStats {
  totalOrders: number;
  completedOrders: number;
  pendingOrders: number;
  activeClients: number;
  dailyActivity: DailyActivityItem[];
  monthlyActivity: MonthlyActivityItem[];
}

/** Clientes */

export interface Client {
  id: number;
  firstName: string;
  lastName: string;
  email: string;
  phone?: string | null;
  nationalId?: string | null;
  address?: string | null;
  document: string;
  typeDocument: string;
  status: string;
  createdAt: string;
  updatedAt?: string | null;
}

export interface CreateClientRequest {
  firstName: string;
  lastName: string;
  email: string;
  document: string;
  phone?: string | null;
  address?: string | null;
}

export interface CreateClientResponse {
  id: number;
  message: string;
}

/** Pedidos */

export interface CreateOrderRequest {
  clientId: number;
  totalAmount: number;
  description?: string | null;
}

export interface CreateOrderResponse {
  id: number;
  message: string;
}

// El swagger no define explícitamente el esquema de GET /api/orders,
// asumimos una estructura razonable basada en la entidad
export interface Order {
  id: number;
  clientId: number;
  totalAmount: number;
  status: string;
  description?: string | null;
  createdAt: string;
  updatedAt?: string | null;
}

@Injectable({
  providedIn: 'root',
})
export class ApiService {
  constructor(private http: HttpClient) {}

  /** Dashboard */

  getDashboard(): Observable<DashboardStats> {
    return this.http.get<DashboardStats>('/api/dashboard');
  }

  /** Clientes */

  getClients(): Observable<Client[]> {
    return this.http.get<Client[]>('/api/clients');
  }

  createClient(payload: CreateClientRequest): Observable<CreateClientResponse> {
    return this.http.post<CreateClientResponse>('/api/clients', payload);
  }

  /** Pedidos */

  getOrders(params?: { status?: string; clientId?: number }): Observable<Order[]> {
    let httpParams = new HttpParams();
    if (params?.status) {
      httpParams = httpParams.set('status', params.status);
    }
    if (params?.clientId) {
      httpParams = httpParams.set('clientId', params.clientId.toString());
    }
    return this.http.get<Order[]>('/api/orders', { params: httpParams });
  }

  createOrder(payload: CreateOrderRequest): Observable<CreateOrderResponse> {
    return this.http.post<CreateOrderResponse>('/api/orders', payload);
  }

  completeOrder(id: number): Observable<{ message: string }> {
    return this.http.post<{ message: string }>(`/api/orders/${id}/complete`, {});
  }

  cancelOrder(id: number): Observable<{ message: string }> {
    return this.http.post<{ message: string }>(`/api/orders/${id}/cancel`, {});
  }
}
